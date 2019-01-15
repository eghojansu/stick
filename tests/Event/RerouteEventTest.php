<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use Fal\Stick\Event\RerouteEvent;
use PHPUnit\Framework\TestCase;

class RerouteEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new RerouteEvent(new Core(), 'foo', true);
    }

    public function testGetCore()
    {
        $this->assertInstanceOf('Fal\\Stick\\Core', $this->event->getCore());
    }

    public function testGetUrl()
    {
        $this->assertEquals('foo', $this->event->getUrl());
    }

    public function testIsPermanent()
    {
        $this->assertTrue($this->event->isPermanent());
    }

    public function testRedirect()
    {
        $this->expectOutputString('foo out');
        $this->event->getCore()->route('GET /foo', function () {
            return 'foo out';
        });
        $this->event->redirect('/foo');
    }
}
