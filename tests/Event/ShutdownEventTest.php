<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 11, 2019 14:25
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Event\ShutdownEvent;

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
