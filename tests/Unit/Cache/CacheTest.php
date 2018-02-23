<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Cache\Test\Unit;

use Fal\Stick\Cache\Apc;
use Fal\Stick\Cache\Apcu;
use Fal\Stick\Cache\Cache;
use Fal\Stick\Cache\FileCache;
use Fal\Stick\Cache\Memcached;
use Fal\Stick\Cache\NoCache;
use Fal\Stick\Cache\Redis;
use Fal\Stick\Cache\Wincache;
use Fal\Stick\Cache\Xcache;
use Fal\Stick\Helper;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private $cache;

    public function tearDown()
    {
        if ($this->cache) {
            $this->cache->reset();
        }
    }

    public function cacheProvider()
    {
        $provider = [
            ['', NoCache::class],
            ['folder='.TEMP.'file_cache/', FileCache::class],
            ['apc', Apc::class],
            ['auto', Apcu::class],
            ['fallback', FileCache::class],
            ['memcached=127.0.0.1', Memcached::class],
            ['redis=127.0.0.1', Redis::class],
        ];

        if (extension_loaded('wincache')) {
            $provider[] = ['wincache', Wincache::class];
        }

        if (extension_loaded('xcache')) {
            $provider[] = ['xcache', Xcache::class];
        }

        return $provider;
    }

    protected function cache($dsn)
    {
        $this->cache = new Cache($dsn, 'test', TEMP . 'cache/', new Helper());
    }

    /** @dataProvider cacheProvider */
    public function testGet($dsn)
    {
        $this->cache($dsn);
        $key = 'foo';
        $this->assertEquals([], $this->cache->get($key));

        if ($dsn) {
            $this->assertContains($key, $this->cache->set($key, $key)->get($key));

            $this->cache->clear($key);
            $this->cache->set($key, $key, -1);
            $this->assertEquals([], $this->cache->set($key, $key, -1)->get($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testSet($dsn)
    {
        $this->cache($dsn);
        $key = 'foo';
        $value = 'bar';
        $this->assertEquals($this->cache, $this->cache->set($key, $value));

        if ($dsn) {
            $this->assertContains($value, $this->cache->get($key));

            $this->assertContains($key, $this->cache->set($key, $key)->get($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testClear($dsn)
    {
        $this->cache($dsn);
        $key = 'foo';
        $this->assertFalse($this->cache->clear($key));

        if ($dsn) {
            $this->assertTrue($this->cache->set($key, $key)->clear($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testReset($dsn)
    {
        $this->cache($dsn);
        $key = 'foo';
        $this->assertTrue($this->cache->reset());
        $this->assertTrue($this->cache->set($key, $key)->reset());
    }

    /** @dataProvider cacheProvider */
    public function testGetPrefix($dsn)
    {
        $this->cache($dsn);
        $this->assertEquals('test', $this->cache->getPrefix());
    }

    /** @dataProvider cacheProvider */
    public function testSetPrefix($dsn)
    {
        $this->cache($dsn);
        $this->assertEquals('foo', $this->cache->setPrefix('foo')->getPrefix());
    }

    /** @dataProvider cacheProvider */
    public function testGetDsn($dsn)
    {
        $this->cache($dsn);
        $this->assertEquals($dsn, $this->cache->getDsn());
    }

    /** @dataProvider cacheProvider */
    public function testSetDsn($dsn)
    {
        $this->cache($dsn);
        $this->assertEquals($dsn, $this->cache->setDsn($dsn)->getDsn());
    }

    /** @dataProvider cacheProvider */
    public function testGetDriver($dsn, $class)
    {
        $this->cache($dsn);
        $this->assertInstanceof($class, $this->cache->getDriver());
    }
}
