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
use Fal\Stick\Web\Security\Event\LogoutEvent;
use Fixture\SimpleUser;

class LogoutEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new LogoutEvent($this->prepare()->auth, new SimpleUser('1', 'foo', 'bar'));
    }

    public function testGetUser()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Security\\UserInterface', $this->event->getUser());
    }
}
