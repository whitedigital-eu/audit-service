<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Enum;

enum AuditType: string
{
    case EXCEPTION = 'EXCEPTION';
    case AUTH = 'AUTHENTICATION';
    case DB = 'DATABASE';
    case EXTERNAL = 'EXTERNAL_CALL';
    case ETL = 'ETL_PIPELINE';
}
