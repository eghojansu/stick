<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 19:54
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Event\GetResponseForErrorEvent;

class GetResponseForErrorEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetResponseForErrorEvent(new Core(), array('foo'));
    }

    public function testGetError()
    {
        $this->assertEquals(array('foo'), $this->event->getError());
    }
}
