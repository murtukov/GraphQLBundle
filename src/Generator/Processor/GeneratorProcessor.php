<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Processor;

interface GeneratorProcessor
{
    public function process(array $config): array;
}
