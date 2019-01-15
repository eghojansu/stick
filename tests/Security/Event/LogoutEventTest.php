<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:45
 */

namespace Fal\Stick\Test\Security\Event;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\Event\LogoutEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

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
