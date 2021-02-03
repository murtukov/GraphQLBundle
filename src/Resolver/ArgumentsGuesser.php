<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use Exception;
use ReflectionMethod;
use function is_array;
use function is_callable;
use function method_exists;

class ArgumentsGuesser
{
    /**
     * @param string|array $link Link to method to guess arguments. Allowed formats:
     *                              - `MyClass::myMethod`
     *                              - `MyClass` (if the __invoke method defined)
     *                              - ['MyClass', 'myMethod']
     *
     * @return array
     *
     * @throws Exception
     */
    public function guess($link): array
    {
        $reflection = $this->resolveMethodReflection($link);
        $params = $reflection->getParameters();



        return [];
    }

    /**
     * @param mixed $link
     *
     * @throws Exception
     */
    private function resolveMethodReflection($link): ReflectionMethod
    {
        if (is_string($link)) {
            // App\Resolver\MyClass::myMethod
            if (is_callable($link)) {
                return new ReflectionMethod($link);
            }
            // App\Resolver\MyClass::__invoke
            if (method_exists($link, '__invoke')) {
                return new ReflectionMethod("$link::__invoke");
            }
        } elseif (is_array($link) AND is_callable($link)) {
            // ['MyClass', 'myMethod']
            return new ReflectionMethod(join('::', $link));
        }

        throw new Exception('Unresolveable');
    }
}
