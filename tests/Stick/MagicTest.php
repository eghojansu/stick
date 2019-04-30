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

use Fal\Stick\TestSuite\MyTestCase;

class MagicTest extends MyTestCase
{
    public function testHas()
    {
        $this->assertFalse($this->magic->has('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->magic->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('foo', $this->magic->set('foo', 'foo')->get('foo'));
        $this->assertTrue($this->magic->has('foo'));
    }

    public function testRem()
    {
        $this->assertFalse($this->magic->set('foo', 'foo')->rem('foo')->has('foo'));
    }

    public function testMagicIsset()
    {
        $this->assertFalse(isset($this->magic->foo));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->magic->foo);
    }

    public function testMagicSet()
    {
        $this->magic->foo = 'foo';

        $this->assertEquals('foo', $this->magic->foo);
        $this->assertTrue(isset($this->magic->foo));
    }

    public function testMagicUnset()
    {
        $this->magic->foo = 'foo';
        unset($this->magic->foo);

        $this->assertFalse(isset($this->magic->foo));
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->magic['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->magic['foo']);
    }

    public function testOffsetSet()
    {
        $this->magic['foo'] = 'foo';

        $this->assertEquals('foo', $this->magic['foo']);
        $this->assertTrue(isset($this->magic['foo']));
    }

    public function testOffsetUnset()
    {
        $this->magic['foo'] = 'foo';
        unset($this->magic['foo']);

        $this->assertFalse(isset($this->magic['foo']));
    }

    public function testHive()
    {
        $this->assertEquals(array(), $this->magic->hive());
    }

    public function testReset()
    {
        $this->assertEquals(array(), $this->magic->set('foo', 'bar')->reset()->hive());
    }
}
