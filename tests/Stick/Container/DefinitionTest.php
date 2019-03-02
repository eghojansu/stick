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

namespace Fal\Stick\Test\Container;

use Fal\Stick\Container\Definition;
use PHPUnit\Framework\TestCase;

class DefinitionTest extends TestCase
{
    private $definition;

    public function setup()
    {
        $this->definition = new Definition('foo');
    }

    public function testGetClass()
    {
        $this->assertEquals('foo', $this->definition->getClass());
    }

    public function testSetClass()
    {
        $this->assertEquals('bar', $this->definition->setClass('bar')->getClass());
    }

    public function testGetUse()
    {
        $this->assertNull($this->definition->getUse());
    }

    public function testSetUse()
    {
        $this->assertEquals('foo', $this->definition->setUse('foo')->getUse());
    }

    public function testIsShared()
    {
        $this->assertTrue($this->definition->isShared());
    }

    public function testSetShared()
    {
        $this->assertFalse($this->definition->setShared(false)->isShared());
    }

    public function testGetInstance()
    {
        $this->assertNull($this->definition->getInstance());
    }

    public function testSetInstance()
    {
        $std = new \stdClass();
        $this->assertSame($std, $this->definition->setInstance($std)->getInstance());
    }

    public function testGetArguments()
    {
        $this->assertEquals(array(), $this->definition->getArguments());
    }

    public function testSetArguments()
    {
        $this->assertEquals(array('foo'), $this->definition->setArguments(array('foo'))->getArguments());
    }

    public function testGetFactory()
    {
        $this->assertNull($this->definition->getFactory());
    }

    public function testSetFactory()
    {
        $cb = function () {};
        $this->assertSame($cb, $this->definition->setFactory($cb)->getFactory());
    }

    public function testGetBoot()
    {
        $this->assertNull($this->definition->getBoot());
    }

    public function testSetBoot()
    {
        $cb = function () {};
        $this->assertSame($cb, $this->definition->setBoot($cb)->getBoot());
    }

    /**
     * @dataProvider constructProvider
     */
    public function testConstruct($expected, $definition)
    {
        $obj = new Definition('foo', $definition);

        foreach ($expected as $key => $value) {
            $get = 'shared' === $key ? 'is'.$key : 'get'.$key;

            $this->assertEquals($value, $obj->$get());
        }
    }

    public function constructProvider()
    {
        return array(
            array(array('factory' => function () {}), function () {}),
            array(array('use' => 'stdClass'), new \stdClass()),
            array(array('use' => 'bar'), 'bar'),
            array(array('shared' => false), false),
            array(array('arguments' => array('foo')), array('arguments' => array('foo'))),
        );
    }
}
