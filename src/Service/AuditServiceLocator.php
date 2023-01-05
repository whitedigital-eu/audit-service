<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Service;

use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Entity\Audit;

use function array_key_first;

class AuditServiceLocator implements AuditServiceInterface
{
    private readonly AuditServiceInterface $audit;

    public function __construct(#[TaggedLocator(tag: 'whitedigital.audit')] ServiceLocator $audits)
    {
        $this->audit = $audits->get(array_key_first($audits->getProvidedServices()));
    }

    public function audit(string $type, string $message, array $data = [], string $class = Audit::class): void
    {
        $this->audit->audit($type, $message, $data, $class);
    }

    public function auditException(Throwable $exception, ?string $url = null, string $class = Audit::class): void
    {
        $this->audit->auditException($exception, $url, $class);
    }
}
