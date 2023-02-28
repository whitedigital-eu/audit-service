<?php declare(strict_types = 1);

namespace WhiteDigital\Audit;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WhiteDigital\ApiResource\DependencyInjections\Traits\DefineApiPlatformMappings;
use WhiteDigital\ApiResource\DependencyInjections\Traits\DefineOrmMappings;
use WhiteDigital\ApiResource\Functions;
use WhiteDigital\Audit\Contracts\AuditType;

use function array_merge;
use function array_merge_recursive;

class AuditBundle extends AbstractBundle implements AuditType
{
    use DefineApiPlatformMappings;
    use DefineOrmMappings;

    private const MAPPINGS = [
        'type' => 'attribute',
        'dir' => __DIR__ . '/Entity',
        'alias' => 'Audit',
        'prefix' => 'WhiteDigital\Audit\Entity',
        'is_bundle' => false,
        'mapping' => true,
    ];

    private const PATHS = [
        '%kernel.project_dir%/vendor/whitedigital-eu/audit-service/src/ApiResource',
    ];

    protected string $extensionAlias = 'whitedigital';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $audit = $config['audit'] ?? [];

        if (true === $audit['enabled'] ?? false) {
            $this->validate($audit);

            $erc = [Response::HTTP_NOT_FOUND];
            if (isset($audit['excluded']['response_codes'])) {
                $erc = $audit['excluded']['response_codes'];
            }

            foreach ((new Functions())->makeOneDimension(['whitedigital' => $config], onlyLast: true) as $key => $value) {
                $builder->setParameter($key, $value);
            }

            $builder->setParameter('whitedigital.audit.excluded.response_codes', $erc);
            $builder->setParameter('whitedigital.audit.additional_audit_types', array_merge($audit['additional_audit_types'] ?? [], AuditType::AUDIT_TYPES));

            if (!$builder->hasParameter($key1 = 'whitedigital.audit.excluded.paths')) {
                $builder->setParameter($key1, []);
            }

            if (!$builder->hasParameter($key2 = 'whitedigital.audit.excluded.routes')) {
                $builder->setParameter($key2, []);
            }

            if (true === $audit['custom_configuration'] ?? false) {
                $container->import('../config/void_audit.php');
            } else {
                $container->import('../config/audit_service.php');
            }

            $container->import('../config/services.php');
        }

        $container->import('../config/global.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $audit = array_merge_recursive(...$builder->getExtensionConfig('whitedigital') ?? [])['audit'] ?? [];

        if (true === ($audit['enabled'] ?? false)) {
            if (true === ($audit['set_doctrine_mappings'] ?? true)) {
                $this->validate($audit);

                $mappings = $this->getOrmMappings($builder, $audit['default_entity_manager']);

                $this->addDoctrineConfig($container, $audit['audit_entity_manager'], $mappings, 'Audit', self::MAPPINGS, true);
                $this->addDoctrineConfig($container, $audit['default_entity_manager'], $mappings, 'Audit', self::MAPPINGS);
            }

            if (false === ($audit['custom_configuration'] ?? false)) {
                $this->addApiPlatformPaths($container, self::PATHS);
            }
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
                    ->arrayNode('additional_audit_types')
                        ->scalarPrototype()->end()
                    ->end()
                    ->booleanNode('set_doctrine_mappings')->defaultTrue()->end()
                    ->booleanNode('custom_configuration')->defaultFalse()->end()
                    ->arrayNode('excluded')
                        ->children()
                            ->arrayNode('response_codes')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('paths')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('routes')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->booleanNode('enable_audit_resource')->defaultTrue()->end()
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
}
