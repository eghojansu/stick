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

namespace Fal\Stick\Test\Html;

use Fal\Stick\Html\Html;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    private $html;

    public function setUp()
    {
        $this->html = new Html();
    }

    public function testAttr()
    {
        $this->assertEquals(' name="foo" checked enabled', $this->html->attr(array('name' => 'foo', 'checked' => true, 'enabled')));
    }

    public function testElement()
    {
        $this->assertEquals('<a href="foo">Foo</a>', $this->html->element('a', true, 'Foo', array('href' => 'foo')));
        $this->assertEquals('<br>', $this->html->element('br'));
    }

    public function testInput()
    {
        $this->assertEquals('<input type="text" name="foo" id="foo">', $this->html->input('text', 'foo'));
    }

    public function testText()
    {
        $this->assertEquals('<input type="text" name="foo" id="foo">', $this->html->text('foo'));
    }

    public function testPassword()
    {
        $this->assertEquals('<input type="password" name="foo" id="foo">', $this->html->password('foo'));
    }

    public function testHidden()
    {
        $this->assertEquals('<input type="hidden" name="foo" id="foo">', $this->html->hidden('foo'));
    }

    public function testCheckbox()
    {
        $this->assertEquals('<label><input type="checkbox" name="foo"> Foo</label>', $this->html->checkbox('foo', null, 'Foo'));
    }

    public function testRadio()
    {
        $this->assertEquals('<label><input type="radio" name="foo"> Foo</label>', $this->html->radio('foo', null, 'Foo'));
    }

    public function testTextarea()
    {
        $this->assertEquals('<textarea name="foo" id="foo">Foo</textarea>', $this->html->textarea('foo', 'Foo'));
    }

    public function testSelect()
    {
        $expected = '<select name="foo" id="foo"><option value="foo" selected>Foo</option><option value="bar">Bar</option></select>';
        $actual = $this->html->select('foo', 'foo', null, null, array(
            'Foo' => 'foo',
            'Bar' => 'bar',
        ));
        $this->assertEquals($expected, $actual);

        $expected = '<select name="foo" id="foo"><option value="">Foo</option><option value="foo" selected>Foo</option><option value="bar">Bar</option><option value="baz" selected>Baz</option></select>';
        $actual = $this->html->select('foo', array('foo', 'baz'), 'Foo', null, array(
            'Foo' => 'foo',
            'Bar' => 'bar',
            'Baz' => 'baz',
        ));
        $this->assertEquals($expected, $actual);
    }

    public function testCheckboxGroup()
    {
        $expected = '<div><label><input checked type="checkbox" name="foo[]" value="foo"> Foo</label>'.
                    '<label><input type="checkbox" name="foo[]" value="bar"> Bar</label></div>';
        $actual = $this->html->checkboxGroup('foo', 'foo', null, array(
            'Foo' => 'foo',
            'Bar' => 'bar',
        ));
        $this->assertEquals($expected, $actual);

        $expected = '<div><label><input checked type="checkbox" name="foo[]" value="foo"> Foo</label>'.
                    '<label><input type="checkbox" name="foo[]" value="bar"> Bar</label>'.
                    '<label><input checked type="checkbox" name="foo[]" value="baz"> Baz</label></div>';
        $actual = $this->html->checkboxGroup('foo', array('foo', 'baz'), null, array(
            'Foo' => 'foo',
            'Bar' => 'bar',
            'Baz' => 'baz',
        ));
        $this->assertEquals($expected, $actual);
    }

    public function testRadioGroup()
    {
        $expected = '<div><label><input checked type="radio" name="foo" value="foo"> Foo</label>'.
                    '<label><input type="radio" name="foo" value="bar"> Bar</label></div>';
        $actual = $this->html->radioGroup('foo', 'foo', null, array(
            'Foo' => 'foo',
            'Bar' => 'bar',
        ));
        $this->assertEquals($expected, $actual);
    }

    public function testLabel()
    {
        $this->assertEquals('<label>Foo</label>', $this->html->label('Foo'));
        $this->assertEquals('<label for="foo">Foo</label>', $this->html->label('Foo', 'foo'));
    }

    public function testButton()
    {
        $this->assertEquals('<button type="button">Foo</button>', $this->html->button('Foo'));
    }

    public function testFixId()
    {
        $this->assertNull($this->html->fixId(''));
        $this->assertEquals('foo_bar', $this->html->fixId('foo[bar]'));
        $this->assertEquals('foo', $this->html->fixId('foo'));
    }
}
