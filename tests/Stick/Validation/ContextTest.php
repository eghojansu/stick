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

namespace Fal\Stick\Test\Validation;

use Fal\Stick\TestSuite\MyTestCase;

class ContextTest extends MyTestCase
{
    public function testGetField()
    {
        $this->assertNull($this->context->getField());
    }

    public function testSetField()
    {
        $this->assertEquals('foo', $this->context->setField('foo')->getField());
    }

    public function testGetArguments()
    {
        $this->assertCount(0, $this->context->getArguments());
    }

    public function testSetArguments()
    {
        $this->assertEquals(array('foo'), $this->context->setArguments(array('foo'))->getArguments());
    }

    public function testGetValue()
    {
        $this->context->addValidated('foo', 'bar');

        $this->assertEquals('bar', $this->context->setField('foo')->getValue());
        $this->assertNull($this->context->setField('bar')->getValue());
    }

    public function testGetData()
    {
        $this->assertCount(0, $this->context->getData());
    }

    public function testGetValidated()
    {
        $this->assertCount(0, $this->context->getValidated());
    }

    public function testSetValidated()
    {
        $this->assertEquals(array('foo'), $this->context->setValidated(array('foo'))->getValidated());
    }

    public function testAddValidated()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->context->addValidated('foo', 'bar')->getValidated());
    }
}
