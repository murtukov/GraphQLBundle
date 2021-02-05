<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use ReflectionMethod;
use function is_array;
use function is_callable;
use function method_exists;

class ArgumentsMapper
{
    private array $knownNames = ['args', 'info', 'context', 'value'];

    private array $typesMap = [
        ArgumentInterface::class => 'args',
        ResolveInfo::class => 'info'
    ];

    /**
     * @param string $type FQCN
     * @param string $name Name of the generated argument
     */
    public function addType(string $type, string $name)
    {
        $this->typesMap[$type] = $name;
    }

    /**
     * @param string|array $link
     *
     * @throws Exception
     */
    public function print($link, string $prefix = '$'): array
    {
        $reflection = $this->resolveMethodReflection($link);
        $parameters = $reflection->getParameters();

        $result = [];

        foreach ($parameters as $param) {
            $type = $param->getType();
            $name = $param->getName();

            if (null === $type) {
                // by name
                if (in_array($name, $this->knownNames)) {
                    $result[] = $name;
                } else {
                    throw new Exception(sprintf('Cannot guess argument "%s"', $name));
                }
            } elseif ($type->isBuiltin()) {
                // by name
                if (isset($this->knownNames[$name])) {
                    // check type
                    if (array_search($name, $this->typesMap) === (string) $type) {
                        $result[] = $name;
                    } else {
                        throw new Exception(sprintf('Cannot guess argument "%s"', $name));
                    }
                }
            } else {
                if (isset($this->typesMap[(string) $type])) {
                    $result[] = $this->typesMap[(string) $type];
                } else {
                    throw new Exception(sprintf('Cannot guess argument "%s"', $name));
                }
            }
        }

        array_walk($result, fn ($item) => '$'.$item);

        return $result;
    }

    /**
     * @param mixed $link
     *
     * @throws Exception
     */
    private function resolveMethodReflection($link): ReflectionMethod
    {
        if (is_string($link)) {
            [$class, $method] = explode('::', $link);
            // App\Resolver\MyClass::myMethod
            if (method_exists($class, $method)) {
                return new ReflectionMethod($link);
            }
            // App\Resolver\MyClass::__invoke
            if (method_exists($link, '__invoke')) {
                return new ReflectionMethod($link, '__invoke');
            }
        } elseif (is_array($link) AND is_callable($link)) {
            // ['MyClass', 'myMethod']
            return new ReflectionMethod(...$link);
        }

        throw new Exception("Method doesn't exist or malformed");
    }
}
