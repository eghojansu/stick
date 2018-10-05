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

namespace Fal\Stick\Test\Template;

use Fal\Stick\App;
use Fal\Stick\Template\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private $app;
    private $template;

    public function setUp()
    {
        $this->app = new App();
        $this->template = new Template($this->app, FIXTURE.'template/');
    }

    public function testGetDirs()
    {
        $this->assertEquals(array(FIXTURE.'template/'), $this->template->getDirs());
    }

    public function testSetDirs()
    {
        $this->assertEquals(array('foo'), $this->template->setDirs('foo')->getDirs());
        $this->assertEquals(array('foo', 'bar'), $this->template->setDirs('bar', true)->getDirs());
    }

    public function testRender()
    {
        $expected = 'foo';
        $actual = trim($this->template->render('foo.php'));

        $this->assertEquals($expected, $actual);
        $this->assertEquals(3, $this->app->get('HEADERS.Content-Length'));
    }

    public function callProvider()
    {
        return array(
            array('FOO', 'upper', array('foo')),
            array('foo &gt; 1', 'e', array('foo > 1')),
            array('/foo', 'alias', array('foo')),
            array('snake_case', 'snakecase', array('snakeCase')),
            array('foo', 'trim', array(' foo  ')),
        );
    }

    /**
     * @dataProvider callProvider
     */
    public function testCall($expected, $func, array $args = null)
    {
        $this->app->route('GET foo /foo', 'foo');

        $this->assertEquals($expected, $this->template->call($func, $args));
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Call to undefined function unknown_function()
     */
    public function testCallException()
    {
        $this->template->call('unknown_function');
    }

    public function testAddFunction()
    {
        $this->assertEquals('foo', $this->template->addFunction('foo', 'trim')->call('foo', array(' foo')));
    }

    public function testAddMacro()
    {
        $expected = 'foo macro';
        $actual = $this->template->addMacro('bar', FIXTURE.'template/macros/foo.php')->call('bar');

        $this->assertEquals($expected, $actual);
    }

    public function testCallMacro()
    {
        $expected = 'foo bar';
        $actual = $this->template->call('arg', array('foo bar'));

        $this->assertEquals($expected, $actual);
    }

    public function testCallFilter()
    {
        $actual = $this->template->call('filter', array(' foo ', 'ltrim|rtrim'));

        $this->assertEquals('foo', $actual);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Macro not exists: "unknown_macro".
     */
    public function testCallMacroException()
    {
        $this->template->call('macro', array('unknown_macro'));
    }

    public function testFindMacro()
    {
        $expected = 'foo macro';
        $actual = $this->template->call('macro', array(FIXTURE.'template/macros/foo.php'));

        $this->assertEquals($expected, $actual);
    }
}
