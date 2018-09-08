<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Security;

use Fal\Stick\Security\AuthEvent;
use Fal\Stick\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

class AuthEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new AuthEvent();
    }

    public function testGetUser()
    {
        $this->assertNull($this->event->getUser());
    }

    public function testSetUser()
    {
        $user = new SimpleUser('1', 'foo', 'bar');

        $this->assertSame($user, $this->event->setUser($user)->getUser());
    }
}
