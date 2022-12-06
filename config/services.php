<?php declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WhiteDigital\Audit\Service\AuditService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(AuditService::class)
                ->args([
                    service('request_stack'),
                    service('security.helper'),
                    service('translator'),
                    service('doctrine'),
                    '%wd.audit.entity_manager%',
                ]);
};
