<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use GraphQL\Type\Definition\Type;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Trait HydratorResolverTrait
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
trait HydratorResolverTrait
{
    /**
     * @var ServiceLocator
     */
    private $hydratorLocator;

    /**
     * @param Type $type
     *
     * @return HydratorInterface
     */
    final private function getHydratorForType(Type $type): HydratorInterface
    {
        if ($serviceId = $type->config['hydration']['hydrator']) {
            if ($this->hydratorLocator->has($serviceId)) {
                $hydrator = $this->hydratorLocator->get($serviceId); /** @var HydratorInterface $hydrator */
            } else {
                throw new RuntimeException("Unable to resolve the hydrator '$serviceId'. Service not found.");
            }
        } else {
            $hydrator = $this->hydratorLocator->get(DefaultHydrator::class);
        }

        $hydrator->setPropertyAccessor($this->propertyAccessor);
        $hydrator->setHydratorLocator($this->hydratorLocator);
        $hydrator->setTypeName($type->name);

        return $hydrator;
    }

    final public function setHydratorLocator(ServiceLocator $hydratorLocator): void
    {
        $this->hydratorLocator = $hydratorLocator;
    }
}
