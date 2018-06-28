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

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperParameterConverter;
use Fal\Stick\Test\fixture\mapper\FooBar;
use Fal\Stick\Translator;
use PHPUnit\Framework\TestCase;

class MapperParameterConverterTest extends TestCase
{
    /**
     * @var MapperParameterConverter
     */
    private $converter;

    private function create(\Closure $cb, array $raw, array $params)
    {
        $app = App::create()->mset([
            'TEMP' => TEMP,
        ])->logClear();
        $cache = $app->get('cache');
        $cache->reset();

        $conn = new Connection($app, $cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE IF NOT EXISTS `foo_bar` (
    `foo` TEXT NOT NULL,
    `bar` TEXT NOT NULL,
    `val` TEXT,
    PRIMARY KEY (`foo`,`bar`)
);
INSERT INTO foo_bar VALUES (1,1,1), (1,2,2), (1,3,3);
SQL1
        ]);

        $ref = new \ReflectionFunction($cb);

        $this->converter = new MapperParameterConverter($conn, new Translator(), $ref, $raw, $params);
    }

    public function testResolve()
    {
        $cb = function (FooBar $foo) {};
        $raw = [
            'foo' => 1,
            'bar' => 1,
        ];
        $params = [
            1,
        ];
        $this->create($cb, $raw, $params);
        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceof(FooBar::class, $args[0]);
        $this->assertTrue($args[0]->valid());
        $this->assertEquals('1', $args[0]['val']);
    }

    public function testResolve2()
    {
        $cb = function ($baz, FooBar $foo) {};
        $raw = [
            'baz' => 3,
            'foo' => 1,
            'bar' => 2,
            'qux' => 4,
        ];
        $params = [
            3,
            1,
        ];
        $this->create($cb, $raw, $params);
        $args = $this->converter->resolve();

        $this->assertCount(2, $args);
        $this->assertInstanceof(FooBar::class, $args[1]);
        $this->assertTrue($args[1]->valid());
        $this->assertEquals(3, $args[0]);
        $this->assertEquals('2', $args[1]['val']);
    }

    /**
     * @expectedException \Fal\Stick\ResponseException
     * @expectedExceptionMessage Insufficient primary keys value, expect value of "foo, bar".
     */
    public function testResolveException()
    {
        $cb = function (FooBar $foo) {};
        $raw = [
            'foo' => 1,
        ];
        $params = [
            1,
        ];
        $this->create($cb, $raw, $params);
        $this->converter->resolve();
    }

    /**
     * @expectedException \Fal\Stick\ResponseException
     * @expectedExceptionMessage Record of foo_bar not found.
     */
    public function testResolveException2()
    {
        $cb = function (FooBar $foo) {};
        $raw = [
            'foo' => 1,
            'baz' => 4,
            'bar' => 2,
        ];
        $params = [
            1,
        ];
        $this->create($cb, $raw, $params);
        $this->converter->resolve();
    }
}
