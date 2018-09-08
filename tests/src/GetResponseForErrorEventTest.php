<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\GetResponseForErrorEvent;
use PHPUnit\Framework\TestCase;

class GetResponseForErrorEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new GetResponseForErrorEvent(404, 'Not Found');
    }

    public function testGetStatus()
    {
        $this->assertEquals('Not Found', $this->event->getStatus());
    }

    public function testGetTrace()
    {
        $this->assertNull($this->event->getTrace());
    }
}
