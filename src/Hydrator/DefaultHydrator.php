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
        $className = $type->config['hydration']['target'] ?? ArrayObject::class;
        $object = new $className;

        foreach ($values as $fieldName => $value) {
            $propertyName = $this->mapName($fieldName);

            // Create property dynamically to allow the
            // property accessor to write a value.
            if ($object instanceof ArrayObject) {
                $object->$propertyName = '';
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

                $this->propertyAccessor->setValue($object, $propertyName, $this->transformValue($fieldName, $value));
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
}
