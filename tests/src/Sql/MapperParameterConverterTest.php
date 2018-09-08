<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Sql\MapperParameterConverter;
use FixtureMapper\Friends;
use FixtureMapper\User;
use PHPUnit\Framework\TestCase;

class MapperParameterConverterTest extends TestCase
{
    private $converter;
    private $app;

    public function tearDown()
    {
        spl_autoload_unregister(array($this->app, 'loadClass'));
    }

    private function prepare($handler, array $params)
    {
        $this->app = new App();
        $this->app->mset(array(
            'AUTOLOAD' => array(
                'FixtureMapper\\' => array(FIXTURE.'classes/mapper/'),
            ),
        ))->registerAutoloader();
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
            array(array('bar'), function ($foo) {}, array('foo' => 'bar')),
            array(array('bar', 'baz'), function ($foo, $bar) {}, array('foo' => 'bar', 'bar' => 'baz')),
            array(array('bar', null), function ($foo, $bar) {}, array('foo' => 'bar')),
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
        $handler = function (User $foo) {};
        $params = array('foo' => 1);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args[0]);
        $this->assertEquals('foo', $args[0]->get('username'));
    }

    public function testResolveMapperComposit()
    {
        $handler = function (Friends $foo) {};
        $params = array('foo' => 1, 'bar' => 2);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args[0]);
        $this->assertEquals(3, $args[0]->get('level'));
    }

    public function testResolveMapperOverflowParams()
    {
        $handler = function (User $foo) {};
        $params = array('foo' => 1, 2 /* overflow */);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args[0]);
        $this->assertEquals('foo', $args[0]->get('username'));
    }

    /**
     * @expectedException \Fal\Stick\ResponseException
     * @expectedExceptionMessage Insufficient primary keys value, expect value of "user_id, friend_id".
     */
    public function testResolveMapperException()
    {
        $handler = function (Friends $foo) {};
        $params = array('foo' => 1);
        $this->prepare($handler, $params);

        $this->converter->resolve();
    }

    /**
     * @expectedException \Fal\Stick\ResponseException
     * @expectedExceptionMessage Record of user is not found.
     */
    public function testResolveMapperException2()
    {
        $handler = function (User $foo) {};
        $params = array('foo' => 4);
        $this->prepare($handler, $params);

        $this->converter->resolve();
    }
}
