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

use Fal\Stick\Helper;
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

    public function parseExprProvider()
    {
        return [
            [
                '',
                [],
            ],
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
}
