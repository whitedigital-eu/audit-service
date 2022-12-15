<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Contracts;

use Throwable;

interface AuditServiceInterface
{
    public function audit(string $type, string $message, array $data = [], string $class = '');

    public function auditException(Throwable $exception, ?string $url = null, string $class = '');
}
