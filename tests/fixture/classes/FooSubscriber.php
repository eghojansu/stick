<?php

namespace Fixture;

use Fal\Stick\EventSubscriberInterface;

class FooSubscriber implements EventSubscriberInterface
{
    public static function getEvents(): array
    {
        return array('foo' => 'Fixture\\FooSubscriber->bar');
    }

    public function bar()
    {
        return 'subscribe foo';
    }
}
