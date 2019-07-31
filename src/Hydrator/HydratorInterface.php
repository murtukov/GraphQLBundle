<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use GraphQL\Type\Definition\InputObjectType;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Interface HydratorInterface
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
interface HydratorInterface
{
    /**
     * @param InputObjectType $type
     * @param array           $values
     *
     * @return object
     */
    function hydrate(InputObjectType $type, array $values): object;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     *
     * @return mixed
     */
    function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void;

    /**
     * Set name of the GraphQL type for which an object is being hydrated.
     *
     * @param string $name
     */
    function setTypeName(string $name): void;

    /**
     * @param ServiceLocator $hydratorLocator
     */
    function setHydratorLocator(ServiceLocator $hydratorLocator): void;

    function setTransformers(): void;

    function setNamesMapping(): void;

    function mapTransformers(): array;

    function mapNames(): array;
}

