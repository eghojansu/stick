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

namespace Fal\Stick\Test\Library\Sql;

use Fal\Stick\App;
use Fal\Stick\Library\Sql\Connection;
use Fal\Stick\Library\Sql\Mapper;
use Fal\Stick\Library\Sql\MapperParameterConverter;
use PHPUnit\Framework\TestCase;

class MapperParameterConverterTest extends TestCase
{
    private $converter;
    private $app;

    private function prepare($handler, array $params)
    {
        $this->app = new App();
        $conn = new Connection($this->app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
        $conn->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
        $conn->pdo()->exec('insert into friends (user_id, friend_id, level) values (1, 2, 3), (2, 3, 4)');

        $this->converter = new MapperParameterConverter($this->app, $conn, $handler, $params);
    }

    public function resolveProvider()
    {
        return array(
            array(array(), function () {}, array()),
            array(array('foo' => 'bar'), function ($foo) {}, array('foo' => 'bar')),
            array(array('foo' => 'bar', 'bar' => 'baz'), function ($foo, $bar) {}, array('foo' => 'bar', 'bar' => 'baz')),
            array(array('foo' => 'bar'), function ($foo, $bar) {}, array('foo' => 'bar')),
        );
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve($expected, $handler, $params)
    {
        $this->prepare($handler, $params);

        $this->assertEquals($expected, $this->converter->resolve());
    }

    public function testResolveMapper()
    {
        $handler = function (\Fixture\Mapper\User $foo) {};
        $params = array('foo' => 1);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals('foo', $args['foo']->get('username'));
    }

    public function testResolveMapperComposit()
    {
        $handler = function (\Fixture\Mapper\Friends $foo) {};
        $params = array('foo' => 1, 'bar' => 2);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals(3, $args['foo']->get('level'));
    }

    public function testResolveMapperOverflowParams()
    {
        $handler = function (\Fixture\Mapper\User $foo) {};
        $params = array('foo' => 1, 2 /* overflow */);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(2, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals('foo', $args['foo']->get('username'));
    }

    /**
     * @expectedException \Fal\Stick\HttpException
     * @expectedExceptionMessage Record of user is not found.
     */
    public function testResolveMapperException()
    {
        $handler = function (\Fixture\Mapper\User $foo) {};
        $params = array('foo' => 4);
        $this->prepare($handler, $params);

        $this->converter->resolve();
    }

    public function hasMapperProvider()
    {
        return array(
            array(true, function (\Fixture\Mapper\User $user) {}),
            array(false, function ($user) {}),
        );
    }

    /**
     * @dataProvider hasMapperProvider
     */
    public function testHasMapper($expected, $handler)
    {
        $this->prepare($handler, array('user' => 'foo'));

        $this->assertEquals($expected, $this->converter->hasMapper());
    }
}
