<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\CacheItem;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    private $item;

    public function setup()
    {
        $this->item = new CacheItem('foo', 0, 1547266154.7082);
    }

    public function testIsValid()
    {
        $this->assertTrue($this->item->isValid());
    }

    public function testIsExpired()
    {
        $this->assertFalse($this->item->isExpired());
    }

    public function testGetValue()
    {
        $this->assertEquals('foo', $this->item->getValue());
    }

    public function testGetTime()
    {
        $this->assertEquals(1547266154.7082, $this->item->getTime());
    }

    public function testGetTtl()
    {
        $this->assertEquals(0, $this->item->getTtl());
    }

    public function testToString()
    {
        $this->assertEquals(serialize($this->item), $this->item->toString());
    }

    public function testFromString()
    {
        $this->assertEquals($this->item, CacheItem::fromString($this->item->toString()));
    }

    public function testFromStringException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Invalid cache source');

        CacheItem::fromString('O:8:"stdClass":0:{}');
    }
}
