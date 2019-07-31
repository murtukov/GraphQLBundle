<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use ArrayObject;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class Hydrator
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class Hydrator implements HydratorInterface
{

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;


    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function process(ArgumentInterface $args, ResolveInfo $info)
    {
        $requestedField = $info->parentType->getField($info->fieldName);
        $hydrated = new Hydrated();

        foreach ($args->getArrayCopy() as $key => $value) {
            $argType = $requestedField->getArg($key)->getType();
            $unwrappedType = Type::getNamedType($argType);

            if (Type::isBuiltInType($unwrappedType) || !isset($unwrappedType->config['hydration'])) {
                continue;
            }

            if ($argType instanceof ListOfType) {
                $collection = [];
                foreach ($value as $item) {
                    $collection[] = $this->hydrate($unwrappedType, $item);
                }
                $value = $collection;
            } else {
                $value = $this->hydrate($unwrappedType, $value);
            }

            $hydrated[$key] = $value;
        }

        return $hydrated;
    }

    /**
     * @param InputObjectType $type
     * @param array           $values
     *
     * @return mixed
     * @throws \Exception
     */
    public function hydrate(InputObjectType $type, array $values)
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

    public function mapName($fieldName): string
    {
        return $fieldName;
    }
}
