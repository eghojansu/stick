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

namespace Fal\Stick\Test;

use Fal\Stick\GetResponseForControllerEvent;
use PHPUnit\Framework\TestCase;

class GetResponseForControllerEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new GetResponseForControllerEvent('foo', array());
    }

    public function testGetResult()
    {
        $this->assertEquals('foo', $this->event->getResult());
    }

    public function testSetResult()
    {
        $this->assertEquals('bar', $this->event->setResult('bar')->getResult());
    }
}
