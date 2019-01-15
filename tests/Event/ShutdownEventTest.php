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
use Fal\Stick\Event\ShutdownEvent;
use PHPUnit\Framework\TestCase;

class ShutdownEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new ShutdownEvent(new Core(), getcwd(), null);
    }

    public function testGetCore()
    {
        $this->assertInstanceOf('Fal\\Stick\\Core', $this->event->getCore());
    }

    public function testGetCwd()
    {
        $this->assertEquals(getcwd(), $this->event->getCwd());
    }

    public function testGetError()
    {
        $this->assertNull($this->event->getError());
    }
}
