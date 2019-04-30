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

use Fal\Stick\Validation\Result;
use Fal\Stick\Validation\Context;
use Fal\Stick\TestSuite\MyTestCase;

class ResultTest extends MyTestCase
{
    protected function createInstance()
    {
        return new Result(new Context());
    }

    public function testGetError()
    {
        $this->assertCount(0, $this->result->getError('foo'));
    }

    public function testSetError()
    {
        $this->assertEquals(array('foo'), $this->result->setError('foo', array('foo'))->getError('foo'));
    }

    public function testAddError()
    {
        $this->assertEquals(array('foo'), $this->result->addError('foo', 'foo')->getError('foo'));
        $this->assertEquals(array('foo', 'bar'), $this->result->addError('foo', array('bar'))->getError('foo'));
    }

    public function testGetErrors()
    {
        $this->assertCount(0, $this->result->getErrors());
    }

    public function testIsSuccess()
    {
        $this->assertTrue($this->result->isSuccess());
        $this->assertFalse($this->result->setError('foo', array('bar'))->isSuccess());
    }

    public function testGetData()
    {
        $this->assertCount(0, $this->result->getData());
    }
}
