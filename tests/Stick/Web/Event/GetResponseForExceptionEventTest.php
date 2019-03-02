<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:29
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Event\GetResponseForExceptionEvent;

class GetResponseForExceptionEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new GetResponseForExceptionEvent(new Kernel(), new Request(), 1, new \LogicException('foo'));
    }

    public function testGetException()
    {
        $this->assertEquals('foo', $this->event->getException()->getMessage());
    }
}
