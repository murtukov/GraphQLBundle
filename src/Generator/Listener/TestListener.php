<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Listener;

use Symfony\Component\EventDispatcher\Event;

class TestListener
{
    public function __invoke(Event $event)
    {
        $x = $event;
    }
}
