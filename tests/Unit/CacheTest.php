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

use Fal\Stick\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new Cache('', 'test', TEMP . 'cache/');
    }

    public function tearDown()
    {
        $this->cache->reset();

        $cache = TEMP . 'cache/';
        if (file_exists($cache)) {
            foreach (glob($cache . '*') as $file) {
                unlink($file);
            }
            rmdir($cache);
        }

        error_clear_last();
    }

    public function cacheProvider()
    {
        $provider = [
            [''],
            ['folder='.TEMP.'file_cache/'],
            ['fallback'],
        ];

        if (extension_loaded('apc')) {
            $provider[] = ['apc'];
            $provider[] = ['apcu'];
            $provider[] = ['auto'];
        }

        if (extension_loaded('memcached')) {
            $provider[] = ['memcached=127.0.0.1'];
        }

        if (extension_loaded('redis')) {
            $provider[] = ['redis=127.0.0.1'];
        }

        if (extension_loaded('wincache')) {
            $provider[] = ['wincache'];
        }

        if (extension_loaded('xcache')) {
            $provider[] = ['xcache'];
        }

        return $provider;
    }

    /** @dataProvider cacheProvider */
    public function testIsCached($dsn)
    {
        $this->cache->setDsn($dsn);
        $val = 'foo';

        $this->assertFalse($this->cache->isCached($hash, $data, 'foo', $val));

        if ($dsn) {
            $this->cache->set($hash, $val);

            $this->assertTrue($this->cache->isCached($hash, $data));
            $this->assertEquals($val, $data[0]);
        }
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionRegex /expect at least a hash parameter, none given$/
     */
    public function testIsCachedException()
    {
        $this->cache->isCached($hash, $data);
    }

    /** @dataProvider cacheProvider */
    public function testExists($dsn)
    {
        $this->cache->setDsn($dsn);
        $key = 'foo';
        $this->assertFalse($this->cache->exists($key));

        if ($dsn) {
            $this->assertTrue($this->cache->set($key, $key)->exists($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testGet($dsn)
    {
        $this->cache->setDsn($dsn);
        $key = 'foo';
        $this->assertEquals([], $this->cache->get($key));

        if ($dsn) {
            $this->assertContains($key, $this->cache->set($key, $key)->get($key));

            $this->cache->clear($key);
            $this->cache->set($key, $key, 1);
            // onesecond
            usleep(1000000);
            $this->assertEquals([], $this->cache->get($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testSet($dsn)
    {
        $this->cache->setDsn($dsn);
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
        $this->cache->setDsn($dsn);
        $key = 'foo';
        $this->assertFalse($this->cache->clear($key));

        if ($dsn) {
            $this->assertTrue($this->cache->set($key, $key)->clear($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testReset($dsn)
    {
        $this->cache->setDsn($dsn);
        $key = 'foo';
        $this->assertTrue($this->cache->reset());
        $this->assertTrue($this->cache->set($key, $key)->reset());
    }

    public function testDef()
    {
        $this->assertEquals([null, null], $this->cache->def());
    }

    public function testRedis()
    {
        $this->cache->setDsn('redis=invalid-host');
        // fallback to folder
        $this->assertEquals(['folder', TEMP . 'cache/'], $this->cache->def());
    }

    public function testGetPrefix()
    {
        $this->assertEquals('test', $this->cache->getPrefix());
    }

    public function testSetPrefix()
    {
        $this->assertEquals('foo', $this->cache->setPrefix('foo')->getPrefix());
    }

    public function testGetDsn()
    {
        $this->assertEquals('', $this->cache->getDsn());
    }

    public function testSetDsn()
    {
        $this->assertEquals('foo', $this->cache->setDsn('foo')->getDsn());
        $this->assertEquals(['folder', TEMP . 'cache/'], $this->cache->def());
    }
}
