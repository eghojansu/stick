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
use Fal\Stick\Security\Event\LogoutEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use PHPUnit\Framework\TestCase;

class LogoutEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new LogoutEvent(new Auth(new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder()));
    }

    public function testGetAuth()
    {
        $this->assertInstanceOf('Fal\\Stick\\Security\\Auth', $this->event->getAuth());
    }
}
