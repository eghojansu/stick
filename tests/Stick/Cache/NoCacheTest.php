<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 22, 2019 13:45
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Cache;

use Fal\Stick\Cache\NoCache;
use Fal\Stick\Cache\CacheItem;
use PHPUnit\Framework\TestCase;

class NoCacheTest extends TestCase
{
    private $cache;

    public function setup()
    {
        $this->cache = new NoCache();
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
        $this->assertFalse($this->cache->set('foo', new CacheItem('foo')));
    }

    public function testClear()
    {
        $this->assertFalse($this->cache->clear('foo'));
    }

    public function testReset()
    {
        $this->assertEquals(0, $this->cache->reset());
    }
}
