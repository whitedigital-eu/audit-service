<?php declare(strict_types = 1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use WhiteDigital\Audit\Service\AuditVoidService;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
        ->set('whitedigital.audit.service.audit_void')
        ->class(AuditVoidService::class)
        ->tag('whitedigital.audit', ['priority' => 0, ], );
};
