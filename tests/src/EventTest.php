<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private $event;

    public function setUp()
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
