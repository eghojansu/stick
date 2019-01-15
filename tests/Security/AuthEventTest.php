<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:36
 */

namespace Fal\Stick\Test\Security;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\AuthEvent;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

class AuthEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new AuthEvent(new Auth(new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder()));
    }

    public function testGetAuth()
    {
        $this->assertInstanceOf('Fal\\Stick\\Security\\Auth', $this->event->getAuth());
    }
}
