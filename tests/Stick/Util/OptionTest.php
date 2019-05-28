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

    public function testIsRequired()
    {
        $this->assertFalse($this->option->isRequired('foo'));
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->option->isOptional('foo'));
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
    }

    public function testSetTypes()
    {
        $this->option->setTypes(array(
            'foo' => 'string,stdClass',
        ));

        $this->assertCount(1, $this->option->options());
    }

    public function testSetRequired()
    {
        $this->assertTrue($this->option->setRequired('foo')->isRequired('foo'));
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

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\OptionProvider::set
     */
    public function testSet($expected, $field, $add = null, $value = null, $allowed = null, $exception = null)
    {
        if ($add) {
            $this->option->add($field, ...$add);
        }

        if ($allowed) {
            $this->option->setAllowed(array(
                $field => $allowed,
            ));
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->option->set($field, $value);

            return;
        }

        $this->option->set($field, $value ?? $expected);

        $this->assertEquals($expected, $this->option->get($field));
    }

    public function testRem()
    {
        $this->option->set('foo', 'bar')->rem('foo');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option foo is not available.');
        $this->option->get('foo');
    }

    public function testAdd()
    {
        $this->option->add('foo', 'foo');

        $this->assertCount(1, $this->option->options());
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
        $this->expectExceptionMessage('Option baz is not available.');
        $this->option->resolve(array('baz' => null));
    }

    public function testOptions()
    {
        $this->assertCount(1, $this->option->options());
    }

    public function testSetOptional()
    {
        $this->assertTrue($this->option->setOptional('bar')->isOptional('bar'));
    }
}
