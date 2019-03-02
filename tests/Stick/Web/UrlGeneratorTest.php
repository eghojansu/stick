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

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStack;
use Fal\Stick\Web\Router\Router;
use Fal\Stick\Web\UrlGenerator;
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    private $generator;
    private $requestStack;
    private $router;

    public function setup()
    {
        $this->requestStack = new RequestStack();
        $this->requestStack->push(Request::create('/'));

        $this->router = new Router();
        $this->router->route('GET foo /foo', 'foo');

        $this->generator = new UrlGenerator($this->requestStack, $this->router, false, 'v1', array(
            'foo' => 'bar.css',
        ));
    }

    public function testGetUri()
    {
        $this->assertEquals('http://localhost/', $this->generator->getUri());
    }

    public function testGetBase()
    {
        $this->assertEquals('', $this->generator->getBase());
        $this->assertEquals('http://localhost', $this->generator->getBase(true));
    }

    /**
     * @dataProvider generateProvider
     */
    public function testGenerate($expected, $routeName, $parameters = null, $absolute = false)
    {
        $this->assertEquals($expected, $this->generator->generate($routeName, $parameters, $absolute));
    }

    /**
     * @dataProvider assetProvider
     */
    public function testAsset($expected, $path, $absolute = false)
    {
        $this->assertEquals($expected, $this->generator->asset($path, $absolute));
    }

    /**
     * @dataProvider redirectProvider
     */
    public function testRedirect($expected, $target, $permanent = false)
    {
        $response = $this->generator->redirect($target, $permanent);
        $actual = array($response->getTargetUrl(), $response->getStatusCode());

        $this->assertEquals($expected, $actual);
    }

    public function generateProvider()
    {
        return array(
            array('/foo', 'foo'),
            array('http://localhost/foo', 'foo', null, true),
        );
    }

    public function assetProvider()
    {
        return array(
            array('/bar.css?v1', 'foo'),
            array('http://localhost/bar.css?v1', 'foo', true),
        );
    }

    public function redirectProvider()
    {
        return array(
            array(array('http://localhost/', 302), null),
            array(array('http://localhost/', 301), null, true),
            array(array('http://localhost/foo', 302), 'foo'),
            array(array('http://localhost/foo', 302), array('foo')),
            array(array('http://localhost/foo?bar=baz&qux=quux', 302), 'foo(bar=baz)?qux=quux'),
            array(array('http://localhost/bar', 302), 'bar'),
            array(array('http://foo', 302), 'http://foo'),
        );
    }
}
