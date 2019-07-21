<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Executor\ExecutionResult;
use Symfony\Contracts\EventDispatcher\Event;

final class ExecutorResultEvent extends Event
{
    /** @var ExecutionResult */
    private $result;

    public function __construct(ExecutionResult $result)
    {
        $this->result = $result;
    }

    /**
     * @return ExecutionResult
     */
    public function getResult(): ExecutionResult
    {
        return $this->result;
    }
}
