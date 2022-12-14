<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Service;

use Throwable;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

class AuditVoidService implements AuditServiceInterface
{
    public function audit(string $type, string $message, array $data = [], string $class = ''): void
    {
    }

    public function auditException(Throwable $exception, ?string $url = null, string $class = ''): void
    {
    }
}
