<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 19:39
 */

namespace Fal\Stick\Test;

use Fal\Stick\CacheItem;
use Fal\Stick\NoCache;
use PHPUnit\Framework\TestCase;

class NoCacheTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new NoCache();
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
        $this->assertTrue($this->cache->set('foo', new CacheItem()));
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
