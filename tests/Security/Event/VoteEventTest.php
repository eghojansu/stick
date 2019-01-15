<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:48
 */

namespace Fal\Stick\Test\Security\Event;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\Event\VoteEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

class VoteEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new VoteEvent(new Auth(new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder()), 'foo', 'bar');
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
        $this->assertEquals('bar', $this->event->getAttributes());
    }
}
