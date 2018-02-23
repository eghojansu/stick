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

use Fal\Stick\Cache\NoCache;
use PHPUnit\Framework\TestCase;

class NoCacheTest extends TestCase
{
    private $driver;

    public function setUp()
    {
        $this->driver = new NoCache;
    }

    public function testName()
    {
        $expected = 'nocache';
        $result = $this->driver->name();
        $this->assertEquals($expected, $result);
    }

    public function testGet()
    {
        $expected = '';
        $key = 'foo';
        $result = $this->driver->get($key);
        $this->assertEquals($expected, $result);
    }

    public function testSet()
    {
        $expected = $this->driver;
        $key = 'foo';
        $value = 'bar';
        $result = $this->driver->set($key, $value);
        $this->assertEquals($expected, $result);
    }

    public function testClear()
    {
        $expected = false;
        $key = 'foo';
        $result = $this->driver->clear($key);
        $this->assertEquals($expected, $result);
    }

    public function testReset()
    {
        $expected = true;
        $result = $this->driver->reset();
        $this->assertEquals($expected, $result);
    }
}
