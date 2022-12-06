<?php declare(strict_types = 1);

namespace WhiteDigital\Audit;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function array_merge;

class AuditBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (true === $config['enabled']) {
            $container->import('../config/services.php');

            $container->parameters()->set('wd.audit.entity_manager', $config['entity_manager']);
            $container->parameters()->set('wd.audit.excluded_response_codes', array_merge($config['excluded_response_codes'], [Response::HTTP_NOT_FOUND]));
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->extension('doctrine', [
            'orm' => [
                'entity_managers' => [
                    $builder->getExtensionConfig('audit')[0]['entity_manager'] => [
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                        'mappings' => [
                            'Audit' => [
                                'type' => 'attribute',
                                'dir' => __DIR__ . '/Entity',
                                'alias' => 'Audit',
                                'prefix' => 'WhiteDigital\Audit\Entity',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->canBeDisabled()
            ->children()
                ->scalarNode('entity_manager')->defaultValue('default')->end()
                ->arrayNode('excluded_response_codes')
                    ->scalarPrototype()->end()
                ->end()
            ->end();
    }
}
