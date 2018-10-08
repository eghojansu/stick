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

namespace Fal\Stick\Test\Library\Html;

use Fal\Stick\App;
use Fal\Stick\Library\Html\Html;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    private $html;
    private $app;

    public function setUp()
    {
        $this->app = App::create();
        $this->html = new Html($this->app);
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

    public function paginationProvider()
    {
        return array(
            array('foo', 1, 0, null, ''),
            array('foo', 0, 1, null, ''),
            array('foo', 1, 1, null, '<li class="active"><a href="/?page=1">1</a></li>'),
            array('foo', 2, 1, null, '<li><a href="/?page=1">1</a></li>'),
            array('foo', 1, 2, null,
                '<li class="active"><a href="/?page=1">1</a></li>'.
                '<li><a href="/?page=2">2</a></li>',
            ),
            array('foo', 2, 3, null,
                '<li><a href="/?page=1">1</a></li>'.
                '<li class="active"><a href="/?page=2">2</a></li>'.
                '<li><a href="/?page=3">3</a></li>',
            ),
            array('foo', 1, 1, array('route_query' => array('bar' => 'baz')),
                '<li class="active"><a href="/?page=1&bar=baz">1</a></li>',
            ),
            array('foo', 5, 9, null,
                '<li><a href="/?page=4">Prev</a></li>'.
                '<li><a href="/?page=1">1</a></li>'.
                '<li class="gap"><span>&hellip;</span></li>'.
                '<li><a href="/?page=3">3</a></li>'.
                '<li><a href="/?page=4">4</a></li>'.
                '<li class="active"><a href="/?page=5">5</a></li>'.
                '<li><a href="/?page=6">6</a></li>'.
                '<li><a href="/?page=7">7</a></li>'.
                '<li class="gap"><span>&hellip;</span></li>'.
                '<li><a href="/?page=9">9</a></li>'.
                '<li><a href="/?page=6">Next</a></li>',
            ),
            array('bar', 1, 1, array('route_data' => array('bar' => 'baz')),
                '<li class="active"><a href="/bar/baz?page=1">1</a></li>',
            ),
            array('baz', 1, 1, array('route_data' => array('bar', 'baz')),
                '<li class="active"><a href="/baz/bar/baz?page=1">1</a></li>',
            ),
            array('qux', 1, 1, array('route_data' => array('qux' => 'qux', 'bar', 'baz')),
                '<li class="active"><a href="/qux/qux/bar/baz?page=1">1</a></li>',
            ),
        );
    }

    /**
     * @dataProvider paginationProvider
     */
    public function testPagination($alias, $page, $max, $config, $content, $query = null)
    {
        $this->app
            ->route('GET foo /', 'foo')
            ->route('GET bar /bar/@bar', 'bar')
            ->route('GET baz /baz/*', 'baz')
            ->route('GET qux /qux/@qux/*', 'qux')
            ->set('ALIAS', $alias)
            ->set('GET', $query)
        ;
        $expected = $content ? '<nav aria-label="Page navigation"><ul class="pagination">'.$content.'</ul></nav>' : $content;

        $this->assertEquals($expected, $this->html->pagination($page, $max, $config));
    }

    public function ulinksProvider()
    {
        return array(
            array(array(), null, null, ''),
            array(array(
                'Foo' => 'foo',
            ), null, null, '<ul>'.
                '<li><a href="/foo">Foo</a></li>'.
                '</ul>',
            ),
            array(array(
                'Foo' => 'foo',
                'Bar' => array(
                    'items' => array(
                        'Bar 1' => 'foo',
                    ),
                ),
            ), 'foo', array('parent_wrapper_attr' => array('class' => 'foo')), '<ul>'.
                '<li class="active"><a href="/foo">Foo</a></li>'.
                '<li class="active">'.
                    '<a href="#">Bar</a>'.
                    '<ul class="foo">'.
                        '<li class="active"><a href="/foo">Bar 1</a></li>'.
                    '</ul>'.
                    '</li>'.
                '</ul>',
            ),
            array(array(
                'Foo' => 'foo',
                'Bar' => array(
                    'items' => array(
                        'Bar 1' => array(
                            'route' => 'bar',
                            'args' => array('bar' => 1),
                        ),
                        'Bar 2' => array(
                            'route' => 'bar',
                            'args' => array('bar' => 2),
                        ),
                    ),
                ),
                'Baz' => array(
                    'items' => array(
                        'Baz 1' => array(
                            'route' => 'baz',
                            'args' => array(1),
                        ),
                        'Baz 234' => array(
                            'route' => 'baz',
                            'args' => array(2, 3, 4),
                        ),
                    ),
                ),
                'Qux' => array(
                    'items' => array(
                        'Qux 1 1' => array(
                            'route' => 'qux',
                            'args' => array('qux' => 1, 1),
                        ),
                        'Qux 1 234' => array(
                            'route' => 'qux',
                            'args' => array('qux' => 1, 2, 3, 4),
                        ),
                    ),
                ),
            ), null, null, '<ul>'.
                '<li><a href="/foo">Foo</a></li>'.
                '<li><a href="#">Bar</a>'.
                    '<ul>'.
                        '<li><a href="/bar/1">Bar 1</a></li>'.
                        '<li><a href="/bar/2">Bar 2</a></li>'.
                    '</ul>'.
                    '</li>'.
                '<li><a href="#">Baz</a>'.
                    '<ul>'.
                        '<li><a href="/baz/1">Baz 1</a></li>'.
                        '<li><a href="/baz/2/3/4">Baz 234</a></li>'.
                    '</ul>'.
                    '</li>'.
                '<li><a href="#">Qux</a>'.
                    '<ul>'.
                        '<li><a href="/qux/1/1">Qux 1 1</a></li>'.
                        '<li><a href="/qux/1/2/3/4">Qux 1 234</a></li>'.
                    '</ul>'.
                    '</li>'.
                '</ul>',
            ),
        );
    }

    /**
     * @dataProvider ulinksProvider
     */
    public function testUlinks($items, $activeRoute, $config, $expected)
    {
        $this->app
            ->route('GET foo /foo', 'foo')
            ->route('GET bar /bar/@bar', 'foo')
            ->route('GET baz /baz/*', 'foo')
            ->route('GET qux /qux/@qux/*', 'foo')
        ;

        $this->assertEquals($expected, $this->html->ulinks($items, $activeRoute, $config));
    }
}
