<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Validator\Mapping\MemberMetadata;

/**
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class PropertyMetadata extends MemberMetadata
{
    public function __construct(string $name)
    {
        parent::__construct('anonymous', $name, $name);
    }

    /**
     * @param  $object
     * @return ReflectionMethod|ReflectionProperty
     * @throws ReflectionException
     */
    protected function newReflectionMember($object)
    {
        $member = new ReflectionProperty($object, $this->getName());
        $member->setAccessible(true);

        return $member;
    }


    public function getPropertyValue($object)
    {
        return $this->getReflectionMember($object)->getValue($object);
    }
}
