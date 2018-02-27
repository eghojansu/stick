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

use Fal\Stick as f;
use Fal\Stick\Test\fixture\CommonClass;
use PHPUnit\Framework\Testcase;

class FunctionsTest extends Testcase
{
    public function tearDown()
    {
        if (file_exists($file = TEMP . 'foo.txt')) {
            unlink($file);
        }
        if (file_exists($dir = TEMP . 'mktest')) {
            rmdir($dir);
        }
    }

    public function testStringify()
    {
        $expected = "['foo'=>'bar']";
        $arr = ['foo'=>'bar'];
        $result = f\stringify($arr);
        $this->assertEquals($expected, $result);

        $obj = new \stdClass;
        $obj->name = 'foo';
        $expected = "stdClass::__set_state(['name'=>'foo'])";
        $result = f\stringify($obj);
        $this->assertEquals($expected, $result);
    }

    public function testContexttostring()
    {
        $expected = "foo: 'bar'";
        $context = ['foo'=>'bar'];
        $result = f\contexttostring($context);
        $this->assertEquals($expected, $result);
    }

    public function testCamelcase()
    {
        $expected = 'fooBar';
        $str = 'foo_bar';
        $result = f\camelcase($str);
        $this->assertEquals($expected, $result);
    }

    public function testSnakecase()
    {
        $expected = 'foo_bar';
        $str = 'fooBar';
        $result = f\snakecase($str);
        $this->assertEquals($expected, $result);
    }

    public function testDashcase()
    {
        $expected = 'Foo-Bar';
        $str = 'foo_bar';
        $result = f\dashcase($str);
        $this->assertEquals($expected, $result);

        $str = 'FOO_BAR';
        $result = f\dashcase($str);
        $this->assertEquals($expected, $result);
    }

    public function testFixslashes()
    {
        $expected = 'foo/bar';
        $str = 'foo\\bar';
        $result = f\fixslashes($str);
        $this->assertEquals($expected, $result);
    }

    public function testRead()
    {
        $expected = 'foo';
        $file = FIXTURE . 'foo.txt';
        $result = f\read($file);
        $this->assertEquals($expected, $result);
    }

    public function testWrite()
    {
        $expected = 3;
        $file = TEMP . 'foo.txt';
        $data = 'foo';
        $result = f\write($file, $data);
        $this->assertEquals($expected, $result);
    }

    public function testDelete()
    {
        $file = TEMP . 'todelete.txt';
        $this->assertFalse(f\delete($file));
        touch($file);
        $this->assertFileExists($file);
        $this->assertTrue(f\delete($file));
        $this->assertFileNotExists($file);
    }

    public function testSplit()
    {
        $expected = ['foo','bar'];
        $str = 'foo|bar||';
        $noempty = TRUE;
        $result = f\split($str, $noempty);
        $this->assertEquals($expected, $result);
    }

    public function testExtract()
    {
        $expected = ['foo'=>'bar', 'bar'=>'baz'];
        $arr = ['foo_foo'=>'bar', 'foo_bar'=>'baz', 'bar_baz'=>'qux'];
        $prefix = 'foo_';
        $result = f\extract($arr, $prefix);
        $this->assertEquals($expected, $result);
    }

    public function testConstants()
    {
        $expected = ['FOO'=>'bar'];
        $class = CommonClass::class;
        $prefix = 'PREFIX_';
        $result = f\constants($class, $prefix);
        $this->assertEquals($expected, $result);
    }

    public function testHash()
    {
        $expected = '1xnmsgr3l2f5f';
        $str = 'foo';
        $result = f\hash($str);
        $this->assertEquals($expected, $result);
    }

    public function testBase64()
    {
        $expected = 'data:text/plain;base64,Zm9v';
        $data = 'foo';
        $mime = 'text/plain';
        $result = f\base64($data, $mime);
        $this->assertEquals($expected, $result);
    }

    public function testMkdir()
    {
        $path = TEMP.'mktest';
        $result = f\mkdir($path);
        $this->assertTrue($result);
        $this->assertTrue(is_dir($path));

        $result = f\mkdir($path);
        $this->assertTrue($result);
    }

    public function testReqarr()
    {
        $this->assertEquals(['foo','bar'], f\reqarr(['foo','bar']));
        $this->assertEquals(['foo','bar'], f\reqarr('foo,bar'));
    }

    public function testReqstr()
    {
        $this->assertEquals('foo,bar', f\reqstr(['foo','bar']));
        $this->assertEquals('foo,bar', f\reqstr('foo,bar'));
    }

    public function testConstant()
    {
        $this->assertEquals('bar', f\constant(CommonClass::class . '::PREFIX_FOO'));
    }

    public function testQuoteall()
    {
        $src = ['foo','bar'];
        $this->assertEquals($src, f\quoteall($src));
        $this->assertEquals([':foo:',':bar:'], f\quoteall($src, [':',':']));
        $this->assertEquals([':foo:',':bar:',':qux,:'], f\quoteall($src+[2=>'qux,'], [':',':'], '|'));
        $this->assertEquals([':u_foo',':u_bar'], f\quoteall($src, [':u_']));
        $this->assertEquals(['foo_u:','bar_u:'], f\quoteall($src, ['','_u:']));
    }

    public function testQuotekey()
    {
        $src = ['foo'=>'bar','bar'=>'baz'];
        $this->assertEquals($src, f\quotekey($src));
        $this->assertEquals([':foo:'=>'bar',':bar:'=>'baz'], f\quotekey($src, [':',':']));
        $this->assertEquals([':foo:'=>'bar',':bar:'=>'baz',':qux,:'=>'quux'], f\quotekey($src+['qux,'=>'quux'], [':',':'], '|'));
        $this->assertEquals([':u_foo'=>'bar',':u_bar'=>'baz'], f\quotekey($src, [':u_']));
        $this->assertEquals(['foo_u:'=>'bar','bar_u:'=>'baz'], f\quotekey($src, ['','_u:']));
    }

    public function testStartswith()
    {
        $this->assertTrue(f\startswith('foo', 'foobar'));
        $this->assertFalse(f\startswith('bar', 'foobar'));
        $this->assertFalse(f\startswith('FOO', 'foobar'));
    }

    public function testIstartswith()
    {
        $this->assertTrue(f\istartswith('foo', 'foobar'));
        $this->assertFalse(f\istartswith('bar', 'foobar'));
        $this->assertTrue(f\istartswith('FOO', 'foobar'));
    }

    public function testEndswith()
    {
        $this->assertTrue(f\endswith('bar', 'foobar'));
        $this->assertFalse(f\endswith('foo', 'foobar'));
        $this->assertFalse(f\endswith('BAR', 'foobar'));
    }

    public function testIendswith()
    {
        $this->assertTrue(f\iendswith('bar', 'foobar'));
        $this->assertFalse(f\iendswith('foo', 'foobar'));
        $this->assertTrue(f\iendswith('BAR', 'foobar'));
    }

    public function testCutafter()
    {
        $this->assertEquals('bar', f\cutafter('foo', 'foobar'));
        $this->assertEquals('', f\cutafter('bar', 'foobar'));
        $this->assertEquals('', f\cutafter('FOO', 'foobar'));
    }

    public function testIcutafter()
    {
        $this->assertEquals('bar', f\icutafter('foo', 'foobar'));
        $this->assertEquals('', f\icutafter('bar', 'foobar'));
        $this->assertEquals('bar', f\icutafter('FOO', 'foobar'));
    }

    public function testCutbefore()
    {
        $this->assertEquals('foo', f\cutbefore('bar', 'foobar'));
        $this->assertEquals('', f\cutbefore('foo', 'foobar'));
        $this->assertEquals('', f\cutbefore('BAR', 'foobar'));
    }

    public function testICutbefore()
    {
        $this->assertEquals('foo', f\iCutbefore('bar', 'foobar'));
        $this->assertEquals('', f\iCutbefore('foo', 'foobar'));
        $this->assertEquals('foo', f\iCutbefore('BAR', 'foobar'));
    }

    public function testCast()
    {
        $this->assertEquals(0, f\cast('0'));
        $this->assertEquals(TRUE, f\cast('TRUE'));
        $this->assertEquals(FALSE, f\cast('FALSE'));
        $this->assertEquals(NULL, f\cast('NULL'));
        $this->assertEquals(0.1, f\cast('0.1'));
        $this->assertEquals('foo', f\cast('foo'));
    }
}
