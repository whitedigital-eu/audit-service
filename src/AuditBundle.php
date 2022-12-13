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
    public const AUTH = 'AUTHENTICATION';
    public const DB = 'DATABASE';
    public const ETL = 'ETL_PIPELINE';
    public const EXCEPTION = 'EXCEPTION';
    public const EXTERNAL = 'EXTERNAL_CALL';

    private const AUDIT_TYPES = [
        self::AUTH,
        self::DB,
        self::ETL,
        self::EXCEPTION,
        self::EXTERNAL,
    ];

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (true === $config['enabled']) {
            $builder->setParameter('whitedigital.audit.enabled', $config['enabled']);
            $builder->setParameter('whitedigital.audit.entity_manager', $config['entity_manager']);
            $builder->setParameter('whitedigital.audit.excluded_response_codes', $config['excluded_response_codes'] ?? [Response::HTTP_NOT_FOUND]);
            $builder->setParameter('whitedigital.audit.audit_types', array_merge($config['additional_audit_types'] ?? [], self::AUDIT_TYPES));

            $container->import('../config/services.php');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (true === ($builder->getExtensionConfig('audit')[0]['enabled'] ?? false)) {
            $this->addDoctrineConfig($container, $builder->getExtensionConfig('audit')[0]['entity_manager']);
            if (null !== ($builder->getExtensionConfig('audit')[0]['default_entity_manager'] ?? null)) {
                $this->addDoctrineConfig($container, $builder->getExtensionConfig('audit')[0]['default_entity_manager']);
            }
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('entity_manager')->defaultValue('default')->end()
                ->scalarNode('default_entity_manager')->defaultValue(null)->end()
                ->arrayNode('excluded_response_codes')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('additional_audit_types')
                    ->scalarPrototype()->end()
                ->end()
            ->end();
    }

    private function addDoctrineConfig(ContainerConfigurator $container, string $entityManager): void
    {
        $container->extension('doctrine', [
            'orm' => [
                'entity_managers' => [
                    $entityManager => [
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
}
