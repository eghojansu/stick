<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 09:54
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    /**
     * @dataProvider slugProvider
     */
    public function testSlug($expected, $text)
    {
        $this->assertEquals($expected, Util::slug($text));
    }

    /**
     * @dataProvider mimeProvider
     */
    public function testMime($expected, $filename)
    {
        $this->assertEquals($expected, Util::mime($filename));
    }

    public function testHash()
    {
        $this->assertEquals('1xnmsgr3l2f5f', Util::hash('foo'));
    }

    /**
     * @dataProvider camelCaseProvider
     */
    public function testCamelCase($expected, $text)
    {
        $this->assertEquals($expected, Util::camelCase($text));
    }

    /**
     * @dataProvider snakeCaseProvider
     */
    public function testSnakeCase($expected, $text)
    {
        $this->assertEquals($expected, Util::snakeCase($text));
    }

    /**
     * @dataProvider classNameProvider
     */
    public function testClassName($expected, $class)
    {
        $this->assertEquals($expected, Util::className($class));
    }

    /**
     * @dataProvider splitProvider
     */
    public function testSplit($expected, $var, $delimiter = null)
    {
        $this->assertEquals($expected, Util::split($var, $delimiter));
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoin($expected, $var, $glue = null)
    {
        $this->assertEquals($expected, Util::join($var, $glue));
    }

    /**
     * @dataProvider castProvider
     */
    public function testCast($expected, $var)
    {
        $this->assertEquals($expected, Util::cast($var));
    }

    public function testAttr()
    {
        $expected = ' foo="bar" baz qux="{\\"one\\":1}" quux';
        $attr = array(
            'foo' => 'bar',
            'bar' => null,
            'baz' => true,
            'qux' => array('one' => 1),
            'quux',
        );

        $this->assertEquals($expected, Util::attr($attr));
        $this->assertEquals('', Util::attr());
    }

    /**
     * @dataProvider tagProvider
     */
    public function testTag($expected, $tag, $attr = null, $pair = false, $content = null)
    {
        $this->assertEquals($expected, Util::tag($tag, $attr, $pair, $content));
    }

    /**
     * @dataProvider titleCaseProvider
     */
    public function testTitleCase($expected, $text)
    {
        $this->assertEquals($expected, Util::titleCase($text));
    }

    /**
     * @dataProvider stringifyProvider
     */
    public function testStringify($expected, $argument)
    {
        $this->assertEquals($expected, Util::stringify($argument));
    }

    public function testTrace()
    {
        $expected = 'Fal\Stick\Test\UtilTest->testTrace()';
        $trace = Util::trace();

        $this->assertContains($expected, $trace);
    }

    public function castProvider()
    {
        return array(
            array(1, '1'),
            array(1.2, '1.2'),
            array('foo', 'foo'),
            array(null, 'null'),
            array(true, 'true'),
            array(false, 'false'),
            array(-23, '-23'),
            array(26, '0x1A'),
            array(255, '0b11111111'),
            array(83, '0123'),
            array(array('foo'), array('foo')),
        );
    }

    public function classNameProvider()
    {
        return array(
            array('DateTime', new \DateTime()),
            array('DateTime', 'DateTime'),
        );
    }

    public function camelCaseProvider()
    {
        return array(
            array('snakeCase', 'snake_case'),
            array('snakeCase', 'SNAKE_CASE'),
            array('snakeCaseText', 'snake_case_text'),
            array('snakeCaseText', 'Snake_case_text'),
        );
    }

    public function snakeCaseProvider()
    {
        return array(
            array('camel_case', 'camelCase'),
            array('camel_case_text', 'camelCaseText'),
            array('camel_case_text', 'CamelCaseText'),
        );
    }

    public function splitProvider()
    {
        return array(
            array(array('foo', 'bar'), array('foo', 'bar')),
            array(array(), null),
            array(array('foo', 'bar'), 'foo,bar'),
            array(array('foo', 'bar'), 'foo, bar'),
            array(array('foo', 'bar'), 'foo: bar', ':'),
            array(array('foo', 'bar', 'baz'), 'foo, bar|baz '),
        );
    }

    public function joinProvider()
    {
        return array(
            array('foo,bar', 'foo,bar'),
            array('foo,bar', array('foo', 'bar')),
            array('foo:bar', array('foo', 'bar'), ':'),
        );
    }

    public function slugProvider()
    {
        return array(
            array('foo-bar', 'foo bar'),
            array('foo-bar', "foo\nbar"),
            array('foo-bar', 'foo bǍr'),
        );
    }

    public function mimeProvider()
    {
        return array(
            array('audio/basic', 'foo.au'),
            array('text/html', 'foo.html'),
            array('text/html', 'foo.phtml'),
            array('text/html', 'foo.phtm'),
            array('text/html', 'foo.htm'),
            array('text/plain', 'foo.bar.txt'),
            array('application/octet-stream', 'foo.134'),
        );
    }

    public function tagProvider()
    {
        return array(
            array('<form>', 'form'),
            array('<form method="post">', 'form', array('method' => 'post')),
            array('<div>text</div>', 'div', null, true, 'text'),
        );
    }

    public function titleCaseProvider()
    {
        return array(
            array('Foo', 'foo'),
            array('Foo Bar', 'foo_bar'),
            array('Foo Bar', 'FooBar'),
        );
    }

    public function stringifyProvider()
    {
        return array(
            array('Object(stdClass)', new \stdClass()),
            array("['foo', 'bar', Object(stdClass)]", array('foo', 'bar', new \stdClass())),
            array("'foo'", 'foo'),
            array(1, 1),
            array('true', true),
            array('NULL', null),
        );
    }
}
