<?php declare(strict_types=1);

namespace Fal\Stick\Test\Unit;

use Fal\Stick\Helper;
use Fal\Stick\Request;
use Fal\Stick\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private $response;
    private $request;

    public function setUp()
    {
        $helper = new Helper();
        $this->request = new Request($helper);
        $this->response = new Response($this->request, $helper);
    }

    public function tearDown()
    {
        header_remove();
    }

    public function testStatus()
    {
        $this->assertEquals('Not Found', $this->response->status(404)->getStatusText());
    }

    public function testGetStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testGetStatusText()
    {
        $this->assertEquals('OK', $this->response->getStatusText());
    }

    public function testGetHeader()
    {
        $this->response->setHeader('Location', '/foo');
        $this->assertEquals('/foo', $this->response->getHeader('Location'));
    }

    public function testRemoveHeader()
    {
        $this->response->setHeader('Location', '/foo');
        $this->assertEquals('/foo', $this->response->getHeader('Location'));
        $this->response->removeHeader('Location');
        $this->assertEmpty($this->response->getHeaders());

        $this->response->setHeader('Location', '/foo');
        $this->assertEquals('/foo', $this->response->getHeader('location'));
        $this->response->removeHeader();
        $this->assertEmpty($this->response->getHeaders());
    }

    public function testGetHeaders()
    {
        $this->assertEquals([], $this->response->getHeaders());
    }

    public function testSetHeaders()
    {
        $this->response->setHeaders(['Location'=>'/foo','Content-Length: 0']);
        $this->assertEquals('/foo', $this->response->getHeader('Location'));
        $this->assertEquals('0', $this->response->getHeader('Content-Length'));
    }

    public function testSetHeader()
    {
        $this->response->setHeader('Location', '/foo');
        $this->assertEquals('/foo', $this->response->getHeader('Location'));
    }

    public function testGetCookies()
    {
        $this->assertEquals([], $this->response->getCookies());
    }

    public function testSetCookie()
    {
        $this->response->setCookie('foo', 'bar');

        $this->assertEquals([['foo','bar',0]], $this->response->getCookies());
    }

    public function testSendHeader()
    {
        $this->request['CLI'] = false;
        $this->response->setHeader('Location', '/foo');
        $this->response->setCookie('foo', 'bar', 5);
        $this->response->setCookie('foo', 'baz');
        $this->response->sendHeader();

        $this->assertEquals('/foo', $this->response->getHeader('location'));
        $this->assertContains(['foo','bar',5], $this->response->getCookies());
        $this->assertContains(['foo','baz',0], $this->response->getCookies());

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertNotEmpty(preg_grep('~^Location: /foo~', $headers));
            $this->assertNotEmpty(preg_grep('~^Set-Cookie: foo=baz~', $headers));
        }
    }

    public function testSendContent()
    {
        $this->expectOutputString('foo');

        $this->request['CLI'] = false;
        $this->response->html('foo');
        $this->response->sendContent();

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertEmpty(preg_grep('~^Content-Type: text/html~', $headers));
        }
    }

    public function testSendContentWithThrottle()
    {
        $this->expectOutputString('foo');

        $this->request['CLI'] = false;
        $this->response->html('foo');
        $this->response->sendContent(1);

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertEmpty(preg_grep('~^Content-Type: text/html~', $headers));
        }
    }

    public function testSend()
    {
        $this->expectOutputString('foo');

        $this->request['CLI'] = false;
        $this->response->html('foo');
        $this->response->send();

        $this->assertEquals('text/html;charset=' . ini_get('default_charset'), $this->response->getHeader('Content-Type'));

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertNotEmpty(preg_grep('~^Content-Type: text/html~', $headers));
        }
    }

    public function testHtml()
    {
        $this->response->html('foo');
        $this->assertEquals('text/html;charset=' . ini_get('default_charset'), $this->response->getHeader('Content-Type'));
        $this->assertEquals('3', $this->response->getHeader('Content-Length'));

        $this->assertEquals('foo', $this->response->getBody());
    }

    public function testJson()
    {
        $this->response->json(['foo'=>'bar']);
        $this->assertEquals('application/json;charset=' . ini_get('default_charset'), $this->response->getHeader('Content-Type'));
        $this->assertEquals('13', $this->response->getHeader('Content-Length'));

        $this->assertEquals('{"foo":"bar"}', $this->response->getBody());
    }

    public function testThrottle()
    {
        $onemb = 'wait one second';
        $output = $this->response->throttle($onemb, 1);

        $this->expectOutputString($onemb);
        $start = microtime(true);
        $output();
        $end = microtime(true) - $start;

        $this->assertGreaterThan(1, $end);
    }

    public function testSetOutput()
    {
        $this->assertEquals($this->response, $this->response->setOutput(function() {}));
    }

    public function testGetOutput()
    {
        $this->assertNull($this->response->getOutput());
    }

    public function testGetHeadersWithoutCookie()
    {
        $this->response->setHeaders([
            'Location: /foo',
            'Set-Cookie: foo=bar',
        ]);
        $this->assertEquals(['Location: /foo'], $this->response->getHeadersWithoutCookie());
    }

    public function testGetBody()
    {
        $this->assertEquals('', $this->response->getBody());
    }

    public function testSetBody()
    {
        $this->assertEquals('foo', $this->response->setBody('foo')->getBody());

        $this->expectOutputString('foo');
        $this->response->send();
    }

    public function testClearOutput()
    {
        $this->response->html('foo');
        $this->assertEquals('foo', $this->response->getBody());
        $this->assertEquals('', $this->response->clearOutput()->getBody());
    }
}
