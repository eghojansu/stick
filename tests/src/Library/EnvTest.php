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

namespace Fal\Stick\Test\Library;

use Fal\Stick\Library\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    public function tearDown()
    {
        Env::reset();
    }

    public function testGet()
    {
        $this->assertEquals('bar', Env::get('foo', 'bar'));
    }

    public function testSet()
    {
        Env::set('foo', 'bar');
        $this->assertEquals('bar', Env::get('foo'));
    }

    public function testMerge()
    {
        Env::merge(array('foo' => 'bar'));
        $this->assertEquals('bar', Env::get('foo'));
    }

    public function testAll()
    {
        $this->assertEquals(array(), Env::all());
    }

    public function testReset()
    {
        Env::set('foo', 'bar');
        Env::reset();
        $this->assertEquals(array(), Env::all());
    }

    public function testLoad()
    {
        Env::load(FIXTURE.'files/env.php');
        $this->assertEquals('bar', Env::get('foo'));
        $this->assertTrue(Env::get('vtrue'));
        $this->assertNull(Env::get('vnull'));
    }
}
