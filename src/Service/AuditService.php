<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Service;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use WhiteDigital\Audit\Entity\Audit;
use WhiteDigital\Audit\Enum\AuditType;

use function in_array;
use function mb_strimwidth;
use function method_exists;
use function sprintf;

class AuditService
{
    public array $ignorableExceptionStatusCodes = [Response::HTTP_NOT_FOUND];

    private readonly ObjectManager $entityManager;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        ManagerRegistry $registry,
        ?string $managerName = null,
    ) {
        $this->entityManager = $registry->getManager($managerName);
    }

    public function auditException(Throwable $exception, ?string $url = null): void
    {
        if (
            method_exists($exception, 'getStatusCode') &&
            in_array($exception->getStatusCode(), $this->ignorableExceptionStatusCodes, true, )
        ) {
            return;
        }

        $this->audit(AuditType::EXCEPTION, mb_strimwidth($exception->getMessage(), 0, 500, '...'), [
            'exceptionClass' => $exception::class,
            'url' => $url,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stackTrace' => $exception->getTraceAsString(),
        ]);
    }

    public function audit(AuditType $type, string $message, array $data = []): void
    {
        $audit = (new Audit())
            ->setUserIdentifier($this->security->getUser()?->getUserIdentifier())
            ->setIpAddress($this->requestStack->getMainRequest()?->getClientIp())
            ->setCategory($this->translator->trans(sprintf('audit.event.%s', $type->value)))
            ->setMessage($message)
            ->setData($data)
            ->setCreatedAt(new DateTimeImmutable());

        $this->entityManager->persist($audit);
        $this->entityManager->flush();
    }
}
