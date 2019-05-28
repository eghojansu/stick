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

namespace Fal\Stick\Test\Web;

use Fal\Stick\Fw;
use Fal\Stick\Web\Basket;
use Fal\Stick\TestSuite\MyTestCase;

class BasketTest extends MyTestCase
{
    private $fw;
    private $basket;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->basket = new Basket($this->fw = new Fw());
    }

    public function testCurrent()
    {
        $this->assertSame($this->basket, $this->basket->current());
    }

    public function testKey()
    {
        $this->assertEquals(0, $this->basket->key());
    }

    public function testNext()
    {
        $this->assertNull($this->basket->next());
        $this->assertEquals(1, $this->basket->key());
    }

    public function testRewind()
    {
        $this->assertNull($this->basket->rewind());
    }

    public function testValid()
    {
        $this->assertFalse($this->basket->valid());
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->basket->count());
    }

    public function testHas()
    {
        $this->assertFalse($this->basket->has('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->basket->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->basket->set('foo', 'bar')->get('foo'));
    }

    public function testRem()
    {
        $this->assertNull($this->basket->set('foo', 'bar')->rem('foo')->get('foo'));
    }

    public function testReset()
    {
        $this->assertSame($this->basket, $this->basket->reset());
    }

    public function testToArray()
    {
        $this->assertEquals(array(), $this->basket->toArray());
    }

    public function testFromArray()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->basket->fromArray(array('foo' => 'bar'))->toArray());
    }

    public function testDry()
    {
        $this->assertTrue($this->basket->dry());
    }

    public function testFind()
    {
        $this->basket->fromArray(array(
            'name' => 'foo',
        ))->save()->reset();
        $this->basket->fromArray(array(
            'name' => 'foo',
        ))->save()->reset();

        $this->assertCount(1, $this->basket->find('name', 'foo', 1));
    }

    public function testSave()
    {
        $this->assertCount(1, $this->basket->fromArray(array(
            '_id' => 'bar',
        ))->save());
    }

    public function testDelete()
    {
        $this->basket->fromArray(array(
            '_id' => 'foo',
        ))->save();

        $this->assertCount(0, $this->basket->delete());
    }

    public function testLoad()
    {
        $this->basket->fromArray(array(
            '_id' => 'foo',
        ))->save();

        $this->assertCount(1, $this->basket->load());
    }

    public function testDrop()
    {
        $this->basket->fromArray(array(
            '_id' => 'foo',
        ))->save();

        $this->assertCount(1, $this->fw->get('SESSION.basket'));
        $this->assertCount(1, $this->basket);
        $this->assertCount(0, $this->basket->drop());
    }

    public function testCheckout()
    {
        $this->basket->fromArray(array(
            '_id' => 'foo',
        ))->save();

        $this->assertEquals(array(array('_id' => 'foo')), $this->basket->checkout());
    }
}
