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

    public function testContextToString()
    {
        $expected = "foo: 'bar'";
        $context = ['foo'=>'bar'];
        $result = f\contextToString($context);
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
        $noempty = true;
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
        $expected = true;
        $path = TEMP.'mktest';
        $result = f\mkdir($path);
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, is_dir($path));

        $result = f\mkdir($path);
        $this->assertEquals($expected, $result);
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
}
