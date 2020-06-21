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

namespace Ekok\Stick\Tests\Validation;

use Ekok\Stick\Validation\Result;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Validation\Result
 */
final class ResultTest extends TestCase
{
    private $result;

    protected function setUp(): void
    {
        $raw = array(
            'foo' => 'bar',
            'bar' => array(
                'baz',
                'qux',
            ),
            'time' => 'August 21, 1998',
            'next_time' => 'August 21, 1999',
            'arr' => array('foo', 'bar'),
            'number' => '10',
            'foo_copy' => 'bar',
        );

        $this->result = new Result($raw);
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->result['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->result['foo']);
    }

    public function testOffsetSet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Array access is read only.');

        $this->result['foo'] = 'bar';
    }

    public function testOffsetUnset()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Array access is read only.');

        unset($this->result['foo']);
    }

    public function testHasField()
    {
        $this->assertTrue($this->result->hasField('foo'));
        $this->assertFalse($this->result->hasField('baz'));
    }

    public function testGetField()
    {
        $this->assertNull($this->result->getField());
    }

    public function testSetField()
    {
        $this->assertEquals('foo', $this->result->setField('foo')->getField());
    }

    public function testRaw()
    {
        $expected = array(
            'foo' => 'bar',
            'bar' => array(
                'baz',
                'qux',
            ),
            'time' => 'August 21, 1998',
            'next_time' => 'August 21, 1999',
            'arr' => array('foo', 'bar'),
            'number' => '10',
            'foo_copy' => 'bar',
        );

        $this->assertEquals($expected, $this->result->raw());
    }

    public function testData()
    {
        $this->assertEquals(array(), $this->result->data());
    }

    public function testErrors()
    {
        $this->assertEquals(array(), $this->result->errors());
    }

    public function testError()
    {
        $this->assertNull($this->result->error('foo'));
    }

    public function testErrorAdd()
    {
        $this->result->setField('foo');

        $this->assertEquals(array('foo'), $this->result->errorAdd('foo')->error());
        $this->assertFalse($this->result->noErrorAdded());
    }

    public function testNoErrorAdded()
    {
        $this->assertTrue($this->result->noErrorAdded());
    }

    public function testSuccess()
    {
        $this->assertTrue($this->result->success());
    }

    public function testNewRule()
    {
        $this->assertSame($this->result, $this->result->newRule());
    }

    public function testIsSkip()
    {
        $this->assertFalse($this->result->isSkip());
    }

    public function testSkip()
    {
        $this->assertTrue($this->result->skip()->isSkip());
    }

    public function testGetValue()
    {
        $this->result->setField('foo');

        $this->assertEquals('bar', $this->result->getValue());
    }

    public function testSetValue()
    {
        $this->result->setField('foo');

        $this->assertEquals('baz', $this->result->setValue('baz')->getValue());
    }

    public function testFieldCheck()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No field pointed.');

        $this->result->getValue();
    }

    public function testGetValueAsTime()
    {
        $this->result->setField('time');

        $this->assertEquals(strtotime('August 21, 1998'), $this->result->getValueAsTime());
        $this->assertEquals(strtotime('August 21, 1999'), $this->result->getValueAsTime('next_time'));
        $this->assertEquals(strtotime('August 21, 1998'), $this->result->getValueAsTime('August 21, 1998'));
    }

    public function testGetValueSize()
    {
        $this->assertEquals(3, $this->result->getValueSize(true, 'foo'));
        $this->assertEquals(2, $this->result->getValueSize(true, 'arr'));
        $this->assertEquals(10, $this->result->getValueSize(true, 'number'));
        $this->assertEquals(2, $this->result->getValueSize(false, 'number'));
    }

    public function testGetValueAfter()
    {
        $this->assertEquals(' 1998', $this->result->getValueAfter(',', 'time'));
    }

    public function testGetValueBefore()
    {
        $this->assertEquals('August 21', $this->result->getValueBefore(',', 'time'));
    }

    public function testIsValueEqualTo()
    {
        $this->result->setField('foo');

        $this->assertFalse($this->result->isValueEqualTo('bar'));
        $this->assertTrue($this->result->isValueEqualTo('foo_copy'));
    }

    public function testIsValueEmpty()
    {
        $this->assertFalse($this->result->isValueEmpty('foo'));
    }

    public function testFilterValue()
    {
        $this->result->setField('foo');

        $this->assertFalse($this->result->filterValue(FILTER_VALIDATE_IP));
    }
}
