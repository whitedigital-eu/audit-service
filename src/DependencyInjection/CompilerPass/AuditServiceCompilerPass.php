<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\DependencyInjection\CompilerPass;

use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Service\AuditService;

class AuditServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @throws ReflectionException
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AuditServiceInterface::class)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(AuditServiceInterface::class);

        $maxPriority = AuditService::getDefaultPriority();
        $highestPriorityService = null;
        foreach ($taggedServices as $service => $ignored) {
            $priority = $container->getReflectionClass($service)->getName()::getDefaultPriority();

            if ($maxPriority < $priority) {
                $maxPriority = $priority;
                $highestPriorityService = $service;
            }
        }

        if (null !== $highestPriorityService) {
            $container->setDefinition(AuditServiceInterface::class, $container->getDefinition($highestPriorityService));
        }
    }
}
