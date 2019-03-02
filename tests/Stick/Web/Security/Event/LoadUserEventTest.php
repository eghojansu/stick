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
use Fal\Stick\Web\Security\Event\LoadUserEvent;
use Fixture\SimpleUser;

class LoadUserEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new LoadUserEvent($this->prepare()->auth);
    }

    public function testHasUser()
    {
        $this->assertFalse($this->event->hasUser());
    }

    public function testGetUser()
    {
        $this->assertNull($this->event->getUser());
    }

    public function testSetUser()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Security\\UserInterface', $this->event->setUser(new SimpleUser('1', 'foo', 'bar'))->getUser());
    }
}