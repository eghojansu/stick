<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\Helper;
use Fal\Stick\Test\fixture\helper\ConstantPool;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testQuote()
    {
        $src = ['foo', 'bar'];
        $this->assertEquals('foobar', Helper::quote('foobar'));
        $this->assertEquals('foobar', Helper::quote($src));
        $this->assertEquals(':foo:,:bar:', Helper::quote($src, [':', ':'], ','));
        $this->assertEquals(':foo:|:bar:|:qux,:', Helper::quote($src + [2 => 'qux,'], [':', ':'], '|'));
        $this->assertEquals(':u_foo,:u_bar', Helper::quote($src, [':u_'], ','));
        $this->assertEquals('foo_u:,bar_u:', Helper::quote($src, ['', '_u:'], ','));
    }

    public function testInterpolate()
    {
        $this->assertEquals('foo', Helper::interpolate('foo'));
        $this->assertEquals('foo bar', Helper::interpolate('foo baz', ['baz' => 'bar']));
        $this->assertEquals('foo baz', Helper::interpolate('foo baz', ['bar' => 'bar']));
        $this->assertEquals('foo bar', Helper::interpolate('foo {baz}', ['baz' => 'bar'], '{}'));
        $this->assertEquals('foo bar', Helper::interpolate('foo {baz', ['baz' => 'bar'], '{'));
    }

    public function testClassname()
    {
        $this->assertEquals('HelperTest', Helper::classname($this));
        $this->assertEquals('HelperTest', Helper::classname(self::class));
    }

    public function testCast()
    {
        $this->assertEquals(0, Helper::cast('0'));
        $this->assertEquals(true, Helper::cast('true'));
        $this->assertEquals(false, Helper::cast('false'));
        $this->assertEquals(null, Helper::cast('null'));
        $this->assertEquals(0.1, Helper::cast('0.1'));
        $this->assertEquals('foo', Helper::cast('foo'));
    }

    public function testCamelcase()
    {
        $this->assertEquals('camelCase', Helper::camelcase('camel_case'));
        $this->assertEquals('camelCase', Helper::camelcase('CamelCase'));
        $this->assertEquals('longRingLongIsland', Helper::camelcase('long_ring_long_island'));
    }

    public function testSnakecase()
    {
        $this->assertEquals('snake_case', Helper::snakecase('SnakeCase'));
        $this->assertEquals('snake_case', Helper::snakecase('snakeCase'));
        $this->assertEquals('long_ring_long_island', Helper::snakecase('longRingLongIsland'));
    }

    public function testToHKey()
    {
        $this->assertEquals('Allow', Helper::toHKey('ALLOW'));
        $this->assertEquals('Content-Type', Helper::toHKey('CONTENT_TYPE'));
    }

    public function testFromHKey()
    {
        $this->assertEquals('ALLOW', Helper::fromHKey('Allow'));
        $this->assertEquals('CONTENT_TYPE', Helper::fromHKey('Content-Type'));
    }

    public function testFixslashes()
    {
        $this->assertEquals('/root', Helper::fixslashes('\\root'));
        $this->assertEquals('/root/path', Helper::fixslashes('\\root\\path'));
    }

    public function testHash()
    {
        $one = Helper::hash('foo');
        $two = Helper::hash('foobar');
        $three = Helper::hash('foobarbaz');

        $this->assertEquals(13, strlen($one));
        $this->assertEquals(13, strlen($two));
        $this->assertEquals(13, strlen($three));
    }

    public function testStartswith()
    {
        $this->assertTrue(Helper::startswith('foo', 'foo'));
        $this->assertTrue(Helper::startswith('foobar', 'foo'));
        $this->assertFalse(Helper::startswith('bar', 'foo'));
        $this->assertFalse(Helper::startswith('FOO', 'foo'));
    }

    public function testIstartswith()
    {
        $this->assertTrue(Helper::istartswith('foo', 'foo'));
        $this->assertTrue(Helper::istartswith('foobar', 'foo'));
        $this->assertFalse(Helper::istartswith('bar', 'foo'));
        $this->assertTrue(Helper::istartswith('FOO', 'foo'));
        $this->assertTrue(Helper::istartswith('foo', 'FOO'));
    }

    public function testEndswith()
    {
        $this->assertTrue(Helper::endswith('foo', 'foo'));
        $this->assertTrue(Helper::endswith('barfoo', 'foo'));
        $this->assertFalse(Helper::endswith('bar', 'foo'));
        $this->assertFalse(Helper::endswith('FOO', 'foo'));
    }

    public function testIendswith()
    {
        $this->assertTrue(Helper::iendswith('foo', 'foo'));
        $this->assertTrue(Helper::iendswith('barfoo', 'foo'));
        $this->assertFalse(Helper::iendswith('bar', 'foo'));
        $this->assertTrue(Helper::iendswith('foo', 'FOO'));
        $this->assertTrue(Helper::iendswith('FOO', 'foo'));
    }

    public function testCutafter()
    {
        $this->assertEquals('', Helper::cutafter('foo', 'foo'));
        $this->assertEquals('bar', Helper::cutafter('foobar', 'foo'));
        $this->assertEquals('def', Helper::cutafter('bar', 'foo', 'def'));
    }

    public function testIcutafter()
    {
        $this->assertEquals('', Helper::icutafter('foo', 'foo'));
        $this->assertEquals('BAR', Helper::icutafter('FOOBAR', 'foo'));
        $this->assertEquals('def', Helper::icutafter('bar', 'foo', 'def'));
    }

    public function testCutbefore()
    {
        $this->assertEquals('', Helper::cutbefore('bar', 'bar'));
        $this->assertEquals('foo', Helper::cutbefore('foobar', 'bar'));
        $this->assertEquals('def', Helper::cutbefore('foo', 'bar', 'def'));
    }

    public function testIcutbefore()
    {
        $this->assertEquals('', Helper::icutbefore('bar', 'bar'));
        $this->assertEquals('FOO', Helper::icutbefore('FOOBAR', 'bar'));
        $this->assertEquals('def', Helper::icutbefore('FOO', 'bar', 'def'));
    }

    public function testPickstartsat()
    {
        $this->assertEquals([], Helper::pickstartsat(['foo' => 'bar'], 'bar'));
        $this->assertEquals(['foo' => 'bar'], Helper::pickstartsat(['foo' => 'bar'], 'foo'));
        $this->assertEquals(['bar' => 'baz', 'qux' => 'quux'], Helper::pickstartsat(['foo' => 'bar', 'bar' => 'baz', 'qux' => 'quux'], 'bar'));
        $this->assertEquals(['bar' => 'baz', 'qux' => 'quux'], Helper::pickstartsat(['foo' => 'bar', 'bar' => 'baz', 'qux' => 'quux'], 'bar', 2));
        $this->assertEquals(['bar' => 'baz'], Helper::pickstartsat(['foo' => 'bar', 'bar' => 'baz'], 'bar', 1));
        $this->assertEquals(['bar' => 'baz'], Helper::pickstartsat(['foo' => 'bar', 'bar' => 'baz', 'qux' => 'quux'], 'bar', 1));
    }

    public function testExtract()
    {
        $this->assertEquals(['bar' => 'baz'], Helper::extract(['foobar' => 'baz'], 'foo'));
        $this->assertEquals(['foobar' => 'baz'], Helper::extract(['foobar' => 'baz'], ''));
    }

    public function testConstant()
    {
        $this->assertEquals('foo', Helper::constant(ConstantPool::class.'::FOO'));
        $this->assertEquals('none', Helper::constant(ConstantPool::class.'::BAR', 'none'));
    }

    public function testConstants()
    {
        $this->assertEquals(['OO' => 'foo'], Helper::constants(ConstantPool::class, 'F'));
    }

    public function testSplit()
    {
        $this->assertEquals(['a', 'b', 'c'], Helper::split('a|b|c'));
        $this->assertEquals(['a', 'b', 'c'], Helper::split('a,b,c'));
        $this->assertEquals(['a', 'b', 'c'], Helper::split('a;b;c'));
        $this->assertEquals(['a', 'b', 'c', ''], Helper::split('a,b,c,', false));
    }

    public function testReqarr()
    {
        $this->assertEquals(['a', 'b', 'c'], Helper::reqarr(['a', 'b', 'c']));
        $this->assertEquals(['a', 'b', 'c'], Helper::reqarr('a,b,c'));
    }

    public function testReqstr()
    {
        $this->assertEquals('abc', Helper::reqstr('abc'));
        $this->assertEquals('abc', Helper::reqstr(['a', 'b', 'c'], ''));
    }

    public function testExinclude()
    {
        $this->expectOutputString("Foo\n");
        Helper::exinclude(FIXTURE.'helper/foo.php');
    }

    public function testExrequire()
    {
        $this->expectOutputString("Foo\n");
        Helper::exrequire(FIXTURE.'helper/foo.php');
    }

    public function testMkdir()
    {
        $path = TEMP.'mktest';
        $result = Helper::mkdir($path);
        $this->assertTrue($result);
        $this->assertTrue(is_dir($path));

        $result = Helper::mkdir($path);
        $this->assertTrue($result);
    }

    public function testRead()
    {
        $expected = 'foo';
        $file = FIXTURE.'helper/foo.txt';
        $result = Helper::read($file);
        $this->assertEquals($expected, $result);
    }

    public function testWrite()
    {
        $expected = 3;
        $file = TEMP.'foo.txt';
        $data = 'foo';
        $result = Helper::write($file, $data);
        $this->assertEquals($expected, $result);
    }

    public function testDelete()
    {
        $file = TEMP.'todelete.txt';
        $this->assertFalse(Helper::delete($file));
        touch($file);
        $this->assertFileExists($file);
        $this->assertTrue(Helper::delete($file));
        $this->assertFileNotExists($file);
    }

    public function testCsv()
    {
        $this->assertEquals("1,2,'3'", Helper::csv([1, 2, '3']));
    }

    public function testContexttostring()
    {
        $this->assertEquals("foo: 'bar'", Helper::contexttostring(['foo' => 'bar']));
        $this->assertEquals("foo: 'bar'\nbar: 'baz'", Helper::contexttostring(['foo' => 'bar', 'bar' => 'baz']));
        $this->assertEquals("foo: array(\n    0 => 'bar',\n)", Helper::contexttostring(['foo' => ['bar']]));
    }

    public function testStringifyignorescalar()
    {
        $this->assertEquals('foo', Helper::stringifyignorescalar('foo'));
        $this->assertEquals('0', Helper::stringifyignorescalar(0));
        $this->assertEquals("['foo']", Helper::stringifyignorescalar(['foo']));
    }

    public function testStringify()
    {
        $this->assertEquals("'foo'", Helper::stringify('foo'));
        $this->assertEquals("['foo']", Helper::stringify(['foo']));
        $this->assertEquals('stdClass::__set_state([])', Helper::stringify(new \StdClass()));

        $std = new \StdClass();
        $std->foo = 'bar';
        $this->assertEquals("stdClass::__set_state(['foo'=>'bar'])", Helper::stringify($std));
    }

    public function parseExprProvider()
    {
        return [
            [
                'foo',
                [
                    'foo' => [],
                ],
            ],
            [
                'foo|bar',
                [
                    'foo' => [],
                    'bar' => [],
                ],
            ],
            [
                'foo:1|bar:arg|baz:[0,1,2]|qux:{"foo":"bar"}|quux:1,arg,[0,1,2],{"foo":"bar"}',
                [
                    'foo' => [1],
                    'bar' => ['arg'],
                    'baz' => [[0, 1, 2]],
                    'qux' => [['foo' => 'bar']],
                    'quux' => [1, 'arg', [0, 1, 2], ['foo' => 'bar']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider parseExprProvider
     */
    public function testParsexpr($expr, $expected)
    {
        $this->assertEquals($expected, Helper::parsexpr($expr));
    }

    public function refProvider()
    {
        return [
            [null, 'foo'],
            [null, 'foo.bar.baz'],
            [null, 'foo', [], false],
            [null, 'foo.bar.baz', [], false],
            ['bar', 'foo', ['foo' => 'bar']],
            ['baz', 'foo.bar', ['foo' => ['bar' => 'baz']]],
        ];
    }

    /**
     * @dataProvider refProvider
     */
    public function testRef($expected, $key, $data = [], $add = true)
    {
        $this->assertEquals($expected, Helper::ref($key, $data, $add));
    }

    public function testRefRealCase()
    {
        $data = ['foo' => 'bar'];
        $ref = &Helper::ref('foo', $data);
        $ref = 'baz';

        $this->assertEquals(['foo' => 'baz'], $data);

        $ref = &Helper::ref('baz.qux', $data);
        $ref = 'quux';

        $this->assertEquals(['foo' => 'baz', 'baz' => ['qux' => 'quux']], $data);
    }
}
