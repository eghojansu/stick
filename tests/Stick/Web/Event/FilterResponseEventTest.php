<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:20
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Response;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Event\FilterResponseEvent;

class FilterResponseEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new FilterResponseEvent(new Kernel(), new Request(), 1, new Response());
    }

    public function testConstruct()
    {
        $this->assertTrue($this->event->hasResponse());
    }
}
