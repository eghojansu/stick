<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Cache;

use Fal\Stick\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private $driver;

    public function setUp()
    {
        $this->driver = new FileCache(TEMP . 'cache/');
    }

    public function tearDown()
    {
        $this->driver->reset();
        if (file_exists($dir = TEMP . 'cache')) {
            rmdir($dir);
        }
    }

    public function testName()
    {
        $expected = 'filecache';
        $result = $this->driver->name();
        $this->assertEquals($expected, $result);
    }

    public function testGet()
    {
        $key = 'foo';
        $this->assertEquals('', $this->driver->get($key));

        $this->assertEquals($key, $this->driver->set($key, $key)->get($key));
    }

    public function testSet()
    {
        $expected = $this->driver;
        $key = 'foo';
        $value = 'bar';
        $result = $this->driver->set($key, $value);
        $this->assertEquals($expected, $result);
        $this->assertEquals($value, $this->driver->get($key));
    }

    public function testClear()
    {
        $key = 'foo';
        $result = $this->driver->clear($key);
        $this->assertFalse($result);

        $this->driver->set($key, $key);
        $result = $this->driver->clear($key);
        $this->assertTrue($result);
    }

    public function testReset()
    {
        $result = $this->driver->reset();
        $this->assertTrue($result);

        $key = 'foo';
        $this->driver->set($key, $key);
        $this->assertContains($key, $this->driver->get($key));

        $result = $this->driver->clear($key);
        $this->assertTrue($result);
        $this->assertEquals('', $this->driver->get($key));
    }
}
