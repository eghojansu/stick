<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 15, 2019 11:27
 */

namespace Fal\Stick\Test\Database\Sql;

use Fal\Stick\Core;
use Fixture\Mapper\TUser;
use Fixture\Mapper\TFriends;
use Fal\Stick\Database\Sql\Sql;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Database\Sql\Mapper;
use Fal\Stick\Database\Sql\ParameterConverter;
use Fal\Stick\Event\GetControllerArgumentsEvent;

class ParameterConverterTest extends TestCase
{
    private $converter;
    private $core;

    private function prepare($handler, array $params)
    {
        $this->core = new Core();
        $conn = new Sql($this->core, 'sqlite::memory:', null, null, array(file_get_contents(TEST_FIXTURE.'/files/schema.sql')));

        $conn->getPdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
        $conn->getPdo()->exec('insert into friends (user_id, friend_id, level) values (1, 2, 3), (2, 3, 4)');

        $this->core->rule(Sql::class, $conn);

        $this->converter = new ParameterConverter($this->core, $handler, $params);
    }

    public function testFromEvent()
    {
        $event = new GetControllerArgumentsEvent(new Core(), function () {}, array());

        $this->assertInstanceOf('Fal\\Stick\\Database\\Sql\\ParameterConverter', ParameterConverter::fromEvent($event));
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
        $handler = function (TUser $foo) {};
        $params = array('foo' => 1);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals('foo', $args['foo']->get('username'));
    }

    public function testResolveMapperComposit()
    {
        $handler = function (TFriends $foo) {};
        $params = array('foo' => 1, 'bar' => 2);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(1, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals(3, $args['foo']->get('level'));
    }

    public function testResolveMapperOverflowParams()
    {
        $handler = function (TUser $foo) {};
        $params = array('foo' => 1, 2 /* overflow */);
        $this->prepare($handler, $params);

        $args = $this->converter->resolve();

        $this->assertCount(2, $args);
        $this->assertInstanceOf(Mapper::class, $args['foo']);
        $this->assertEquals('foo', $args['foo']->get('username'));
    }

    public function testResolveMapperException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Record is not found: "TUser"');

        $handler = function (TUser $foo) {};
        $params = array('foo' => 4);
        $this->prepare($handler, $params);

        $this->converter->resolve();
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
}
