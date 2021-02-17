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

namespace Ekok\Stick\Tests;

use Ekok\Stick\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testIsUsable()
    {
        $event = new Event();

        $this->assertFalse($event->isPropagationStopped());
        $this->assertTrue($event->stopPropagation()->isPropagationStopped());
    }
}

