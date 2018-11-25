<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 19:51
 */

namespace Fal\Stick\Test;

use Fal\Stick\CacheItem;
use Fal\Stick\FilesystemCache;
use PHPUnit\Framework\TestCase;

class FilesystemCacheTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new FilesystemCache(TEMP.'fs-cache/');
    }

    public function tearDown()
    {
        $this->cache->reset();
    }

    public function testSeed()
    {
        $this->assertSame($this->cache, $this->cache->seed('foo'));
    }

    public function testExists()
    {
        $this->assertFalse($this->cache->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->cache->get('foo'));
    }

    public function testSet()
    {
        $this->cache->set('foo', new CacheItem('foo'));

        $this->assertEquals('foo', $this->cache->get('foo')->getValue());

        $this->cache->set('bar', new CacheItem('foo', null, -1));
        $this->assertNull($this->cache->get('bar'));
    }

    public function testClear()
    {
        $this->assertTrue($this->cache->clear('foo'));
    }

    public function testReset()
    {
        $this->assertSame($this->cache, $this->cache->reset());
    }
}
