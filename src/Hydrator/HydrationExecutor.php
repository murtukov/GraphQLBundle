<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use Exception;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class Hydrator
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class HydrationExecutor
{
    use HydratorResolverTrait;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * HydrationExecutor constructor.
     *
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param ArgumentInterface $args
     * @param ResolveInfo       $info
     *
     * @return HydratedSet
     * @throws Exception
     */
    public function process(ArgumentInterface $args, ResolveInfo $info)
    {
        $requestedField = $info->parentType->getField($info->fieldName);
        $hydrated = new HydratedSet();

        foreach ($args->getArrayCopy() as $key => $value) {
            $argType = $requestedField->getArg($key)->getType(); /** @var Type $argType */
            $unwrappedType = Type::getNamedType($argType);

            // If a primitive type or no hydration is configured
            if (Type::isBuiltInType($unwrappedType) || !isset($unwrappedType->config['hydration'])) {
                continue;
            }

            $hydrator = $this->getHydratorForType($unwrappedType);

            if ($argType instanceof ListOfType) {
                $collection = [];
                foreach ($value as $item) {
                    $collection[] = $hydrator->hydrate($unwrappedType, $item);
                }
                $value = $collection;
            } else {
                $value = $hydrator->hydrate($unwrappedType, $value);
            }

            $hydrated[$key] = $value;
        }

        return $hydrated;
    }
}
