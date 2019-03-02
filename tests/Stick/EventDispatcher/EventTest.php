<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 20:01
 */

namespace Fal\Stick\Test\EventDispatcher;

use Fal\Stick\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new Event();
    }

    public function testIsPropagationStopped()
    {
        $this->assertFalse($this->event->isPropagationStopped());
    }

    public function testStopPropagation()
    {
        $this->assertTrue($this->event->stopPropagation()->isPropagationStopped());
    }
}
