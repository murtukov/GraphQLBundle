<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;

interface ExecutorInterface
{
    /**
     * @param PromiseAdapter $promiseAdapter
     * @param Schema         $schema
     * @param string         $requestString
     * @param mixed          $rootValue
     * @param array|null     $contextValue
     * @param array|null     $variableValues
     * @param string|null    $operationName
     * @param callable|null  $fieldResolver
     * @param array|null     $validationRules
     *
     * @return ExecutionResult
     */
    public function execute(
        PromiseAdapter $promiseAdapter,
        Schema $schema,
        string $requestString,
        $rootValue = null,
        $contextValue = null,
        $variableValues = null,
        $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): ExecutionResult;
}
