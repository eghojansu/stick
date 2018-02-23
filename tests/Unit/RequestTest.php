<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick\Helper;
use Fal\Stick\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private $request;
    private $init;

    public function setUp()
    {
        $_SERVER['CONTENT_LENGTH'] = 0;
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        $_SERVER['argc'] = 1;
        $_SERVER['argv'] = [$_SERVER['argv'][0]];

        $this->request = new Request(new Helper());
        $this->init = [
            '_GET'     => $_GET,
            '_POST'    => $_POST,
            '_COOKIE'  => [],
            '_FILES'   => $_FILES,
            '_ENV'     => $_ENV,
            '_REQUEST' => $_REQUEST,
            '_SERVER'  => $_SERVER,
        ];
    }

    public function tearDown()
    {
        foreach ($this->init as $key => $value) {
            $GLOBALS[$key] = $value;
        }
        header_remove();
    }

    public function globalProvider()
    {
        return [
            ['GET'],
            ['POST'],
            ['COOKIE'],
            ['FILES'],
            ['ENV'],
            ['REQUEST'],
            ['SERVER'],
        ];
    }

    public function testConstructInCliMode()
    {
        $_SERVER['argv'] = [$_SERVER['argv'][0]];
        $_SERVER['argv'][] = 'foo';
        $_SERVER['argv'][] = 'bar';
        $_SERVER['argv'][] = '-opt';
        $_SERVER['argv'][] = '-uvw=baz';
        $_SERVER['argv'][] = '--qux=quux';
        $_SERVER['argc'] = 6;
        $request = new Request(new Helper());

        $this->assertEquals('/foo/bar', $request['PATH']);
        $this->assertEquals('o=&p=&t=&u=&v=&w=baz&qux=quux', $request['QUERY']);
    }

    /** @dataProvider globalProvider */
    public function testOffsetExists($global)
    {
        $this->assertTrue(isset($this->request[$global]));
        $this->assertFalse(isset($this->request["$global.foo"]));
        $var = '_' . $global;
        $GLOBALS[$var]['foo'] = 'bar';
        $this->assertTrue(isset($this->request["$global.foo"]));
    }

    /** @dataProvider globalProvider */
    public function testOffsetGet($global)
    {
        $var = '_' . $global;
        $this->assertEquals($GLOBALS[$var], $this->request[$global]);
        $this->assertNull($this->request["$global.foo"]);
        $GLOBALS[$var]['foo'] = 'bar';
        $this->assertEquals('bar', $this->request["$global.foo"]);
    }

    /** @dataProvider globalProvider */
    public function testOffsetSet($global)
    {
        $var = '_' . $global;
        $this->request["$global.foo"] = 'bar';
        $this->assertEquals('bar', $this->request["$global.foo"]);

        $this->request["$global.bar.baz"] = 'qux';
        $this->assertEquals('qux', $this->request["$global.bar.baz"]);

        $this->request["JAR.secure"] = false;
        $this->assertEquals(false, $this->request["JAR.secure"]);
    }

    public function testOffsetSetUriMethod()
    {
        // URI and VERB
        $this->request['URI'] = '/foo';
        $this->request['METHOD'] = 'FOO';
        $this->assertEquals('/foo', $_SERVER['REQUEST_URI']);
        $this->assertEquals('FOO', $_SERVER['REQUEST_METHOD']);
    }

    public function testOffsetSetNonExists()
    {
        $this->assertFalse(isset($this->request['foo.bar']));
        $this->request['foo.bar'] = 'baz';
        $this->assertTrue(isset($this->request['foo.bar']));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Object value is not allowed
     */
    public function testOffsetSetObject()
    {
        $this->request['foo'] = new \StdClass;
    }

    /** @dataProvider globalProvider */
    public function testOffsetUnset($global)
    {
        $var = '_' . $global;
        $GLOBALS[$var]['foo'] = 'bar';

        $this->assertTrue(isset($this->request["$global.foo"]));
        unset($this->request["$global.foo"]);
        $this->assertFalse(isset($this->request["$global.foo"]));
    }

    public function testOffsetUnsetReset()
    {
        $this->assertTrue(isset($this->request['URI']));

        $init = $this->request['URI'];

        // change
        $this->request['URI'] = '/foo';
        $this->assertNotEquals($init, $this->request['URI']);

        unset($this->request['URI']);
        $this->assertEquals($init, $this->request['URI']);
    }

    public function testOffsetUnsetNonExists()
    {
        $this->assertFalse(isset($this->request['foo']));
        unset($this->request['foo.bar']);
        $this->assertFalse(isset($this->request['foo']));
    }

    public function testAgent()
    {
        $this->request['HEADERS.User-Agent'] = 'User-Agent';
        $this->assertEquals('User-Agent', $this->request->agent());
        unset($this->request['HEADERS.User-Agent']);

        $this->request['HEADERS.X-Operamini-Phone-Ua'] = 'X-Operamini-Phone-Ua';
        $this->assertEquals('X-Operamini-Phone-Ua', $this->request->agent());
        unset($this->request['HEADERS.X-Operamini-Phone-Ua']);

        $this->request['HEADERS.X-Skyfire-Phone'] = 'X-Skyfire-Phone';
        $this->assertEquals('X-Skyfire-Phone', $this->request->agent());
    }

    public function testAjax()
    {
        $this->assertFalse($this->request->ajax());
        $this->request['HEADERS.X-Requested-With'] = 'xmlhttprequest';
        $this->assertTrue($this->request->ajax());
    }

    public function testIp()
    {
        $this->request['HEADERS.Client-Ip'] = 'Client-Ip';
        $this->assertEquals('Client-Ip', $this->request->ip());
        unset($this->request['HEADERS.Client-Ip']);

        $this->request['HEADERS.X-Forwarded-For'] = 'X-Forwarded-For';
        $this->assertEquals('X-Forwarded-For', $this->request->ip());
        unset($this->request['HEADERS.X-Forwarded-For']);

        $this->request['SERVER.REMOTE_ADDR'] = 'REMOTE_ADDR';
        $this->assertEquals('REMOTE_ADDR', $this->request->ip());
    }

    public function testData()
    {
        $this->request['foo'] = 'bar';
        $this->assertContains('bar', $this->request->data());
    }

    public function testSessionAccess()
    {
        $this->request['SESSION.foo'] = 'bar';

        $this->assertEquals('bar', $this->request['SESSION.foo']);

        $this->assertTrue(isset($this->request['SESSION.foo']));
        unset($this->request['SESSION.foo']);
        $this->assertFalse(isset($this->request['SESSION.foo']));

        $this->request['SESSION.bar.baz'] = 'qux';

        $this->assertEquals('qux', $this->request['SESSION.bar.baz']);
        unset($this->request['SESSION']);
        $this->assertFalse(isset($this->request['SESSION.bar.baz']));
    }
}
