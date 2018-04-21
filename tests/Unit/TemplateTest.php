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
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected $template;

    public function setUp()
    {
        $this->template = new Template(FIXTURE . 'template/');
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testAddFunction()
    {
        $this->assertEquals($this->template, $this->template->addFunction('foo', 'trim'));
    }

    public function testSetMacroAliases()
    {
        $this->template->setMacroAliases(['foo'=>'message']);
        $expected = 'Message content: what message';
        $this->assertEquals($expected, $this->template->foo('what message'));
    }

    public function testGet()
    {
        $this->assertEquals('bar', $this->template->get('foo', 'bar'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->template->set('foo', 'bar')->get('foo'));
    }

    public function testPush()
    {
        $this->assertEquals(['bar'], $this->template->push('foo', 'bar')->get('foo'));
        $this->assertEquals(['baz','qux'], $this->template->set('bar', 'baz')->push('bar', 'qux')->get('bar'));
    }

    public function testPop()
    {
        $this->assertNull($this->template->pop('foo'));
        $this->assertEquals('bar', $this->template->push('foo', 'bar')->pop('foo'));
    }

    public function testUnshift()
    {
        $this->assertEquals(['bar'], $this->template->unshift('foo', 'bar')->get('foo'));
        $this->assertEquals(['qux','baz'], $this->template->set('bar', 'baz')->unshift('bar', 'qux')->get('bar'));
    }

    public function testShift()
    {
        $this->assertNull($this->template->shift('foo'));
        $this->assertEquals('bar', $this->template->push('foo', 'bar')->shift('foo'));
    }

    public function testMerge()
    {
        $this->assertEquals(['bar'], $this->template->merge('foo', ['bar'])->get('foo'));
        $this->assertEquals(['bar','baz'], $this->template->merge('foo', ['baz'])->get('foo'));
    }

    public function testGetTemplateExtension()
    {
        $this->assertEquals('.php', $this->template->getTemplateExtension());
    }

    public function testSetTemplateExtension()
    {
        $this->assertEquals('', $this->template->setTemplateExtension('')->getTemplateExtension());
    }

    public function testFilter()
    {
        $this->assertEquals('fOO', $this->template->filter('foo', 'upper|lcfirst'));
    }

    public function testEsc()
    {
        $this->assertEquals('&lt;span&gt;foo&lt;/span&gt;', $this->template->esc('<span>foo</span>'));
    }

    public function testE()
    {
        $this->assertEquals('&lt;span&gt;foo&lt;/span&gt;', $this->template->e('<span>foo</span>'));
    }

    public function testMagicMethodCall()
    {
        $this->assertEquals('FOO', $this->template->upper('foo'));
        $this->assertTrue($this->template->startswith('foo', 'foobar'));
        $this->assertEquals('fOO', $this->template->lcfirst('FOO'));

        // calling macro
        $expected = '<input type="text" name="noname">';
        $this->assertEquals($expected, $this->template->input());

        $expected = '<input type="hidden" name="hidden">';
        $this->assertEquals($expected, $this->template->input('hidden', 'hidden'));

        $expected = 'Message content: no message';
        $this->assertEquals($expected, $this->template->message());

        $expected = 'Message content: what message';
        $this->assertEquals($expected, $this->template->message('what message'));
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage Call to undefined function foo
     */
    public function testMagicMethodCallException()
    {
        $this->template->foo();
    }

    public function testExists()
    {
        $this->assertTrue($this->template->exists('include', $a));
        $this->assertEquals(FIXTURE.'template/include.php', $a);

        $this->assertFalse($this->template->exists('foo', $b));
        $this->assertNull($b);
    }

    public function testmacroExists()
    {
        $this->assertTrue($this->template->macroExists('input', $a));
        $this->assertEquals(FIXTURE.'template/macros/input.php', $a);

        $this->assertFalse($this->template->macroExists('foo', $b));
        $this->assertNull($b);

        $this->template->setMacroAliases(['foo'=>'input']);
        $this->assertTrue($this->template->macroExists('foo', $c));
        $this->assertEquals(FIXTURE.'template/macros/input.php', $c);
    }

    public function testRender()
    {
        $expected = file_get_contents(FIXTURE . 'template/include.html');
        $this->assertEquals($expected, $this->template->render('include'));
    }

    public function testRender2()
    {
        $expected = file_get_contents(FIXTURE . 'template/single.html');
        $this->assertEquals($expected, $this->template->render('single', ['pageTitle'=>'Foo']));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage View file does not exists: foo
     */
    public function testRenderException()
    {
        $this->template->render('foo');
    }

    public function testInclude()
    {
        $expected = trim(file_get_contents(FIXTURE . 'template/includeme.html'));
        $this->assertEquals($expected, $this->template->include('includeme', null, 3));
    }
}
