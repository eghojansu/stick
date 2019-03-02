<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 07:03
 */

namespace Fal\Stick\Test\Validation;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Validation\Result;

class ResultTest extends TestCase
{
    private $result;

    public function setup()
    {
        $this->result = new Result(array());
    }

    public function testSetCurrent()
    {
        $this->assertEquals('foo', $this->result->setCurrent('foo', 'bar')->rule);
    }

    public function testValid()
    {
        $this->assertTrue($this->result->Valid());
    }

    public function testRaw()
    {
        $this->assertNull($this->result->raw('foo'));
    }

    public function testData()
    {
        $this->assertNull($this->result->data('foo'));
    }

    public function testValue()
    {
        $this->assertNull($this->result->value('foo'));
    }

    public function testAddData()
    {
        $this->assertEquals('foo', $this->result->addData('foo', 'foo')->data('foo'));
    }

    public function testAddError()
    {
        $this->assertEquals(array('foo' => array('bar')), $this->result->addError('foo', 'bar')->errors);
    }

    public function testHasError()
    {
        $this->assertFalse($this->result->hasError('foo'));
        $this->assertTrue($this->result->addError('foo', 'bar')->hasError('foo'));
    }
}
