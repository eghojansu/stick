<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\GetResponseEvent;
use PHPUnit\Framework\TestCase;

class GetResponseEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new GetResponseEvent();
    }

    public function testGetCode()
    {
        $this->assertEquals(200, $this->event->getCode());
    }

    public function testSetCode()
    {
        $this->assertEquals(404, $this->event->setCode(404)->getCode());
    }

    public function testGetHeaders()
    {
        $this->assertEquals(array(), $this->event->getHeaders());
    }

    public function testSetHeaders()
    {
        $this->assertEquals(array('foo'), $this->event->setHeaders(array('foo'))->getHeaders());
    }

    public function testGetKbps()
    {
        $this->assertEquals(0, $this->event->getKbps());
    }

    public function testSetKbps()
    {
        $this->assertEquals(1, $this->event->setKbps(1)->getKbps());
    }

    public function testGetResponse()
    {
        $this->assertEquals('', $this->event->getResponse());
    }

    public function testSetResponse()
    {
        $this->assertEquals('foo', $this->event->setResponse('foo')->getResponse());
        $this->assertTrue($this->event->isPropagationStopped());
    }
}
