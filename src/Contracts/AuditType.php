<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Contracts;

interface AuditType
{
    public const AUTH = 'AUTHENTICATION';
    public const DB = 'DATABASE';
    public const ETL = 'ETL';
    public const EXCEPTION = 'EXCEPTION';
    public const EXTERNAL = 'EXTERNAL';
}
