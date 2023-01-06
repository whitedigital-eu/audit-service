<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Service;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\Audit\Entity\Audit;

use function class_implements;
use function in_array;
use function mb_strimwidth;
use function method_exists;
use function sprintf;

class AuditService implements AuditServiceInterface
{
    private readonly ObjectManager $entityManager;
    private readonly array $excludedCodes;
    private readonly array $auditTypes;
    private readonly array $excludedPaths;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        ManagerRegistry $registry,
        private readonly ParameterBagInterface $bag,
    ) {
        $this->entityManager = $registry->getManager($this->bag->get('whitedigital.audit.audit_entity_manager'));
        $this->excludedCodes = $this->bag->get('whitedigital.audit.excluded_response_codes');
        $this->auditTypes = $this->bag->get('whitedigital.audit.audit_types');
        $this->excludedPaths = $this->bag->get('whitedigital.audit.excluded_paths');
    }

    public function auditException(Throwable $exception, ?string $url = null, string $class = Audit::class): void
    {
        if (
            method_exists($exception, 'getStatusCode') &&
            in_array($exception->getStatusCode(), $this->excludedCodes, true, )
        ) {
            return;
        }

        if (in_array($url, $this->excludedPaths, true)) {
            return;
        }

        $this->audit(AuditType::EXCEPTION, mb_strimwidth($exception->getMessage(), 0, 500, '...'), [
            'exceptionClass' => $exception::class,
            'url' => $url,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stackTrace' => $exception->getTraceAsString(),
        ], $class);
    }

    public function audit(string $type, string $message, array $data = [], string $class = Audit::class): void
    {
        if (!in_array($type, $this->auditTypes, true)) {
            throw new InvalidArgumentException(sprintf('Invalid type: %s. Allowed types: %s', $type, rtrim(implode(' ,', $this->auditTypes), ', ')));
        }

        if (!in_array(AuditEntityInterface::class, class_implements($class), true)) {
            throw new InvalidArgumentException(sprintf('To use other entity than "%s" in audit, "%s" must implement "%s"', Audit::class, $class, AuditEntityInterface::class));
        }

        /** @var AuditEntityInterface $audit */
        $audit = (new $class())
            ->setUserIdentifier($this->security->getUser()?->getUserIdentifier())
            ->setIpAddress($this->requestStack->getMainRequest()?->getClientIp())
            ->setCategory($this->translator->trans(sprintf('audit.%s', $type)))
            ->setMessage($message)
            ->setData($data)
            ->setCreatedAt(new DateTimeImmutable());

        $this->entityManager->persist($audit);
        $this->entityManager->flush();
    }
}
