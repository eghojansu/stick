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

use Fal\Stick\Bag;
use PHPUnit\Framework\TestCase;

class BagTest extends TestCase
{
    private $bag;

    public function setUp()
    {
        $this->bag = new Bag();
    }

    public function testAll()
    {
        $this->assertEquals(array(), $this->bag->all());
    }

    public function testExists()
    {
        $this->assertFalse($this->bag->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->bag->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->bag->set('foo', 'bar')->get('foo'));
    }

    public function testClear()
    {
        $this->assertNull($this->bag->set('foo', 'bar')->clear('foo')->get('foo'));
        $this->assertNull($this->bag->set('bar.baz', 'bar')->clear('bar.baz')->get('bar.baz'));
    }
}
