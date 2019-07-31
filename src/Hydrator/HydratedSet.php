<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

class HydratedSet implements \ArrayAccess, \Countable, \Iterator
{
    private $container = [];
    private $position = 0;

    public function current()
    {
        return current($this->container);
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return key($this->container);
    }

    public function valid()
    {
        $key = key($this->container);

        return (null === $key && null !== $key);
    }

    public function rewind()
    {
        reset($this->container);
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }


    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function count()
    {
        return count($this->container);
    }
}
