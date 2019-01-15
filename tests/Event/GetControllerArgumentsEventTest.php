<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 19:48
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Event\GetControllerArgumentsEvent;

class GetControllerArgumentsEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetControllerArgumentsEvent(new Core(), function () {}, array('foo'));
    }

    public function testGetCore()
    {
        $this->assertInstanceOf('Fal\\Stick\\Core', $this->event->getCore());
    }

    public function testGetController()
    {
        $this->assertEquals(function () {}, $this->event->getController());
    }

    public function testSetController()
    {
        $this->assertEquals(function () { return 'foo'; }, $this->event->setController(function () { return 'foo'; })->getController());
    }

    public function testGetArguments()
    {
        $this->assertEquals(array('foo'), $this->event->getArguments());
    }

    public function testSetArguments()
    {
        $this->assertEquals(array('foobar'), $this->event->setArguments(array('foobar'))->getArguments());
    }
}
