<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:39
 */

namespace Fal\Stick\Test\Security\Event;

use Fal\Stick\Core;
use Fixture\SimpleUser;
use Fal\Stick\Security\Auth;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\Event\LoadUserEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

class LoadUserEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new LoadUserEvent(new Auth(new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder()));
    }

    public function testGetUser()
    {
        $this->assertNull($this->event->getUser());
    }

    public function testSetUser()
    {
        $user = new SimpleUser(1, 'foo', 'bar');

        $this->assertEquals($user, $this->event->setUser($user)->getUser());
    }
}
