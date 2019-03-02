<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 27, 2019 08:58
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Cookie;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    private $cookie;

    public function setup()
    {
        $this->cookie = new Cookie('foo');
    }

    public function testGetName()
    {
        $this->assertEquals('foo', $this->cookie->getName());
    }

    public function testGetValue()
    {
        $this->assertNull($this->cookie->getValue());
    }

    public function testGetDomain()
    {
        $this->assertNull($this->cookie->getDomain());
    }

    public function testGetExpiresTime()
    {
        $this->assertEquals(0, $this->cookie->getExpiresTime());
    }

    public function testGetMaxAge()
    {
        $this->assertEquals(0, $this->cookie->getMaxAge());
    }

    public function testGetPath()
    {
        $this->assertEquals('/', $this->cookie->getPath());
    }

    public function testIsSecure()
    {
        $this->assertFalse($this->cookie->isSecure());
    }

    public function testIsHttpOnly()
    {
        $this->assertTrue($this->cookie->isHttpOnly());
    }

    public function testIsCleared()
    {
        $this->assertFalse($this->cookie->isCleared());
    }

    public function testIsRaw()
    {
        $this->assertFalse($this->cookie->isRaw());
    }

    public function testGetSameSite()
    {
        $this->assertNull($this->cookie->getSameSite());
    }

    public function testSetSecureDefault()
    {
        $this->assertTrue($this->cookie->setSecureDefault(true)->isSecure());
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString($expected, $cookie)
    {
        $this->assertRegExp($expected, $cookie->toString());
    }

    public function testCreate()
    {
        $this->assertEquals('lax', Cookie::create('foo')->getSameSite());
    }

    public function testConstruct()
    {
        $now = new \DateTime();

        $this->assertEquals($now->format('U'), Cookie::create('foo', null, $now)->getExpiresTime());
        $this->assertEquals(0, Cookie::create('foo', null, -1)->getExpiresTime());
        $this->assertGreaterThanOrEqual(strtotime('now'), Cookie::create('foo', null, 'now')->getExpiresTime());
    }

    /**
     * @dataProvider constructExceptionProvider
     */
    public function testConstructException($expected, array $arguments)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage($expected);

        new Cookie(...$arguments);
    }

    public function testFromString()
    {
        $cookie = Cookie::create('foo', 'bar', 'now');
        $text = $cookie->toString();
        $actual = Cookie::fromString($text, true);

        $this->assertEquals($cookie, $actual);
    }

    public function toStringProvider()
    {
        return array(
            array('/^foo%5Bfoo%5D=deleted; expires=[^;]+; Max-Age=0; path=\/; httponly$/', new Cookie('foo[foo]')),
            array('/^foo\[foo\]=deleted; expires=[^;]+; Max-Age=0; path=\/; httponly$/', new Cookie('foo[foo]', null, null, null, null, null, null, true)),
            array('/^foo%5Bfoo%5D=bar%20baz; expires=[^;]+; Max-Age=\d+; path=\/path; domain=domain; secure; httponly; samesite=lax$/', new Cookie('foo[foo]', 'bar baz', 1, '/path', 'domain', true, null, null, 'lax')),
        );
    }

    public function constructExceptionProvider()
    {
        return array(
            array('The cookie name cannot be empty', array('')),
            array('The cookie name "foo=" contains invalid characters', array(
                'foo=',
            )),
            array('The cookie expiration time is not valid', array(
                'foo', null, 'foo',
            )),
            array('The "sameSite" parameter value is not valid', array(
                'foo', null, null, null, null, null, null, null, 'foo',
            )),
        );
    }
}
