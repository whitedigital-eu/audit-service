<?php declare(strict_types = 1);

namespace WhiteDigital\Audit;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WhiteDigital\Audit\Contracts\AuditType;

use function array_merge;

class AuditBundle extends AbstractBundle implements AuditType
{
    protected string $extensionAlias = 'whitedigital';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $audit = $config['audit'];

        if (true === $audit['enabled']) {
            $this->validate($audit);

            $builder->setParameter('whitedigital.audit.enabled', $audit['enabled']);
            $builder->setParameter('whitedigital.audit.audit_entity_manager', $audit['audit_entity_manager']);
            $builder->setParameter('whitedigital.audit.excluded_response_codes', $audit['excluded_response_codes'] ?? [Response::HTTP_NOT_FOUND]);
            $builder->setParameter('whitedigital.audit.audit_types', array_merge($audit['additional_audit_types'] ?? [], AuditType::AUDIT_TYPES));

            if (true === $audit['custom_configuration'] ?? false) {
                $container->import('../config/void_audit.php');
            } else {
                $container->import('../config/audit_service.php');
            }

            $container->import('../config/services.php');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $audit = $builder->getExtensionConfig('whitedigital')[0]['audit'];

        if (true === ($audit['enabled'] ?? false) && true === ($audit['set_doctrine_mappings'] ?? true)) {
            $this->validate($audit);

            $this->addDoctrineConfig($container, $audit['audit_entity_manager']);
            $this->addDoctrineConfig($container, $audit['default_entity_manager']);
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
                ->arrayNode('audit')
                ->canBeEnabled()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('audit_entity_manager')->defaultNull()->end()
                    ->scalarNode('default_entity_manager')->defaultNull()->end()
                    ->arrayNode('excluded_response_codes')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('additional_audit_types')
                        ->scalarPrototype()->end()
                    ->end()
                    ->booleanNode('set_doctrine_mappings')->defaultTrue()->end()
                    ->booleanNode('custom_configuration')->defaultFalse()->end()
                ->end()
            ->end();
    }

    private function validate(array $config): void
    {
        $auditEntityManager = $config['audit_entity_manager'] ?? null;
        $defaultEntityManager = $config['default_entity_manager'] ?? null;

        if (false === ($config['custom_configuration'] ?? false)) {
            if (null === $auditEntityManager || null === $defaultEntityManager) {
                throw new InvalidConfigurationException('WhiteDigital\Audit: "audit_entity_manager" and "default_entity_manager" names must be set');
            }
        }

        if (null !== $auditEntityManager && null !== $defaultEntityManager && $auditEntityManager === $defaultEntityManager) {
            throw new InvalidConfigurationException('WhiteDigital\Audit: "audit_entity_manager" and "default_entity_manager" names must be different');
        }
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
