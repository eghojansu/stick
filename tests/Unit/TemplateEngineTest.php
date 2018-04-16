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

use Fal\Stick\Template;
use Fal\Stick\TemplateEngine;
use PHPUnit\Framework\TestCase;

class TemplateEngineTest extends TestCase
{
    private $engine;

    public function setUp()
    {
        $this->engine = new TemplateEngine(FIXTURE . 'template/');
    }

    public function testAddFunction()
    {
        $this->assertEquals($this->engine, $this->engine->addFunction('foo', 'trim'));
    }

    public function testAddGlobal()
    {
        $this->assertEquals($this->engine, $this->engine->addGlobal('foo', 'bar'));
    }

    public function testAddGlobals()
    {
        $this->assertEquals($this->engine, $this->engine->addGlobals(['foo'=>'bar']));
    }

    public function testGetTemplateExtension()
    {
        $this->assertEquals('.php', $this->engine->getTemplateExtension());
    }

    public function testSetTemplateExtension()
    {
        $this->assertEquals('', $this->engine->setTemplateExtension('')->getTemplateExtension());
    }

    public function testFilter()
    {
        $this->assertEquals('fOO', $this->engine->filter('foo', 'upper|lcfirst'));
    }

    public function testEsc()
    {
        $this->assertEquals('&lt;span&gt;foo&lt;/span&gt;', $this->engine->esc('<span>foo</span>'));
    }

    public function testE()
    {
        $this->assertEquals('&lt;span&gt;foo&lt;/span&gt;', $this->engine->e('<span>foo</span>'));
    }

    public function testMake()
    {
        $this->assertInstanceOf(Template::class, $this->engine->make('fragment'));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage View file does not exists: foo
     */
    public function testMakeException()
    {
        $this->engine->make('foo');
    }

    public function testRender()
    {
        $expected = file_get_contents(FIXTURE.'/template/fragment.html');
        $this->assertEquals($expected, trim($this->engine->render('fragment')));
    }

    public function testMagicMethodCall()
    {
        $this->assertEquals('FOO', $this->engine->upper('foo'));
        $this->assertTrue($this->engine->startswith('foo', 'foobar'));
        $this->assertEquals('fOO', $this->engine->lcfirst('FOO'));
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage Call to undefined function foo
     */
    public function testMagicMethodCallException()
    {
        $this->engine->foo();
    }
}
