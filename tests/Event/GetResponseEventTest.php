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
use Fal\Stick\Event\GetResponseEvent;
use PHPUnit\Framework\TestCase;

class GetResponseEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetResponseEvent(new Core());
    }

    public function testGetCore()
    {
        $this->assertInstanceOf('Fal\\Stick\\Core', $this->event->getCore());
    }

    public function testGetResponse()
    {
        $this->assertEquals(['code' => 200, 'headers' => null, 'content' => null], $this->event->getResponse());
    }

    public function testSetResponse()
    {
        $this->assertEquals(['code' => 500, 'headers' => null, 'content' => 'foo'], $this->event->setResponse('foo', 500)->getResponse());
    }
}
