<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:12
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Event\KernelEvent;
use PHPUnit\Framework\TestCase;

class KernelEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new KernelEvent(new Kernel(), new Request(), 1);
    }

    public function testGetKernel()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\KernelInterface', $this->event->getKernel());
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Request', $this->event->getRequest());
    }

    public function testGetRequestType()
    {
        $this->assertEquals(1, $this->event->getRequestType());
    }

    public function testIsMasterRequest()
    {
        $this->assertTrue($this->event->isMasterRequest());
    }
}
