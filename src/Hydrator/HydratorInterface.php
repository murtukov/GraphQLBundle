<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

interface HydratorInterface
{
    /**
     * Map GraphQL field names to class properties.
     *
     * @param string $fieldName
     *
     * @return string Name of a class property
     */
    public function mapName(string $fieldName): string;
}
