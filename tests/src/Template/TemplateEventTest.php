<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Template;

use Fal\Stick\Template\TemplateEvent;
use PHPUnit\Framework\TestCase;

class TemplateEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $this->event = new TemplateEvent('foo', null, 'bar');
    }

    public function testGetFile()
    {
        $this->assertEquals('foo', $this->event->getFile());
    }

    public function testGetMime()
    {
        $this->assertEquals('bar', $this->event->getMime());
    }

    public function testGetData()
    {
        $this->assertEquals(array(), $this->event->getData());
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo'), $this->event->setData(array('foo'))->getData());
    }

    public function testGetContent()
    {
        $this->assertNull($this->event->getContent());
    }

    public function testSetContent()
    {
        $this->assertEquals('foo', $this->event->setContent('foo')->getContent());
    }

    public function testMergeData()
    {
        $this->assertEquals(array('foo'), $this->event->mergeData(array('foo'))->getData());
    }
}
