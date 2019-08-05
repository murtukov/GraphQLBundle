<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use ArrayObject;
use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class DefaultHydrator
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class DefaultHydrator implements HydratorInterface
{
    use HydratorResolverTrait;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var array An assotiative array of transformer closures
     */
    private $transformers;

    /**
     * @var array
     */
    private $namesMapping;

    /**
     * @param InputObjectType $type
     * @param array           $values
     *
     * @return object
     * @throws Exception
     */
    final public function hydrate(InputObjectType $type, array $values): object
    {
        $className = $type->config['hydration']['target'] ?? ArrayObject::class;
        $object = new $className;

        foreach ($values as $fieldName => $value) {
            // Use a mapped name if set
            $propertyName = $this->namesMapping[$fieldName] ?? $fieldName;

            if ($object instanceof ArrayObject) {
                // Create property dynamically to allow the
                // property accessor to write a value.
                $object->$propertyName = null;
            }

            if ($this->propertyAccessor->isWritable($object, $propertyName)) {
                $childType     = $type->getField($fieldName)->getType();
                $unwrappedType = Type::getNamedType($childType);
                $isCollection  = $childType instanceof ListOfType;

                // Collection of input objects
                if ($isCollection && $unwrappedType instanceof InputObjectType) {
                    $hydrator = $this->getHydratorForType($unwrappedType);
                    $collection = [];

                    foreach ($value as $item) {
                        $collection[] = $hydrator->hydrate($unwrappedType, $item);
                    }

                    $value = $collection;
                }
                // Single input object
                elseif ($unwrappedType instanceof InputObjectType) {
                    $hydrator = $this->getHydratorForType($unwrappedType);
                    $value = $hydrator->hydrate($unwrappedType, $value);
                }

                $this->propertyAccessor->setValue(
                    $object,
                    $propertyName,
                    // Use a transformer if set
                    isset($this->transformers[$fieldName]) ? $this->transformers[$fieldName]($value) : $value
                );
            }
        }

        return $object;
    }

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     *
     * @return void
     */
    final function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    final function setTypeName(string $name): void
    {
        $this->typeName = $name;
    }

    final public function setTransformers(): void
    {
        $this->transformers = $this->mapTransformers();
    }

    final public function setNamesMapping(): void
    {
        $this->namesMapping = $this->mapNames();
    }

    /**
     * Map GraphQL field names to class properties.
     *
     * @param string $fieldName
     *
     * @return string Name of a class property
     */
    public function set(string $fieldName): string
    {
        return $fieldName;
    }

    public function mapTransformers(): array
    {
        return [];
    }

    public function mapNames(): array
    {
        return [];
    }
}
