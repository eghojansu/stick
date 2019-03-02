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
use Fal\Stick\Web\Security\Event\AuthEvent;

class AuthEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new AuthEvent($this->prepare()->auth);
    }

    public function testGetAuth()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Security\\Auth', $this->event->getAuth());
    }
}
