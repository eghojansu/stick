<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 23:09
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\ParameterBag;
use PHPUnit\Framework\TestCase;

class ParameterBagTest extends TestCase
{
    private $bag;

    public function setup()
    {
        $this->bag = new ParameterBag(array('foo' => 'bar'));
    }

    public function testAll()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->bag->all());
    }

    public function testExists()
    {
        $this->assertTrue($this->bag->exists('foo'));
    }

    public function testGet()
    {
        $this->assertEquals('bar', $this->bag->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('baz', $this->bag->set('foo', 'baz')->get('foo'));
    }

    public function testClear()
    {
        $this->assertNull($this->bag->clear('foo')->get('foo'));
    }

    public function testReplace()
    {
        $this->assertEquals(array('bar' => 'baz'), $this->bag->replace(array('bar' => 'baz'))->all());
        $this->assertEquals(array('bar' => 'baz', 'foo' => 'bar'), $this->bag->replace(array('foo' => 'bar'), false)->all());
        $this->assertEquals(array('bar' => 'baz', 'foo' => 'bar'), $this->bag->replace(array('foo' => 'baz'), false, false)->all());
    }
}
