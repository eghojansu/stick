<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:15
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Event;

use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Event\FilterControllerArgumentsEvent;

class FilterControllerArgumentsEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new FilterControllerArgumentsEvent(new Kernel(), new Request(), 1, 'trim', array('foo'));
    }

    public function testGetController()
    {
        $this->assertEquals('trim', $this->event->getController());
    }

    public function testGetArguments()
    {
        $this->assertEquals(array('foo'), $this->event->getArguments());
    }

    public function testSetArguments()
    {
        $this->assertEquals(array('bar'), $this->event->setArguments(array('bar'))->getArguments());
    }
}
