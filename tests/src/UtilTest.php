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

namespace Fal\Stick\Test;

use Fal\Stick\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testClassName()
    {
        $this->assertEquals('UtilTest', Util::className(UtilTest::class));
        $this->assertEquals('UtilTest', Util::className($this));
    }

    public function testCast()
    {
        $this->assertSame(true, Util::cast('true'));
        $this->assertSame(true, Util::cast('TRUE'));
        $this->assertSame(null, Util::cast('null'));
        $this->assertSame(0, Util::cast('0'));
        $this->assertSame('foo', Util::cast('foo'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('fooBar', Util::camelcase('foo_bar'));
    }

    public function testSnakeCase()
    {
        $this->assertEquals('foo_bar', Util::snakecase('fooBar'));
        $this->assertEquals('foo_bar', Util::snakecase('FooBar'));
    }

    public function testTitleCase()
    {
        $this->assertEquals('Foo', Util::titleCase('foo'));
        $this->assertEquals('Foo Bar', Util::titleCase('foo_bar'));
        $this->assertEquals('FOO Bar', Util::titleCase('FOO_bar'));
    }

    public function testStartswith()
    {
        $this->assertTrue(Util::startswith('foobar', 'fo'));
        $this->assertFalse(Util::startswith('foobar', 'Fo'));
        $this->assertFalse(Util::startswith('foobar', 'xo'));
    }

    public function testEndswith()
    {
        $this->assertTrue(Util::endswith('foobar', 'ar'));
        $this->assertFalse(Util::endswith('foobar', 'aR'));
        $this->assertFalse(Util::endswith('foobar', 'as'));
    }

    public function parseExprProvider()
    {
        return array(
            array(
                '',
                array(),
            ),
            array(
                'foo',
                array(
                    'foo' => array(),
                ),
            ),
            array(
                'foo|bar',
                array(
                    'foo' => array(),
                    'bar' => array(),
                ),
            ),
            array(
                'foo:1|bar:arg|baz:[0,1,2]|qux:{"foo":"bar"}|quux:1,arg,[0,1,2],{"foo":"bar"}',
                array(
                    'foo' => array(1),
                    'bar' => array('arg'),
                    'baz' => array(array(0, 1, 2)),
                    'qux' => array(array('foo' => 'bar')),
                    'quux' => array(1, 'arg', array(0, 1, 2), array('foo' => 'bar')),
                ),
            ),
        );
    }

    /**
     * @dataProvider parseExprProvider
     */
    public function testParseExpr($expr, $expected)
    {
        $this->assertEquals($expected, Util::parseExpr($expr));
    }

    public function testSplit()
    {
        $target = array('foo', 'bar', 'baz', 'qux');

        $this->assertEquals(array('foo'), Util::split('foo'));
        $this->assertEquals($target, Util::split('foo,bar,baz,qux'));
        $this->assertEquals($target, Util::split('foo;bar;baz;qux'));
        $this->assertEquals($target, Util::split('foo|bar|baz|qux'));
        $this->assertEquals($target, Util::split('foo,bar;baz|qux'));
        $this->assertEquals($target, Util::split('foo,bar;baz|qux|'));
        $this->assertEquals($target, Util::split('foo,bar ;baz|qux|'));
    }

    public function testFixslashes()
    {
        $this->assertEquals('/foo', Util::fixslashes('\\foo'));
        $this->assertEquals('/foo/bar', Util::fixslashes('\\foo/bar'));
    }

    public function testArr()
    {
        $target = array('foo', 'bar', 'baz', 'qux');

        $this->assertEquals($target, Util::arr('foo,bar,baz,qux'));
        $this->assertEquals($target, Util::arr($target));
    }

    public function testCutbefore()
    {
        $this->assertEquals('foo', Util::cutbefore('foo?bar', '?'));
        $this->assertEquals('foo?', Util::cutbefore('foo?bar', '?', null, true));
        $this->assertEquals('foo?bar', Util::cutbefore('foo?bar', '#'));
        $this->assertEquals('x', Util::cutbefore('foo?bar', '#', 'x'));
    }

    public function testCutafter()
    {
        $this->assertEquals('bar', Util::cutafter('foo?bar', '?'));
        $this->assertEquals('?bar', Util::cutafter('foo?bar', '?', null, true));
        $this->assertEquals('foo?bar', Util::cutafter('foo?bar', '#'));
        $this->assertEquals('x', Util::cutafter('foo?bar', '#', 'x'));
    }

    public function testCutprefix()
    {
        $this->assertEquals('bar', Util::cutprefix('foobar', 'foo'));
        $this->assertEquals('default', Util::cutprefix('foobar', 'foobar', 'default'));
        $this->assertEquals('qux', Util::cutprefix('foobar', 'xoo', 'qux'));
    }

    public function testCutsuffix()
    {
        $this->assertEquals('foo', Util::cutsuffix('foobar', 'bar'));
        $this->assertEquals('default', Util::cutsuffix('foobar', 'foobar', 'default'));
        $this->assertEquals('qux', Util::cutsuffix('foobar', 'xar', 'qux'));
    }

    public function testHash()
    {
        $this->assertEquals(13, strlen(Util::hash('foo')));
        $this->assertEquals(13, strlen(Util::hash('foobar')));
    }

    public function testMkdir()
    {
        if (file_exists($dir = TEMP.'test-mkdir')) {
            rmdir($dir);
        }

        $this->assertTrue(Util::mkdir($dir));
        rmdir($dir);
    }

    public function testRead()
    {
        $this->assertEquals('foo', Util::read(FIXTURE.'files/foo.txt'));
    }

    public function testWrite()
    {
        $this->assertEquals(3, Util::write(TEMP.'write.txt', 'foo'));
    }

    public function testDelete()
    {
        if (!is_file($file = TEMP.'test-delete.txt')) {
            touch($file);
        }

        $this->assertTrue(Util::delete($file));
        $this->assertFalse(Util::delete($file));
    }

    public function testRequireFile()
    {
        $this->assertEquals('foo', Util::requireFile(FIXTURE.'files/foo.php'));
    }

    public function testWalk()
    {
        $this->assertEquals(array(true, false, false), Util::walk(array(1, 'foo', 'bar'), 'is_numeric'));
    }

    public function testColumn()
    {
        $arr = array('foo' => array('foo' => 'bar'), 'bar' => array('foo' => 'baz'));

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), Util::column($arr, 'foo'));
    }

    public function testDashCase()
    {
        $this->assertEquals('Foo-Bar', Util::dashCase('FOO_BAR'));
        $this->assertEquals('X-Foo-Bar', Util::dashCase('X_FOO_BAR'));
    }

    public function testRequestHeaders()
    {
        $source = array(
            'CONTENT_TYPE' => 'foo',
            'HTTP_X_FOO_BAR' => 'baz',
        );
        $expected = array_combine(array('Content-Type', 'X-Foo-Bar'), $source);

        $this->assertEquals($expected, Util::requestHeaders($source));
    }
}
