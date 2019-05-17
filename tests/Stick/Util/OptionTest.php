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

namespace Fal\Stick\Test\Util;

use Fal\Stick\Util\Option;
use Fal\Stick\TestSuite\MyTestCase;

class OptionTest extends MyTestCase
{
    protected function createInstance()
    {
        return new Option(array(
            'foo' => 'foo',
        ));
    }

    public function testCount()
    {
        $this->assertCount(1, $this->option);
    }

    public function testGetIterator()
    {
        $arr = array();

        foreach ($this->option as $value) {
            $arr[] = $value;
        }

        $this->assertCount(1, $arr);
    }

    public function testIsAllowed()
    {
        $this->assertTrue($this->option->isAllowed('foo', 'bar'));
    }

    public function testIsValid()
    {
        $this->assertTrue($this->option->isValid('foo', 'bar'));
    }

    public function testIsRequired()
    {
        $this->assertFalse($this->option->isRequired('foo'));
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->option->isOptional('foo'));
    }

    public function testGetDefaults()
    {
        $this->assertEquals(array('foo' => 'foo'), $this->option->getDefaults());
    }

    public function testSetDefaults()
    {
        $this->assertEquals('bar', $this->option->setDefaults(array(
            'foo' => 'bar',
        ))->get('foo'));
    }

    public function testSetAllowed()
    {
        $this->option->setAllowed(array(
            'foo' => 'foo',
        ));
        $this->assertTrue($this->option->isAllowed('foo', 'foo'));

        $this->expectException('OutOfBoundsException');
        $this->expectExceptionMessage("Option foo only allow these values: 'foo'.");
        $this->option->isAllowed('foo', 'bar');
    }

    public function testSetTypes()
    {
        $this->option->setTypes(array(
            'foo' => 'string,stdClass',
        ));
        $this->assertTrue($this->option->isValid('foo', 'bar'));
        $this->assertTrue($this->option->isValid('foo', new \stdClass()));

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Option foo expect string or stdClass, given integer type.');
        $this->option->isValid('foo', 1);
    }

    public function testSetRequired()
    {
        $this->assertTrue($this->option->setRequired('foo')->isRequired('foo'));
    }

    public function testSetOptionals()
    {
        $this->assertTrue($this->option->setOptionals('foo')->isOptional('foo'));
    }

    public function testHas()
    {
        $this->assertTrue($this->option->has('foo'));
        $this->assertFalse($this->option->has('bar'));
    }

    public function testGet()
    {
        $this->option->setDefaults(array(
            'foo' => function () {
                return 'bar';
            },
        ));

        $this->assertEquals('bar', $this->option->get('foo'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option bar is not available.');
        $this->option->get('bar');
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->option->set('foo', 'bar')->get('foo'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option bar is not available.');
        $this->option->set('bar', 'baz');
    }

    public function testRem()
    {
        $this->option->set('foo', 'bar')->rem('foo');
        $this->assertEquals('foo', $this->option->get('foo'));
    }

    public function testAdd()
    {
        $this->option->add('foo', 'foo');

        $this->assertTrue($this->option->isValid('foo', 'bar'));

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Option foo expect string, given integer type.');
        $this->option->isValid('foo', 1);
    }

    public function testResolve()
    {
        $this->option->setDefaults(array(
            'bar' => 'baz',
            'foo' => null,
        ));

        $this->assertEquals('qux', $this->option->resolve(array(
            'bar' => 'qux',
        ))->get('bar'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option required: foo.');
        $this->option->setRequired('foo');
        $this->option->resolve(array('foo' => null));
    }

    public function testResolveNotOption()
    {
        $this->option->setDefaults(array(
            'bar' => 'baz',
            'foo' => null,
        ));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Not an option: baz.');
        $this->option->resolve(array('baz' => null));
    }
}
