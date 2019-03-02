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

namespace Fal\Stick\Test\Container;

use Fal\Stick\Container\Container;
use Fal\Stick\Container\Definition;
use Fixture\BazClass;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private $container;

    public function setup()
    {
        $this->container = new Container();
    }

    /**
     * @dataProvider grabProvider
     */
    public function testGrab($expected, $expression)
    {
        $callback = $this->container->grab($expression);

        $this->assertEquals($expected, $callback());
    }

    /**
     * @dataProvider callProvider
     */
    public function testCall($expected, $callback, $arguments = null, $exception = null)
    {
        $std = new \stdClass();
        $std->foo = 'bar';
        $this->container->set('std_service', new Definition('stdClass', $std));
        $this->container->setParameter('foo', 'bar');

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->container->call($callback, $arguments);

            return;
        }

        $this->assertEquals($expected, $this->container->call($callback, $arguments));
    }

    public function testHas()
    {
        $this->assertFalse($this->container->has('foo'));
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($expected, $id, $definition, $secondCall = null)
    {
        if ($definition) {
            $this->container->set($id, $definition);
        }

        $instance = $this->container->get($id);

        $this->assertInstanceOf($expected, $instance);

        if (!$definition || $definition->isShared()) {
            $this->assertSame($instance, $this->container->get($secondCall ?: $id));
        } else {
            $secondInstance = $this->container->get($secondCall ?: $id);

            $this->assertInstanceOf($expected, $secondInstance);
            $this->assertNotSame($instance, $secondInstance);
        }
    }

    public function testGetOverride()
    {
        $this->container->set('foo', new Definition('DateTimeZone'));

        $jakarta = $this->container->get('foo', array('Asia/Jakarta'));
        $abidjan = $this->container->get('foo', array('Africa/Abidjan'));

        $this->assertNotEquals($jakarta, $abidjan);
        $this->assertInstanceOf('DateTimeZone', $jakarta);
        $this->assertInstanceOf('DateTimeZone', $abidjan);
    }

    public function testSet()
    {
        $this->assertInstanceOf('DateTime', $this->container->set('foo', new Definition('bar', new \DateTime()))->get('foo'));
    }

    public function testHasParameter()
    {
        $this->assertFalse($this->container->hasParameter('foo'));
    }

    public function testGetParameter()
    {
        $this->assertNull($this->container->getParameter('foo'));
    }

    public function testSetParameter()
    {
        $this->assertEquals('bar', $this->container->setParameter('foo', 'bar')->getParameter('foo'));
    }

    public function testGetParameters()
    {
        $this->assertEquals(array(), $this->container->getParameters());
    }

    /**
     * @dataProvider createInstanceProvider
     */
    public function testCreateInstance($expected, $definition, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->container->createInstance($definition);

            return;
        }

        $this->assertInstanceOf($expected, $this->container->createInstance($definition));
    }

    public function testReference()
    {
        $ref = $this->container->reference('foo', false);
        $this->assertNull($ref);

        $bar = &$this->container->reference('bar');
        $bar = array('baz' => 'qux');
        $this->assertEquals('qux', $this->container->reference('bar.baz'));
        $this->assertNull($this->container->reference('bar.baz.qux'));

        $ref = &$this->container->reference('obj');
        $ref = new \stdClass();

        $ref = &$this->container->reference('obj.foo');
        $ref = 'bar';
        $set = $this->container->reference('obj.foo', false, $found);

        $this->assertEquals('bar', $set);
        $this->assertTrue($found);
    }

    public function testConstruct()
    {
        $container = new Container(array(
            'foo' => new Definition('DateTime'),
        ), array(
            'foo' => 'bar',
        ));

        $this->assertInstanceOf('DateTime', $container->get('foo'));
        $this->assertEquals('bar', $container->getParameter('foo'));
    }

    public function grabProvider()
    {
        return array(
            array(phpversion(), 'phpversion'),
            array('baz class instance name', 'Fixture\\BazClass->getInstanceName'),
            array('baz class static name', 'Fixture\\BazClass::getStaticName'),
        );
    }

    public function callProvider()
    {
        $std = new \stdClass();
        $std->foo = 'baz';

        return array(
            // usage of is parameter optional
            array('foo', 'trim', array('foo')),
            // instance callback
            array('baz class instance name', array(new BazClass(), 'getInstanceName')),
            // static callback
            array('baz class static name', array('Fixture\\BazClass', 'getStaticName')),
            array('foo bar baz qux quux', 'implode', array(' ', array('foo bar baz qux quux'))),
            array('success', function (\DateTime $datetime) {
                return 'success';
            }),
            array('success', function (\DateTime $datetime) {
                return 'success';
            }, array('DateTime')),
            array('success', function (\stdClass $std) {
                return isset($std->foo) && 'baz' === $std->foo ? 'success' : 'error';
            }, array($std)),
            array('success', function ($foo, \stdClass $std, $false = false) {
                return 'foobarbaz' === $foo && isset($std->foo) && 'bar' === $std->foo && false === $false ? 'success' : 'error';
            }, array('foo' => 'foo%foo%baz', '%std_service%')),
            array('success', function ($true = true) {
                return $true ? 'success' : 'error';
            }),
            array('success', function ($foo) {
                return 'success';
            }, array('bar' => 'foo')),
            // access std_service's foo property
            array('success', function ($foo) {
                return 'bar' === $foo ? 'success' : 'error';
            }, array('foo' => '%std_service.foo%')),
            // call BazClass->getInstanceName
            array('success', function ($foo) {
                return 'foo service' === $foo ? 'success' : 'error';
            }, array('foo' => '%Fixture\\FooService.name%')),
            // access undefined object property
            array('Cannot resolve object property (stdClass->bar).', function ($foo) {
                // do nothing, as it will throw an exception
            }, array('foo' => '%std_service.bar%'), 'LogicException'),
            // access undefined object nested property
            array('Previous part is not an object (std_service.foo).', function ($foo) {
                // do nothing, as it will throw an exception
            }, array('foo' => '%std_service.foo.bar%'), 'LogicException'),
            // access protected object property
            array('Cannot resolve private/protected object property (Fixture\\FooService->privateProperty).', function ($foo) {
                // do nothing, as it will throw an exception
            }, array('foo' => '%Fixture\\FooService.privateProperty%'), 'LogicException'),
        );
    }

    public function getProvider()
    {
        return array(
            array('Fal\\Stick\\Container\\ContainerInterface', 'container', null),
            array('Fal\\Stick\\Container\\ContainerInterface', 'Fal\\Stick\\Container\\ContainerInterface', null),
            array('Fixture\\FooService', 'Fixture\\FooService', new Definition('Fixture\\FooService')),
            array('Fixture\\FooService', 'foo', new Definition('Fixture\\FooService'), 'Fixture\\FooService'),
            array('Fixture\\FooService', 'Fixture\\FooService', new Definition('Fixture\\FooService', false)),
            array('Fixture\\FooService', 'foo', new Definition('Fixture\\FooService', false), 'Fixture\\FooService'),
        );
    }

    public function createInstanceProvider()
    {
        return array(
            array('Fixture\\FooService', new Definition('Fixture\\FooService')),
            array(
                'Fixture\\BarService',
                new Definition('bar', array(
                    'use' => 'Fixture\\BarService',
                    'arguments' => array('name' => 'bar'),
                )),
            ),
            array(
                'Fixture\\FooService',
                new Definition('Fixture\\FooService', array(
                    'boot' => function () {},
                )),
            ),
            array(
                'Fixture\\FooService',
                new Definition('Fixture\\FooService', function () {
                    return new \Fixture\FooService();
                }),
            ),
            array(
                'Factory should return instance of Fixture\\FooService (Fixture\\FooService)',
                new Definition('Fixture\\FooService', function () {
                    return new \DateTime();
                }),
                'LogicException',
            ),
            array(
                'Cannot instantiate Fixture\\FooInterface (Fixture\\FooInterface)',
                new Definition('Fixture\\FooInterface'),
                'LogicException',
            ),
        );
    }
}
