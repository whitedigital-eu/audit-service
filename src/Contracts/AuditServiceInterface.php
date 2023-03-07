<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Contracts;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Throwable;

#[AutoconfigureTag]
interface AuditServiceInterface
{
    public function audit(string $type, string $message, array $data = [], string $class = '');

    public function auditException(Throwable $exception, ?string $url = null, string $class = '');

    public static function getDefaultPriority(): int;
}
