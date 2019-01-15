<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 19:51
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Event\GetResponseEvent;

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
        $this->assertEquals(array('code' => 200, 'headers' => null, 'content' => null), $this->event->getResponse());
    }

    public function testSetResponse()
    {
        $this->assertEquals(array('code' => 500, 'headers' => null, 'content' => 'foo'), $this->event->setResponse('foo', 500)->getResponse());
    }
}
