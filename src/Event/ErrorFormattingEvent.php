<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Error\Error;
use Symfony\Contracts\EventDispatcher\Event;

final class ErrorFormattingEvent extends Event
{
    /** @var Error */
    private $error;

    /** @var \ArrayObject */
    private $formattedError;

    public function __construct(Error $error, array $formattedError)
    {
        $this->error = $error;
        $this->formattedError = new \ArrayObject($formattedError);
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * @return \ArrayObject
     */
    public function getFormattedError(): \ArrayObject
    {
        return $this->formattedError;
    }
}
