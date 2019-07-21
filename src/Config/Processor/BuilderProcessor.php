<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Relay\Builder\RelayConnectionFieldsBuilder;
use Overblog\GraphQLBundle\Relay\Builder\RelayEdgeFieldsBuilder;
use Overblog\GraphQLBundle\Relay\Connection\BackwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ForwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\GlobalIdFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class BuilderProcessor implements ProcessorInterface
{
    public const BUILDER_FIELD_TYPE = 'field';
    public const BUILDER_FIELDS_TYPE = 'fields';
    public const BUILDER_ARGS_TYPE = 'args';

    public const BUILDER_TYPES = [
        self::BUILDER_FIELD_TYPE,
        self::BUILDER_FIELDS_TYPE,
        self::BUILDER_ARGS_TYPE,
    ];

    /** @var MappingInterface[] */
    private static $builderClassMap = [
        self::BUILDER_ARGS_TYPE => [
            'Relay::ForwardConnection' => ForwardConnectionArgsDefinition::class,
            'Relay::BackwardConnection' => BackwardConnectionArgsDefinition::class,
            'Relay::Connection' => ConnectionArgsDefinition::class,
        ],
        self::BUILDER_FIELD_TYPE => [
            'Relay::Mutation' => MutationFieldDefinition::class,
            'Relay::GlobalId' => GlobalIdFieldDefinition::class,
            'Relay::Node' => NodeFieldDefinition::class,
            'Relay::PluralIdentifyingRoot' => PluralIdentifyingRootFieldDefinition::class,
        ],
        self::BUILDER_FIELDS_TYPE => [
            'relay-connection' => RelayConnectionFieldsBuilder::class,
            'relay-edge' => RelayEdgeFieldsBuilder::class,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public static function process(array $configs): array
    {
        $addedTypes = [];
        // map: "type name" => "provided by" for better DX, while debugging accidental type overrides in builders
        $reservedTypesMap = \array_combine(
            \array_keys($configs),
            \array_fill(0, \count($configs), 'configs')
        );

        foreach ($configs as &$config) {
            if (isset($config['config']['builders']) && \is_array($config['config']['builders'])) {
                ['fields' => $buildersFields, 'types' => $buildersTypes] = self::processFieldsBuilders(
                    $config['config']['builders'],
                    $reservedTypesMap
                );

                $config['config']['fields'] = isset($config['config']['fields'])
                    ? \array_merge($buildersFields, $config['config']['fields'])
                    : $buildersFields;

                $addedTypes = \array_merge($addedTypes, $buildersTypes);

                unset($config['config']['builders']);
            }

            if (isset($config['config']['fields']) && \is_array($config['config']['fields'])) {
                ['fields' => $buildersFields, 'types' => $buildersTypes] = self::processFieldBuilders(
                    $config['config']['fields'],
                    $reservedTypesMap
                );

                $config['config']['fields'] = $buildersFields;

                $addedTypes = \array_merge($addedTypes, $buildersTypes);
            }
        }

        return \array_merge($configs, $addedTypes);
    }

    public static function addBuilderClass($name, $type, $builderClass): void
    {
        self::checkBuilderClass($builderClass, $type);
        self::$builderClassMap[$type][$name] = $builderClass;
    }

    /**
     * @param string $builderClass
     * @param string $type
     */
    private static function checkBuilderClass($builderClass, $type): void
    {
        $interface = MappingInterface::class;

        if (!\is_string($builderClass)) {
            throw new \InvalidArgumentException(
                \sprintf('%s builder class should be string, but %s given.', \ucfirst($type), \gettype($builderClass))
            );
        }

        if (!\class_exists($builderClass)) {
            throw new \InvalidArgumentException(
                \sprintf('%s builder class "%s" not found.', \ucfirst($type), $builderClass)
            );
        }

        if (!\is_subclass_of($builderClass, $interface)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s builder class should implement "%s", but "%s" given.',
                    \ucfirst($type),
                    $interface,
                    $builderClass
                )
            );
        }
    }

    private static function processFieldBuilders(array $fields, array &$reservedTypesMap)
    {
        $newTypes = [];

        foreach ($fields as &$field) {
            $fieldBuilderName = null;

            if (isset($field['builder']) && \is_string($field['builder'])) {
                $fieldBuilderName = $field['builder'];
                unset($field['builder']);
            }

            $builderConfig = [];
            if (isset($field['builderConfig'])) {
                if (\is_array($field['builderConfig'])) {
                    $builderConfig = $field['builderConfig'];
                }
                unset($field['builderConfig']);
            }

            if (\is_string($fieldBuilderName)) {
                $mapping = self::getFieldBuilderMapping($fieldBuilderName, self::BUILDER_FIELD_TYPE, $builderConfig, $reservedTypesMap);

                $fieldMapping = $mapping['field'];
                $field = \is_array($field) ? \array_merge($fieldMapping, $field) : $fieldMapping;
                $newTypes = \array_merge($newTypes, $mapping['types']);
            }
            if (isset($field['argsBuilder'])) {
                $field = self::processFieldArgumentsBuilders($field);
            }
        }

        return [
            'fields' => $fields,
            'types' => $newTypes,
        ];
    }

    private static function processFieldsBuilders(array $builders, array &$reservedTypesMap)
    {
        $fields = [];
        $newTypes = [];

        foreach ($builders as $builder) {
            $builderName = $builder['builder'];
            $builderConfig = $builder['builderConfig'] ?? [];

            $mapping = self::getFieldBuilderMapping($builderName, self::BUILDER_FIELDS_TYPE, $builderConfig, $reservedTypesMap);

            $fields = \array_merge($fields, $mapping['fields']);
            $newTypes = \array_merge($newTypes, $mapping['types']);
        }

        return [
            'fields' => $fields,
            'types' => $newTypes,
        ];
    }

    /**
     * @param string $builderName
     * @param string $builderType
     * @param array  $builderConfig
     * @param array  $reservedTypesMap
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     */
    private static function getFieldBuilderMapping(string $builderName, string $builderType, array $builderConfig, array &$reservedTypesMap)
    {
        $builder = self::getBuilder($builderName, $builderType);
        $mapping = $builder->toMappingDefinition($builderConfig);

        $fieldMappingKey = null;

        if (self::BUILDER_FIELD_TYPE === $builderType) {
            $fieldMappingKey = 'field';
        } elseif (self::BUILDER_FIELDS_TYPE === $builderType) {
            $fieldMappingKey = 'fields';
        }

        $fieldMapping = $mapping[$fieldMappingKey] ?? $mapping;
        $typesMapping = [];

        if (isset($mapping[$fieldMappingKey], $mapping['types'])) {
            $builderClass = \get_class($builder);

            foreach ($mapping['types'] as $typeName => $typeConfig) {
                if (isset($reservedTypesMap[$typeName])) {
                    throw new InvalidConfigurationException(\sprintf(
                        'Type "%s" emitted by builder "%s" already exists. Type was provided by "%s". Builder may only emit new types. Overriding is not allowed.',
                        $typeName,
                        $builderClass,
                        $reservedTypesMap[$typeName]
                    ));
                }

                $reservedTypesMap[$typeName] = $builderClass;
                $typesMapping[$typeName] = $typeConfig;
            }
        }

        return [
            $fieldMappingKey => $fieldMapping,
            'types' => $typesMapping,
        ];
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return MappingInterface
     *
     * @throws InvalidConfigurationException if builder class not define
     */
    private static function getBuilder($name, $type)
    {
        static $builders = [];
        if (isset($builders[$type][$name])) {
            return $builders[$type][$name];
        }

        $builderClassMap = self::$builderClassMap[$type];

        if (isset($builderClassMap[$name])) {
            return $builders[$type][$name] = new $builderClassMap[$name]();
        }

        throw new InvalidConfigurationException(\sprintf('%s builder "%s" not found.', \ucfirst($type), $name));
    }

    private static function processFieldArgumentsBuilders(array $field)
    {
        $argsBuilderName = null;

        if (\is_string($field['argsBuilder'])) {
            $argsBuilderName = $field['argsBuilder'];
        } elseif (isset($field['argsBuilder']['builder']) && \is_string($field['argsBuilder']['builder'])) {
            $argsBuilderName = $field['argsBuilder']['builder'];
        }

        $builderConfig = [];
        if (isset($field['argsBuilder']['config']) && \is_array($field['argsBuilder']['config'])) {
            $builderConfig = $field['argsBuilder']['config'];
        }

        if ($argsBuilderName) {
            $args = self::getBuilder($argsBuilderName, self::BUILDER_ARGS_TYPE)->toMappingDefinition($builderConfig);
            $field['args'] = isset($field['args']) && \is_array($field['args']) ? \array_merge($args, $field['args']) : $args;
        }

        unset($field['argsBuilder']);

        return $field;
    }
}
