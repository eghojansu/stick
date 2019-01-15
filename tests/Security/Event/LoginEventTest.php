<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:41
 */

namespace Fal\Stick\Test\Security\Event;

use Fal\Stick\Core;
use Fixture\SimpleUser;
use Fal\Stick\Security\Auth;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\Event\LoginEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

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
