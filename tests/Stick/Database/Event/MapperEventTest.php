<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 06, 2019 10:09
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database\Event;

use Fal\Stick\Database\Event\MapperEvent;
use Fal\Stick\TestSuite\TestCase;

class MapperEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $this->event = new MapperEvent($this->mapper('user'));
    }

    public function testGetMapper()
    {
        $this->assertInstanceOf('Fal\\Stick\\Database\\Mapper', $this->event->getMapper());
    }
}
