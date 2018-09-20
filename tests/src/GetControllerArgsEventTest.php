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

use Fal\Stick\GetControllerArgsEvent;
use PHPUnit\Framework\TestCase;

class GetControllerArgsEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new GetControllerArgsEvent('foo', array('bar'));
    }

    public function testGetController()
    {
        $this->assertEquals('foo', $this->event->getController());
    }

    public function testSetController()
    {
        $this->assertEquals('bar', $this->event->setController('bar')->getController());
    }

    public function testGetArgs()
    {
        $this->assertEquals(array('bar'), $this->event->getArgs());
    }

    public function testSetArgs()
    {
        $this->assertEquals(array('foo'), $this->event->setArgs(array('foo'))->getArgs());
    }
}
