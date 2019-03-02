<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:27
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Response;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Event\GetResponseEvent;

class GetResponseEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetResponseEvent(new Kernel(), new Request(), 1);
    }

    public function testHasResponse()
    {
        $this->assertFalse($this->event->hasResponse());
    }

    public function testGetResponse()
    {
        $this->assertNull($this->event->getResponse());
    }

    public function testSetResponse()
    {
        $this->assertEquals(new Response(), $this->event->setResponse(new Response())->getResponse());
    }
}
