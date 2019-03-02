<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\EventDispatcher;

use Fal\Stick\Container\Container;
use Fal\Stick\EventDispatcher\Event;
use Fal\Stick\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private $dispatcher;

    public function setup()
    {
        $this->dispatcher = new EventDispatcher(new Container(), array(
            'foo' => function ($event) {
                $event->stopPropagation();
            },
        ));
    }

    public function testOn()
    {
        $this->dispatcher->on('bar', function ($event) {
            $event->stopPropagation();
        });
        $this->assertCount(2, $this->dispatcher->getEvents());

        $event = new Event();
        $this->dispatcher->dispatch('bar', $event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertCount(2, $this->dispatcher->getEvents());

        // second call, expecting same result
        $event = new Event();
        $this->dispatcher->dispatch('bar', $event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertCount(2, $this->dispatcher->getEvents());
    }

    public function testOne()
    {
        $this->dispatcher->one('bar', function ($event) {
            $event->stopPropagation();
        });
        $this->assertCount(2, $this->dispatcher->getEvents());

        $event = new Event();
        $this->dispatcher->dispatch('bar', $event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertCount(1, $this->dispatcher->getEvents());

        // second call, expecting different result
        $event = new Event();
        $this->dispatcher->dispatch('bar', $event);

        $this->assertFalse($event->isPropagationStopped());
        $this->assertCount(1, $this->dispatcher->getEvents());
    }

    public function testOff()
    {
        // remove previously set event
        $this->dispatcher->off('foo');
        $this->assertCount(0, $this->dispatcher->getEvents());

        $event = new Event();
        $this->dispatcher->dispatch('foo', $event);

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testDispatch()
    {
        $event = new Event();
        $this->dispatcher->dispatch('foo', $event, true);
        $this->assertTrue($event->isPropagationStopped());

        // second call
        $event = new Event();
        $this->dispatcher->dispatch('foo', $event);
        $this->assertFalse($event->isPropagationStopped());

        // register once event
        $this->dispatcher->one('foo', function ($event) {
            $event->stopPropagation();
        });
        $event = new Event();
        $this->dispatcher->dispatch('foo', $event, true);
        $this->assertTrue($event->isPropagationStopped());

        // invalid callback string
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Handler is not callable: "foo".');
        $this->dispatcher->one('foo', 'invalid_callback');
        $this->dispatcher->dispatch('foo', new Event());
    }

    public function testGetEvents()
    {
        $this->assertCount(1, $this->dispatcher->getEvents());
    }
}
