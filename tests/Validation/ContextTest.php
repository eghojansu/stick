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

use Ekok\Stick\Validation\Context;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    /** @var Context */
    private $context;

    public function setUp(): void
    {
        $this->context = new Context('foo', 'bar');
    }

    public function testDuplicate()
    {
        $context = $this->context->duplicate('bar', 'baz');

        $this->assertEquals('bar', $context->getField());
        $this->assertEquals('baz', $context->getValue());
    }

    public function testIsUsable()
    {
        $this->context->setValue('edit');

        $this->assertNull($this->context->getPrefix());
        $this->assertNull($this->context->getSuffix());
        $this->assertNull($this->context->getPosition());
        $this->assertFalse($this->context->isPositional());
        $this->assertEquals('string', $this->context->getType());
        $this->assertNull($this->context->getCurrentData());
        $this->assertCount(0, $this->context->getRaw());
        $this->assertCount(0, $this->context->getData());
        $this->assertEquals('edit', $this->context->getValue());
        $this->assertTrue($this->context->hasValue());
        $this->assertTrue($this->context->valid());
        $this->assertFalse($this->context->isSkipped());
        $this->assertFalse($this->context->isExcluded());
        $this->assertFalse($this->context->isNumeric());
        $this->assertFalse($this->context->isInteger());
        $this->assertFalse($this->context->isDouble());
        $this->assertFalse($this->context->isArray());
        $this->assertFalse($this->context->isBoolean());
        $this->assertFalse($this->context->isNull());
        $this->assertTrue($this->context->isString());

        $this->context->freeValue();
        $this->assertFalse($this->context->hasValue());

        $this->context->setSkip();
        $this->assertTrue($this->context->isSkipped());

        $this->context->setExcluded();
        $this->assertTrue($this->context->isExcluded());
    }

    public function testCheckOther()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'raw' => array('qux' => 'quux'),
        ));

        $this->assertTrue($clone->checkOther('qux'));
        $this->assertFalse($clone->checkOther('quux'));
    }

    public function testCheckOtherWithPosition()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'raw' => array(array('qux' => 'quux')),
            'position' => 0,
        ));

        $this->assertTrue($clone->checkOther('qux'));
        $this->assertFalse($clone->checkOther('quux'));
    }

    public function testGetOther()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'raw' => array('qux' => 'quux'),
        ));

        $this->assertEquals('quux', $clone->getOther('qux'));
        $this->assertNull($clone->getOther('baz'));
    }

    public function testGetOtherWithPosition()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'raw' => array(array('qux' => 'quux')),
            'position' => 0,
        ));

        $this->assertEquals('quux', $clone->getOther('qux'));
        $this->assertNull($clone->getOther('baz'));
    }

    public function testGetPath()
    {
        $this->assertEquals('foo', $this->context->getPath());
    }

    public function testGetPathFull()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'prefix' => 'foo',
            'position' => 0,
        ));

        $this->assertEquals('foo.0.foo', $clone->getPath());
    }

    public function testGetDate()
    {
        $today = new \DateTime();
        $clone = $this->context->duplicate('foo', new \DateTime(), array(
            'raw' => array(
                'instance' => new \DateTime(),
                'date' => 'tomorrow',
                'format' => $today->format('Y-m-d'),
                'invalid' => 'invalid',
            ),
        ));

        $this->assertInstanceOf('DateTime', $clone->getDate());
        $this->assertInstanceOf('DateTime', $clone->getDate('instance'));
        $this->assertInstanceOf('DateTime', $clone->getDate('date'));
        $this->assertInstanceOf('DateTime', $clone->getDate('format', 'Y-m-d'));
        $this->assertNull($clone->getDate('invalid'));
    }

    public function testCompareDate()
    {
        $clone = $this->context->duplicate('foo', new \DateTime(), array(
            'raw' => array(
                'tomorrow' => 'tomorrow',
                'yesterday' => 'yesterday',
            ),
        ));

        $this->assertEquals(-1, $clone->compareDate('tomorrow'));
        $this->assertEquals(1, $clone->compareDate('yesterday'));
    }

    public function testCompareDateException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Both date should be valid date: foo.');

        $this->context->duplicate('foo', 'bar')->compareDate();
    }

    public function testGetSize()
    {
        $clone = $this->context->duplicate('foo', 'bar', array(
            'raw' => array(
                'int' => 1234,
                'double' => 1234.56,
                'array' => array(1,2,3,4),
            ),
        ));

        $this->assertEquals(3, $clone->getSize());
        $this->assertEquals(1234, $clone->getSize('int'));
        $this->assertEquals(1234.56, $clone->getSize('double'));
        $this->assertEquals(4, $clone->getSize('array'));
    }
}
