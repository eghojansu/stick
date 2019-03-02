<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 27, 2019 10:29
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Cookie;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new Response();
    }

    public function testCreate()
    {
        $this->assertNotSame($this->response, Response::create());
    }

    public function testGetStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testGetStatusText()
    {
        $this->assertEquals('OK', $this->response->getStatusText());
    }

    public function testStatus()
    {
        $this->assertEquals('Forbidden', $this->response->status(403)->getStatusText());
    }

    public function testStatusException()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Unsupported HTTP code: 900');

        $this->response->status(900);
    }

    public function testGetCharset()
    {
        $this->assertEquals('UTF-8', $this->response->getCharset());
    }

    public function testSetCharset()
    {
        $this->assertEquals('foo', $this->response->setCharset('foo')->getCharset());
    }

    public function testGetContent()
    {
        $this->assertNull($this->response->getContent());
    }

    public function testSetContent()
    {
        $this->assertEquals('foo', $this->response->setContent('foo')->getContent());
        $this->assertEquals(3, $this->response->headers->first('Content-Length'));
    }

    public function testGetProtocolVersion()
    {
        $this->assertEquals('1.0', $this->response->getProtocolVersion());
    }

    public function testSetProtocolVersion()
    {
        $this->assertEquals('foo', $this->response->setProtocolVersion('foo')->getProtocolVersion());
    }

    /**
     * @dataProvider prepareProvider
     */
    public function testPrepare($expected, $content, $status, $server = null, $headers = null, $cookie = null)
    {
        if ($cookie) {
            $this->response->headers->addCookie($cookie);
        }

        if ($headers) {
            $this->response->headers->replace($headers);
        }

        $this->response->setContent($content);
        $this->response->status($status);

        $this->response->prepare(new Request(null, null, null, null, $server));

        $this->assertEquals($expected['content'], $this->response->getContent());
        $this->assertEquals($expected['length'], $this->response->headers->first('Content-Length'));
        $this->assertEquals($expected['type'], $this->response->headers->first('Content-Type'));
    }

    public function testSendHeaders()
    {
        $this->response->headers->set('foo', 'bar');
        $this->response->headers->addCookie(new Cookie('foo'));

        $this->response->sendHeaders();

        $this->assertTrue(true);
    }

    public function testSendContent()
    {
        $this->expectOutputString('');

        $this->response->sendContent();
    }

    public function testSend()
    {
        $this->expectOutputString('');

        $this->response->send();
    }

    public function testIsInvalid()
    {
        $this->assertFalse($this->response->isInvalid());
    }

    public function testIsInformational()
    {
        $this->assertFalse($this->response->isInformational());
    }

    public function testIsSuccessful()
    {
        $this->assertTrue($this->response->isSuccessful());
    }

    public function testIsRedirection()
    {
        $this->assertFalse($this->response->isRedirection());
    }

    public function testIsClientError()
    {
        $this->assertFalse($this->response->isClientError());
    }

    public function testIsServerError()
    {
        $this->assertFalse($this->response->isServerError());
    }

    public function testIsOk()
    {
        $this->assertTrue($this->response->isOk());
    }

    public function testIsForbidden()
    {
        $this->assertFalse($this->response->isForbidden());
    }

    public function testIsNotFound()
    {
        $this->assertFalse($this->response->isNotFound());
    }

    public function testIsRedirect()
    {
        $this->assertFalse($this->response->isRedirect());
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->response->isEmpty());
    }

    public function testClone()
    {
        $dup = clone $this->response;

        $this->assertNotSame($this->response->headers, $dup->headers);
    }

    public function testExpire()
    {
        $this->response->expire();

        $this->assertEquals('no-cache', $this->response->headers->first('Pragma'));
        $this->assertEquals('no-cache, no-store, must-revalidate', $this->response->headers->first('Cache-Control'));
        $this->assertTrue($this->response->headers->exists('Expires'));
        $this->assertFalse($this->response->headers->exists('Last-Modified'));

        $this->response->expire(1);

        $this->assertFalse($this->response->headers->exists('Pragma'));
        $this->assertEquals('max-age=1', $this->response->headers->first('Cache-Control'));
        $this->assertTrue($this->response->headers->exists('Last-Modified'));
    }

    public function testSetNotModified()
    {
        $this->assertEquals(304, $this->response->setNotModified()->getStatusCode());
    }

    public function prepareProvider()
    {
        return array(
            array(array(
                'content' => null,
                'length' => null,
                'type' => null,
            ), 'foo', 204),
            array(array(
                'content' => 'foo',
                'length' => 3,
                'type' => 'text/html; charset=UTF-8',
            ), 'foo', 200),
            array(array(
                'content' => 'foo',
                'length' => 3,
                'type' => 'text/foo; charset=UTF-8',
            ), 'foo', 200, null, array('Content-Type' => 'text/foo')),
            array(array(
                'content' => 'foo',
                'length' => null,
                'type' => 'text/html; charset=UTF-8',
            ), 'foo', 200, null, array('Transfer-Encoding' => 'foo')),
            array(array(
                'content' => null,
                'length' => 3,
                'type' => 'text/html; charset=UTF-8',
            ), 'foo', 200, array('REQUEST_METHOD' => 'HEAD', 'SERVER_PROTOCOL' => 'HTTP/1.0'), array('Cache-Control' => 'no-cache')),
            array(array(
                'content' => 'foo',
                'length' => 3,
                'type' => 'text/html; charset=UTF-8',
            ), 'foo', 200, array('HTTPS' => 'on'), null, new Cookie('foo')),
        );
    }
}
