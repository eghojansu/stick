<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 23:35
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private $request;

    public function setup()
    {
        $this->request = new Request();
    }

    public function testIsAjax()
    {
        $this->assertFalse($this->request->isAjax());

        $this->request(array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'));
        $this->assertTrue($this->request->isAjax());
    }

    /**
     * @dataProvider getIpProvider
     */
    public function testGetIp($expected, $server)
    {
        $this->request($server);
        $this->assertEquals($expected, $this->request->getIp());
    }

    public function testGetUserAgent()
    {
        $this->assertEquals('Fal/Stick', $this->request->getUserAgent());
    }

    /**
     * @dataProvider getMethodProvider
     */
    public function testGetMethod($expected, $server, $request = null)
    {
        $this->request($server, $request);
        $this->assertEquals($expected, $this->request->getMethod());
    }

    /**
     * @dataProvider isSecureProvider
     */
    public function testIsSecure($expected, $server)
    {
        $this->request($server);
        $this->assertEquals($expected, $this->request->isSecure());
    }

    public function testGetScheme()
    {
        $this->assertEquals('http', $this->request->getScheme());
    }

    public function testGetHost()
    {
        $this->assertEquals('localhost', $this->request->getHost());
    }

    public function testGetPort()
    {
        $this->assertEquals(80, $this->request->getPort());
    }

    public function testGetScript()
    {
        $this->assertEquals('', $this->request->getScript());
    }

    public function testGetFront()
    {
        $this->assertEquals('', $this->request->getFront());
    }

    public function testGetBase()
    {
        $this->assertEquals('', $this->request->getBase());

        $this->request(null);
        $this->assertEquals('', $this->request->getBase());
    }

    public function testGetPath()
    {
        $this->assertEquals('/', $this->request->getPath());
    }

    public function testSetPath()
    {
        $this->assertEquals('/foo', $this->request->setPath('/foo?bar=baz')->getPath());
        $this->assertEquals('/foo?bar=baz', $this->request->server->get('REQUEST_URI'));
        $this->assertEquals('bar=baz', $this->request->server->get('QUERY_STRING'));
        $this->assertEquals(array('bar' => 'baz'), $this->request->query->all());
    }

    public function testGetHash()
    {
        $this->assertEquals('3ggko0xij7qc4', $this->request->getHash());
    }

    /**
     * @dataProvider getContentProvider
     */
    public function testGetContent($expected, $server, $asResource = false, $content = null)
    {
        $this->request($server, null, $content);

        $content = $this->request->getContent($asResource);
        $actual = $asResource ? stream_get_contents($content) : $content;

        $this->assertEquals($expected, $actual);
    }

    public function testCreateFromGlobals()
    {
        $server = $_SERVER;
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $this->assertEquals(array(), Request::createFromGlobals()->request->all());

        $_SERVER = $server;
    }

    public function testIsMethod()
    {
        $this->assertTrue($this->request->isMethod('GET'));
    }

    public function testDuplicate()
    {
        $query = array('foo' => 'bar');
        $request = array('foo' => 'bar');
        $cookies = array('foo' => 'bar');
        $files = array();
        $server = array('REQUEST_METHOD' => 'post');

        $dup = $this->request->duplicate($query, $request, $cookies, $files, $server);

        $this->assertEquals('bar', $dup->query->get('foo'));
        $this->assertEquals('bar', $dup->request->get('foo'));
        $this->assertEquals('bar', $dup->cookies->get('foo'));
        $this->assertEquals('POST', $dup->getMethod());
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($expected, $uri, $method = null)
    {
        $request = Request::create($uri, $method);
        $actual = array_intersect_key($expected, $request->server->all());

        $this->assertEquals($expected, $actual);
    }

    public function testIsMethodCacheable()
    {
        $this->assertTrue($this->request->isMethodCacheable());
    }

    /**
     * @dataProvider getModeProvider
     */
    public function testGetMode($expected, $server)
    {
        $this->request($server);

        $this->assertEquals($expected, $this->request->getMode());
    }

    public function testGetUser()
    {
        $this->assertNull($this->request->getUser());
    }

    public function testGetPassword()
    {
        $this->assertNull($this->request->getPassword());
    }

    public function testGetUserInfo()
    {
        $this->request(array(
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
        ));

        $this->assertEquals('foo:bar', $this->request->getUserInfo());
    }

    public function testGetHttpHost()
    {
        $this->assertEquals('localhost', $this->request->getHttpHost());

        $this->request(array(
            'SERVER_PORT' => 8080,
        ));

        $this->assertEquals('localhost:8080', $this->request->getHttpHost());
    }

    public function testGetSchemeAndHttpHost()
    {
        $this->assertEquals('http://localhost', $this->request->getSchemeAndHttpHost());
    }

    public function testGetRequestUri()
    {
        $this->request(array(
            'REQUEST_URI' => '/foo?bar',
        ));

        $this->assertEquals('/foo?bar', $this->request->getRequestUri());
    }

    public function testGetQueryString()
    {
        $this->assertNull($this->request->getQueryString());
    }

    public function testGetBaseUrl()
    {
        $this->assertEquals('http://localhost', $this->request->getBaseUrl());
    }

    public function testGetUri()
    {
        $this->request(array(
            'REQUEST_URI' => '/foo?bar',
            'QUERY_STRING' => 'bar',
        ));

        $this->assertEquals('http://localhost/foo?bar', $this->request->getUri());
    }

    public function getIpProvider()
    {
        return array(
            array('::1', null),
            array('foo', array('HTTP_X_CLIENT_IP' => 'foo')),
            array('foo', array('HTTP_X_FORWARDED_FOR' => 'foo')),
            array('foo', array('REMOTE_ADDR' => 'foo')),
            array('foo', array('SERVER_ADDR' => 'foo')),
        );
    }

    public function getMethodProvider()
    {
        return array(
            array('GET', null),
            array('FOO', array('HTTP_X_HTTP_METHOD_OVERRIDE' => 'foo')),
            array('FOO', array('REQUEST_METHOD' => 'POST'), array('_method' => 'foo')),
        );
    }

    public function isSecureProvider()
    {
        return array(
            array(false, null),
            array(true, array('HTTP_X_FORWARDED_PROTO' => 'https')),
            array(true, array('HTTPS' => 'on')),
        );
    }

    public function getContentProvider()
    {
        return array(
            array('', null),
            array('foo', null, false, fopen(TEST_FIXTURE.'files/foo.txt', 'r')),
            array('foo', null, true, 'foo'),
            array('', null, true),
            array('foo', null, true, fopen(TEST_FIXTURE.'files/foo.txt', 'r')),
        );
    }

    public function createProvider()
    {
        return array(
            array(array(
                'SERVER_NAME' => 'qux.quux',
                'SERVER_PORT' => '8080',
                'HTTP_HOST' => 'qux.quux:8080',
                'PHP_AUTH_USER' => 'foo',
                'PHP_AUTH_PW' => 'bar',
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/?quuz=corge',
                'QUERY_STRING' => 'quuz=corge',
            ), 'http://foo:bar@qux.quux:8080?quuz=corge'),
            array(array(
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => 443,
                'HTTP_HOST' => 'localhost',
                'REQUEST_METHOD' => 'PATCH',
                'HTTPS' => 'on',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ), 'https://localhost', 'post'),
        );
    }

    public function getModeProvider()
    {
        return array(
            array('ajax', array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest')),
            array('sync', null),
        );
    }

    private function request($server, $request = null, $content = null)
    {
        $this->request = new Request(null, $request, null, null, $server, $content);
    }
}
