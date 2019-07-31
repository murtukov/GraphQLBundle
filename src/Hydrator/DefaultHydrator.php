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
     * @param InputObjectType $type
     * @param array           $values
     *
     * @return object
     * @throws Exception
     */
    final public function hydrate(InputObjectType $type, array $values): object
    {
        $className = $type->config['hydration']['class'] ?? ArrayObject::class;

        $object = new $className;

        foreach ($values as $fieldName => $value) {
            if ($this->propertyAccessor->isWritable($object, $fieldName)) {

                $childType = $type->getField($fieldName)->getType();

                if ($childType instanceof InputObjectType) {
                    $value = $this->hydrate($childType, $value);
                } elseif ($childType instanceof ListOfType && Type::getNamedType($childType) instanceof InputObjectType) {
                    $collection = [];

                    foreach ($value as $item) {
                        $collection[] = $this->hydrate(Type::getNamedType($childType), $item);
                    }

                    $value = $collection;
                }

                $this->propertyAccessor->setValue($object, $this->mapName($fieldName), $value);
            }
        }

        return $object;
    }

    /**
     * Map GraphQL field names to class properties.
     *
     * @param string $fieldName
     *
     * @return string Name of a class property
     */
    public function mapName(string $fieldName): string
    {
        return $fieldName;
    }

    /**
     * Transforms a single value.
     *
     * @param string $fieldName
     * @param        $value
     *
     * @return mixed
     */
    public function transformValue(string $fieldName, $value)
    {
        return $value;
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
}
