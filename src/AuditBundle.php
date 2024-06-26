<?php declare(strict_types = 1);

namespace WhiteDigital\Audit;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\Audit\DependencyInjection\CompilerPass\AuditServiceCompilerPass;
use WhiteDigital\EntityResourceMapper\DependencyInjection\Traits\DefineApiPlatformMappings;
use WhiteDigital\EntityResourceMapper\DependencyInjection\Traits\DefineOrmMappings;
use WhiteDigital\EntityResourceMapper\EntityResourceMapperBundle;

use function array_map;
use function array_merge;
use function array_merge_recursive;
use function array_values;
use function sort;

class AuditBundle extends AbstractBundle
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

    private const API_RESOURCE_PATH = '%kernel.project_dir%/vendor/whitedigital-eu/audit-service/src/Api/Resource';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->validate($config);

        $erc = [Response::HTTP_NOT_FOUND];
        if (isset($config['excluded']['response_codes'])) {
            $erc = $config['excluded']['response_codes'];
        }

        foreach (EntityResourceMapperBundle::makeOneDimension(['whitedigital.audit' => $config], onlyLast: true) as $key => $value) {
            $builder->setParameter($key, $value);
        }
        $builder->setParameter('whitedigital.audit.excluded.response_codes', $erc);

        $types = array_map('strtoupper', array_merge($audit['additional_audit_types'] ?? [], array_values(self::getConstants(AuditType::class))));
        sort($types);
        $builder->setParameter('whitedigital.audit.additional_audit_types', $types);

        if (!$builder->hasParameter($paths = 'whitedigital.audit.excluded.paths')) {
            $builder->setParameter($paths, []);
        }

        if (!$builder->hasParameter($routes = 'whitedigital.audit.excluded.routes')) {
            $builder->setParameter($routes, []);
        }

        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $extensionConfig = array_merge_recursive(...($builder->getExtensionConfig('audit') ?? []));

        if (true === ($extensionConfig['set_doctrine_mappings'] ?? true)) {
            $this->validate($extensionConfig);

            $mappings = $this->getOrmMappings($builder, $extensionConfig['default_entity_manager'] ?? 'default');

            $this->addDoctrineConfig($container, $extensionConfig['audit_entity_manager'] ?? 'audit', 'Audit', self::MAPPINGS, $mappings);
            $this->addDoctrineConfig($container, $extensionConfig['default_entity_manager'] ?? 'default', 'Audit', self::MAPPINGS);
        }

        if ($builder->hasExtension('doctrine_migrations')) {
            $container->extension('doctrine_migrations', [
                'migrations_paths' => [
                    'Whitedigital\Audit\Migrations' => '%kernel.project_dir%/vendor/whitedigital-eu/audit-service/migrations',
                ],
            ]);
        }

        $this->configureApiPlatformExtension($container, $extensionConfig);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('audit_entity_manager')->defaultValue('audit')->end()
                ->scalarNode('default_entity_manager')->defaultValue('default')->end()
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
                ->scalarNode('audit_type_interface_namespace')->defaultValue('App\\Audit')->end()
                ->scalarNode('audit_type_interface_class_name')->defaultValue('AuditType')->end()
                ->scalarNode('custom_api_resource_path')->defaultNull()->end()
            ->end();
    }

    public static function getConstants(string $class): array
    {
        try {
            return (new ReflectionClass($class))->getConstants(ReflectionClassConstant::IS_PUBLIC);
        } catch (ReflectionException) {
            return [];
        }
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AuditServiceCompilerPass());
    }

    private function validate(array $config): void
    {
        $auditEntityManager = $config['audit_entity_manager'] ?? 'audit';
        $defaultEntityManager = $config['default_entity_manager'] ?? 'default';

        if ($auditEntityManager === $defaultEntityManager) {
            throw new InvalidConfigurationException('WhiteDigital\Audit: "audit_entity_manager" and "default_entity_manager" names must be different');
        }
    }

    private function configureApiPlatformExtension(ContainerConfigurator $container, array $extensionConfig): void
    {
        if (!array_key_exists('custom_api_resource_path', $extensionConfig)) {
            $this->addApiPlatformPaths($container, [self::API_RESOURCE_PATH]);
        } elseif (!empty($extensionConfig['custom_api_resource_path'])) {
            $this->addApiPlatformPaths($container, [$extensionConfig['custom_api_resource_path']]);
        }
    }
}
