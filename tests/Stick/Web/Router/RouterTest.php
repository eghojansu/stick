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

namespace Fal\Stick\Test\Web\Router;

use Fal\Stick\Web\Request;
use Fal\Stick\Web\Router\RouteMatch;
use Fal\Stick\Web\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private $router;

    public function setup()
    {
        $this->router = new Router();
    }

    public function testRoute()
    {
        $this->router->route('get /foo', 'foo');
        $this->router->route('GET bar /bar sync', 'bar');
        $this->router->route('POST bar', 'bar_post');

        $expectedRoutes = array(
            '/foo' => array(
                'all' => array(
                    'GET' => 'foo',
                ),
            ),
            '/bar' => array(
                'sync' => array(
                    'GET' => 'bar',
                ),
                'all' => array(
                    'POST' => 'bar_post',
                ),
            ),
        );
        $expectedAliases = array(
            'bar' => '/bar',
        );

        $this->assertEquals($expectedRoutes, $this->router->getRoutes());
        $this->assertEquals($expectedAliases, $this->router->getAliases());
    }

    /**
     * @dataProvider routeExceptionProvider
     */
    public function testRouteException($expected, $route)
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage($expected);

        $this->router->route($route, 'foo');
    }

    public function testController()
    {
        $this->router->controller('Foo', array(
            'GET /foo' => 'bar',
        ));

        $expected = array(
            '/foo' => array(
                'all' => array(
                    'GET' => 'Foo->bar',
                ),
            ),
        );

        $this->assertEquals($expected, $this->router->getRoutes());
    }

    public function testRest()
    {
        $this->router->rest('foo /foo', 'Bar');

        $expected = array(
            '/foo' => array(
                'all' => array(
                    'GET' => 'Bar->all',
                    'POST' => 'Bar->create',
                ),
            ),
            '/foo/@item' => array(
                'all' => array(
                    'GET' => 'Bar->get',
                    'PUT' => 'Bar->update',
                    'PATCH' => 'Bar->update',
                    'DELETE' => 'Bar->delete',
                ),
            ),
        );
        $expectedAliases = array(
            'foo' => '/foo',
            'foo_item' => '/foo/@item',
        );

        $this->assertEquals($expected, $this->router->getRoutes());
        $this->assertEquals($expectedAliases, $this->router->getAliases());
    }

    /**
     * @dataProvider generateProvider
     */
    public function testGenerate($expected, $pattern, $parameters = null, $exception = null)
    {
        $this->router->route('GET foo '.$pattern, 'foo');

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->router->generate('foo', $parameters);
        } else {
            $this->assertEquals($expected, $this->router->generate('foo', $parameters));
        }
    }

    public function testGenerateFallback()
    {
        $this->assertEquals('/foo', $this->router->generate('foo'));
        $this->assertEquals('/foo?bar=baz', $this->router->generate('foo', array('bar' => 'baz')));
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($expected, $route, $request)
    {
        $this->router->route($route, 'foo');

        $this->assertEquals($expected, $this->router->handle($request)->getRouteMatch());
    }

    public function testGetRouteMatch()
    {
        $this->assertNull($this->router->getRouteMatch());
    }

    public function testRoutes()
    {
        $this->router->routes(array(
            'GET /foo' => 'bar',
        ));

        $expected = array(
            '/foo' => array(
                'all' => array(
                    'GET' => 'bar',
                ),
            ),
        );

        $this->assertEquals($expected, $this->router->getRoutes());
    }

    public function testGetPattern()
    {
        $this->assertNull($this->router->getPattern('foo'));
    }

    public function testGetRoutes()
    {
        $this->assertEquals(array(), $this->router->getRoutes());
    }

    public function testGetAliases()
    {
        $this->assertEquals(array(), $this->router->getAliases());
    }

    public function testIsCaseless()
    {
        $this->assertTrue($this->router->isCaseless());
    }

    public static function routeExceptionProvider()
    {
        return array(
            array('Invalid route: "GET".', 'GET'),
            array('Route not exists: "foo".', 'GET foo'),
        );
    }

    public function generateProvider()
    {
        return array(
            array('/', '/'),
            array('/foo', '/foo'),
            array('/foo?bar=baz', '/foo', array('bar' => 'baz')),
            array('/foo/any', '/foo/@bar', array('bar' => 'any')),
            array('/foo/any?baz=qux', '/foo/@bar', array('bar' => 'any', 'baz' => 'qux')),
            array('/foo/1', '/foo/@bar(\d+)', array('bar' => 1)),
            array('/foo/1/any', '/foo/@bar(\d+)/@baz', array('bar' => 1, 'baz' => 'any')),
            array('/foo/1/any', '/foo/@bar(\d+)/@baz', 'bar=1&baz=any'),
            array('/foo/1/any/take/all', '/foo/@bar(\d+)/@baz/@qux*', array('bar' => 1, 'baz' => 'any', 'qux' => array('take', 'all'))),
            array('Parameter "bar" should be provided.', '/foo/@bar', null, 'LogicException'),
            array('Parameter "bar" is not valid, given: \'baz\'.', '/foo/@bar(\d+)', array('bar' => 'baz'), 'LogicException'),
        );
    }

    public function handleProvider()
    {
        $provider = array();

        // standard
        $provider[] = array(
            new RouteMatch('/foo', null, array('GET'), 'foo', array()),
            'GET /foo',
            Request::create('/foo'),
        );

        // not found
        $provider[] = array(
            null,
            'GET /foo',
            Request::create('/bar'),
        );

        // forbidden
        $provider[] = array(
            null,
            'GET /foo ajax',
            Request::create('/foo'),
        );

        // with parameters
        $provider[] = array(
            new RouteMatch('/foo/@bar', 'foo', array('GET'), 'foo', array('bar' => 'baz')),
            'GET foo /foo/@bar',
            Request::create('/foo/baz'),
        );

        // with parameters eater
        $provider[] = array(
            new RouteMatch('/foo/@bar*', null, array('GET'), 'foo', array('bar' => array('baz', 'qux', '1'))),
            'GET /foo/@bar*',
            Request::create('/foo/baz/qux/1'),
        );

        return $provider;
    }
}
