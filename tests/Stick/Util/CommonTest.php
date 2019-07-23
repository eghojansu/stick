<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Util;

use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Util\Common;

class CommonTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::snakeCase
     */
    public function testSnakeCase($expected, $text)
    {
        $this->assertEquals($expected, Common::snakeCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::camelCase
     */
    public function testCamelCase($expected, $text)
    {
        $this->assertEquals($expected, Common::camelCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::pascalCase
     */
    public function testPascalCase($expected, $text)
    {
        $this->assertEquals($expected, Common::pascalCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::titleCase
     */
    public function testTitleCase($expected, $text)
    {
        $this->assertEquals($expected, Common::titleCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::dashCase
     */
    public function testDashCase($expected, $text)
    {
        $this->assertEquals($expected, Common::dashCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::classname
     */
    public function testClassname($expected, $class)
    {
        $this->assertEquals($expected, Common::classname($class));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::arrColumn
     */
    public function testArrColumn($expected, $input, $key, $noFilter = true)
    {
        $this->assertEquals($expected, Common::arrColumn($input, $key, $noFilter));
    }

    public function testTrimTrailingSpace()
    {
        // this is a fake test!
        $this->assertEquals('foo', Common::trimTrailingSpace('foo'));
    }

    public function testFiles()
    {
        $dir = $this->tmp(null, true);

        $this->assertInstanceOf('RecursiveIteratorIterator', Common::files($dir));

        // update
        $dir .= '/foo';
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Directory not exists: '.$dir);
        Common::files($dir);
    }

    public function testFixLinefeed()
    {
        $this->assertEquals("foo\nbar\nbaz", Common::fixLinefeed("foo\nbar\r\nbaz"));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CommonProvider::read
     */
    public function testRead($expected, $file, $normalizeLinefeed = false)
    {
        $this->assertEquals($expected, Common::read($file, $normalizeLinefeed));
    }

    public function testWrite()
    {
        $file = $this->tmp('/foo.txt', true);

        $this->assertEquals(3, Common::write($file, 'foo'));
        $this->assertEquals(3, Common::write($file, 'bar', true));
        $this->assertEquals('foobar', file_get_contents($file));
    }

    public function testDelete()
    {
        $file = $this->tmp('/foo.txt', true);
        touch($file);

        $this->assertFileExists($file);
        $this->assertTrue(Common::delete($file));
        $this->assertFileNotExists($file);
        $this->assertFalse(Common::delete($file));
    }
}
