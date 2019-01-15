<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Security\Event;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\Event\LoginEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fixture\SimpleUser;
use PHPUnit\Framework\TestCase;

class LoginEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new LoginEvent(new Auth(new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder()), new SimpleUser(1, 'foo', 'bar'));
    }

    public function testGetUser()
    {
        $this->assertEquals('foo', $this->event->getUser()->getUsername());
    }
}
