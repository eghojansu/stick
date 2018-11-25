<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 12:52
 */

namespace Fal\Stick\Test;

use Fal\Stick\CacheItem;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    private $item;

    public function setUp()
    {
        $this->item = new CacheItem();
    }

    public function testIsValid()
    {
        $this->assertFalse($this->item->isValid());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->item->isEmpty());
    }

    public function testIsExpired()
    {
        $this->assertFalse($this->item->isExpired());
    }

    public function testGetValue()
    {
        $this->assertNull($this->item->getValue());
    }

    public function testGetTime()
    {
        $this->assertGreaterThan(0, $this->item->getTime());
    }

    public function testGetTtl()
    {
        $this->assertEquals(0, $this->item->getTtl());
    }

    public function testToString()
    {
        $this->assertNotEmpty($this->item->toString());
    }

    public function testFromString()
    {
        $item = new CacheItem('foo');
        $create = CacheItem::fromString($item->toString());

        $this->assertEquals($item->getValue(), $create->getValue());
        $this->assertNull(CacheItem::fromString());
    }
}
