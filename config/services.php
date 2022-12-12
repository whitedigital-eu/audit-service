<?php declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Service\AuditService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->remove(AuditServiceInterface::class);

    $services
        ->set(AuditServiceInterface::class)
            ->class(AuditService::class)
            ->args([
                service('request_stack'),
                service('security.helper'),
                service('translator'),
                service('doctrine'),
                service('parameter_bag'),
            ]);

    $services->load(namespace: 'WhiteDigital\\Audit\\', resource: __DIR__ . '/../src/*')
        ->exclude(excludes: [__DIR__ . '/../src/{Entity}']);
};
