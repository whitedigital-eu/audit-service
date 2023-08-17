<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use WhiteDigital\Audit\AuditBundle;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\Audit\Entity\Audit;

use function array_merge;
use function array_unique;
use function array_values;
use function class_implements;
use function implode;
use function in_array;
use function is_scalar;
use function mb_strimwidth;
use function method_exists;
use function rtrim;
use function sort;

class AuditService implements AuditServiceInterface
{
    private readonly ObjectManager $entityManager;
    private readonly array $excludedCodes;
    private readonly array $auditTypes;
    private readonly array $excludedPaths;
    private readonly ?string $typeInterface;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        ManagerRegistry $registry,
        private readonly ParameterBagInterface $bag,
    ) {
        $this->entityManager = $registry->getManager($this->bag->get('whitedigital.audit.audit_entity_manager'));
        $this->excludedCodes = $this->bag->get('whitedigital.audit.excluded.response_codes');
        $this->auditTypes = $this->bag->get('whitedigital.audit.additional_audit_types');
        $this->excludedPaths = $this->bag->get('whitedigital.audit.excluded.paths');

        if (null !== $namespace = $this->bag->get('whitedigital.audit.audit_type_interface_namespace')) {
            $this->typeInterface = rtrim($namespace, '\\') . '\\' . $this->bag->get('whitedigital.audit.audit_type_interface_class_name');
        } else {
            $this->typeInterface = null;
        }
    }

    public function auditException(Throwable $exception, ?string $url = null, string $class = Audit::class): void
    {
        if (
            method_exists($exception, 'getStatusCode')
            && in_array($exception->getStatusCode(), $this->excludedCodes, true)
        ) {
            return;
        }

        if (in_array($url, $this->excludedPaths, true)) {
            return;
        }

        $data = [
            'exceptionClass' => $exception::class,
            'url' => $url,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stackTrace' => $exception->getTraceAsString(),
        ];

        $mainRequestPayload = $this->requestStack->getMainRequest()?->getContent() ?: '';
        $subRequestPayload = $this->requestStack->getCurrentRequest()?->getContent() ?: '';

        if ('' !== $mainRequestPayload) {
            $data['main_request_payload'] = $mainRequestPayload;
        }

        if ('' !== $subRequestPayload && $mainRequestPayload !== $subRequestPayload) {
            $data['sub_request_payload'] = $subRequestPayload;
        }

        $this->audit(AuditType::EXCEPTION, mb_strimwidth($exception->getMessage(), 0, 500, '...'), $data, $class);
    }

    public function audit(string $type, string $message, array $data = [], string $class = Audit::class): void
    {
        $this->validateType($type);

        $location = '';
        if (null !== ($url = $data['url'] ?? null) && is_scalar($url)) {
            $location = ' ' . $url;
        }

        if (!in_array(AuditEntityInterface::class, class_implements($class), true)) {
            throw new InvalidArgumentException($this->translator->trans('missing_implementation', ['%default%' => Audit::class, '%current%' => $class, '%interface%' => AuditEntityInterface::class], domain: 'Audit'));
        }

        /** @var AuditEntityInterface $audit */
        $audit = (new $class())
            ->setUserIdentifier($this->security->getUser()?->getUserIdentifier())
            ->setIpAddress($this->requestStack->getMainRequest()?->getClientIp())
            ->setCategory($this->translator->trans($type, domain: 'Audit') . $location)
            ->setMessage($message)
            ->setData($data);

        $this->entityManager->persist($audit);
        $this->entityManager->flush();
    }

    public static function getDefaultPriority(): int
    {
        return 1;
    }

    private function validateType(string $type): void
    {
        $allowedTypes = $this->auditTypes;
        if (null !== $this->typeInterface) {
            $allowedTypes = array_unique(array_merge($allowedTypes, array_values(AuditBundle::getConstants($this->typeInterface))));
            sort($allowedTypes);
        }

        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException($this->translator->trans('invalid_parameter_list_allowed', ['%parameter%' => $type, '%allowed%' => rtrim(implode(', ', $allowedTypes), ', ')], domain: 'Audit'));
        }
    }
}
