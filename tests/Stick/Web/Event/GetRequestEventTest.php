<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:25
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Event\GetRequestEvent;

class GetRequestEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetRequestEvent(new Kernel());
    }

    public function testGetKernel()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\KernelInterface', $this->event->getKernel());
    }

    public function testGetRequest()
    {
        $this->assertNull($this->event->getRequest());
    }

    public function testSetRequest()
    {
        $this->assertEquals(new Request(), $this->event->setRequest(new Request())->getRequest());
    }
}
