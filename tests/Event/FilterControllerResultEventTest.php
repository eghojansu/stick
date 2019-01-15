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
use Fal\Stick\Event\FilterControllerResultEvent;
use PHPUnit\Framework\TestCase;

class FilterControllerResultEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new FilterControllerResultEvent(new Core(), 'foo');
    }

    public function testGetResult()
    {
        $this->assertEquals('foo', $this->event->getResult());
    }

    public function testSetResult()
    {
        $this->assertEquals('bar', $this->event->setResult('bar')->getResult());
    }
}
