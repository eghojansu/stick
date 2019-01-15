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
use Fal\Stick\Event\GetResponseForErrorEvent;
use PHPUnit\Framework\TestCase;

class GetResponseForErrorEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetResponseForErrorEvent(new Core(), ['foo']);
    }

    public function testGetError()
    {
        $this->assertEquals(['foo'], $this->event->getError());
    }
}
