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

use Fal\Stick\ReroutingEvent;
use PHPUnit\Framework\TestCase;

class ReroutingEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new ReroutingEvent('foo', false);
    }

    public function testGetUrl()
    {
        $this->assertEquals('foo', $this->event->getUrl());
    }

    public function testIsPermanent()
    {
        $this->assertFalse($this->event->isPermanent());
    }
}
