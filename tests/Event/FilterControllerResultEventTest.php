<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 19:44
 */

namespace Fal\Stick\Test\Event;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Event\FilterControllerResultEvent;

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
