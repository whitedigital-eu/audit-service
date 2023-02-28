<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Maker;

use Exception;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function array_merge;
use function array_unique;
use function dirname;
use function interface_exists;
use function ksort;
use function unlink;

class MakeAuditTypeInterface extends AbstractMaker
{
    public function __construct(private readonly ParameterBagInterface $bag)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:audit-types';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates interface based on allowed audit types for easier audit type usage';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * @throws Exception
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (null === ($namespace = $this->bag->get('whitedigital.audit.audit_type_interface_namespace'))) {
            throw new InvalidConfigurationException('In order to generate audit type inteface, you need to set namespace in audit package configuration');
        }

        $namespace = str_replace($generator->getRootNamespace() . '\\', '', $namespace);
        $interface = $generator->createClassNameDetails($this->bag->get('whitedigital.audit.audit_type_interface_class_name'), $namespace);

        $constants = $this->bag->get('whitedigital.audit.additional_audit_constants');
        if (interface_exists($interface->getFullName())) {
            try {
                $classConstants = ($class = new ReflectionClass(objectOrClass: $interface->getFullName()))->getConstants(filter: ReflectionClassConstant::IS_PUBLIC);
                unlink($class->getFileName());
            } catch (ReflectionException) {
                $classConstants = [];
            }
            $constants = array_unique(array_merge($constants, $classConstants));
        }
        ksort($constants);

        $generator->generateClass(
            $interface->getFullName(),
            dirname(__DIR__, 2) . '/skeleton/AuditTypeInterface.tpl.php',
            [
                'constants' => $constants,
            ],
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }
}
