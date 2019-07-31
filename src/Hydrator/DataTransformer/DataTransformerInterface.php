<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\DataTransformer;

interface DataTransformerInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function transform($value);
}
