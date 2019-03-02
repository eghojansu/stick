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

namespace Fal\Stick\Test\Web\Security\Event;

use Fal\Stick\TestSuite\TestCase;
use Fal\Stick\Web\Security\Event\VoteEvent;

class VoteEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new VoteEvent($this->prepare()->auth, true, 'foo');
    }

    public function testIsGranted()
    {
        $this->assertTrue($this->event->isGranted());
    }

    public function testGetData()
    {
        $this->assertEquals('foo', $this->event->getData());
    }

    public function testGetAttributes()
    {
        $this->assertNull($this->event->getAttributes());
    }
}
