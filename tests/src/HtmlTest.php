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

use Fal\Stick\Html;
use Fal\Stick\Translator;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    /**
     * @var Html
     */
    private $html;

    public function setUp()
    {
        $this->html = new Html(new Translator());
    }

    public function testDefaults()
    {
        $this->assertEquals($this->html, $this->html->defaults('foo', []));
    }

    public function testAlways()
    {
        $this->assertEquals($this->html, $this->html->always('foo', []));
    }

    public function testTemplate()
    {
        $this->assertEquals($this->html, $this->html->template('foo', 'bar'));
    }

    public function attrProvider()
    {
        return [
            ['', [
                'foo' => false,
            ]],
            [' foo', [
                'foo' => true,
            ]],
            [' foo="bar"', [
                'foo' => 'bar',
            ]],
            [' foo="1"', [
                'foo' => 1,
            ]],
            [' foo="1"', [
                'foo="1"',
            ]],
            [' foo="\\"bar\\":\\"baz\\",\\"no\\":1"', [
                'foo' => ['bar' => 'baz', 'no' => 1],
            ]],
        ];
    }

    /**
     * @dataProvider attrProvider
     */
    public function testAttr($expected, array $attr)
    {
        $this->assertEquals($expected, $this->html->attr($attr));
    }

    public function testStrToLabel()
    {
        $this->assertEquals('Foo', $this->html->strToLabel('foo'));
        $this->assertEquals('Foo Bar', $this->html->strToLabel('foo_bar'));
    }

    public function formRowProvider()
    {
        return [
            ['<label for="foo">Foo</label><input name="foo" type="text" id="foo" placeholder="Foo">', [
                'foo',
            ]],
            ['<label for="foo"></label><label><input name="foo" type="checkbox" id="foo" placeholder="Foo" value="1"> Foo</label>', [
                'foo', null, 'checkbox',
            ]],
            ['<label for="foo">Foo</label><textarea name="foo" id="foo" placeholder="Foo"></textarea>', [
                'foo', null, 'textarea',
            ]],
            ['<label for="foo">Foo</label><select name="foo" id="foo" placeholder="Foo"><option value="">Choose --</option></select>', [
                'foo', null, 'choice',
            ]],
        ];
    }

    /**
     * @dataProvider formRowProvider
     */
    public function testFormRow($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->formRow(...$args));
    }

    public function elementProvider()
    {
        return [
            ['<a>Foo</a>', [
                'a',
                'Foo',
            ]],
        ];
    }

    /**
     * @dataProvider elementProvider
     */
    public function testElement($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->element(...$args));
    }

    /**
     * @dataProvider elementProvider
     */
    public function testMagicCall($expected, array $args)
    {
        $element = array_shift($args);

        $this->assertEquals($expected, $this->html->$element(...$args));
    }

    public function testElementWithResolvedAttr()
    {
        $this->html->always('label', ['data-info' => ['bar' => 'baz'], 'class' => 'control-label']);

        $expected = '<label data-info="\"bar\":\"baz\"" class="my-label control-label">Foo</label>';
        $attr = [
            'data-info' => ['bar' => 'qux'],
            'class' => 'my-label',
        ];
        $actual = $this->html->element('label', 'Foo', $attr);

        $this->assertEquals($expected, $actual);
    }

    public function inputLabelProvider()
    {
        return [
            ['<label>Foo</label>', [
                'Foo',
            ]],
        ];
    }

    /**
     * @dataProvider inputLabelProvider
     */
    public function testInputLabel($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputLabel(...$args));
    }

    public function inputBaseProvider()
    {
        return [
            ['<input name="foo" type="text">', [
                'foo',
            ]],
        ];
    }

    /**
     * @dataProvider inputBaseProvider
     */
    public function testInputBase($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputBase(...$args));
    }

    public function inputCheckboxProvider()
    {
        return [
            ['<label><input name="foo" type="checkbox" value="1"> Foo</label>', [
                'foo',
            ]],
        ];
    }

    /**
     * @dataProvider inputCheckboxProvider
     */
    public function testInputCheckbox($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputCheckbox(...$args));
    }

    public function inputRadioProvider()
    {
        return [
            ['<label><input name="foo" type="radio" value="1"> Foo</label>', [
                'foo',
            ]],
        ];
    }

    /**
     * @dataProvider inputRadioProvider
     */
    public function testInputRadio($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputRadio(...$args));
    }

    public function inputTextareaProvider()
    {
        return [
            ['<textarea name="foo"></textarea>', [
                'foo',
            ]],
        ];
    }

    /**
     * @dataProvider inputTextareaProvider
     */
    public function testInputTextarea($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputTextarea(...$args));
    }

    public function inputChoiceProvider()
    {
        return [
            ['<select name="foo"><option value="">Choose --</option><option value="bar" selected>Foo</option><option value="baz">Bar</option></select>', [
                'foo', 'bar', null, [
                    'source' => ['Foo' => 'bar', 'Bar' => 'baz'],
                ],
            ]],
            ['<select name="foo[]" multiple><option value="">Choose --</option><option value="bar" selected>Foo</option><option value="baz">Bar</option></select>', [
                'foo', 'bar', null, [
                    'multiple' => true,
                    'source' => ['Foo' => 'bar', 'Bar' => 'baz'],
                ],
            ]],
            ['<label><input name="foo" type="radio" value="bar" checked> Foo</label><label><input name="foo" type="radio" value="baz"> Bar</label>', [
                'foo', 'bar', null, [
                    'expanded' => true,
                    'source' => ['Foo' => 'bar', 'Bar' => 'baz'],
                ],
            ]],
            ['<label><input name="foo[]" type="checkbox" value="bar" checked> Foo</label><label><input name="foo[]" type="checkbox" value="baz"> Bar</label>', [
                'foo', 'bar', null, [
                    'expanded' => true,
                    'multiple' => true,
                    'source' => ['Foo' => 'bar', 'Bar' => 'baz'],
                ],
            ]],
            ['', [
                'foo', 'bar', null, [
                    'expanded' => true,
                ],
            ]],
        ];
    }

    /**
     * @dataProvider inputChoiceProvider
     */
    public function testInputChoice($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->inputChoice(...$args));
    }

    public function paginationProvider()
    {
        return [
            ['', []],
            ['', [
                'page' => 0,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li class="active"><a href="?page=1">1</a></li></ul></nav>', [
                'page' => 1,
                'pages' => 1,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li class="active"><a href="?page=1">1</a></li><li><a href="?page=2">2</a></li><li><a href="?page=2">Next</a></li></ul></nav>', [
                'page' => 1,
                'pages' => 2,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li><a href="?page=1">Prev</a></li><li><a href="?page=1">1</a></li><li class="active"><a href="?page=2">2</a></li></ul></nav>', [
                'page' => 2,
                'pages' => 2,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li class="active"><a href="?page=1">1</a></li><li><a href="?page=2">2</a></li><li><a href="?page=3">3</a></li><li><span>&hellip;</span></li><li><a href="?page=5">5</a></li><li><a href="?page=2">Next</a></li></ul></nav>', [
                'page' => 1,
                'pages' => 5,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li><a href="?page=4">Prev</a></li><li><a href="?page=1">1</a></li><li><span>&hellip;</span></li><li><a href="?page=3">3</a></li><li><a href="?page=4">4</a></li><li class="active"><a href="?page=5">5</a></li></ul></nav>', [
                'page' => 5,
                'pages' => 5,
            ]],
            ['<nav aria-label="Page navigation"><ul class="pagination"><li><a href="?page=1">Prev</a></li><li><a href="?page=1">1</a></li></ul></nav>', [
                'page' => 2,
                'pages' => 1,
            ]],
        ];
    }

    /**
     * @dataProvider paginationProvider
     */
    public function testPagination($expected, array $setup)
    {
        $this->assertEquals($expected, $this->html->pagination($setup));
    }

    public function ulistProvider()
    {
        return [
            ['<ul><li><a href="foo">foo</a></li><li class="active"><a href="bar">bar</a><ul><li><a href="bar/foo">Depth 2</a></li><li class="active"><a href="bar/baz">Depth 3</a><ul><li class="active"><a href="bar/baz/bar">Depth 4 (no child)</a></li></ul></li></ul></li><li><a href="qux">qux</a></li></ul>', [
                [
                    'foo' => [],
                    'bar' => ['items' => [
                        'bar/foo' => ['label' => 'Depth 2'],
                        'bar/baz' => [
                            'label' => 'Depth 3',
                            'items' => [
                                'bar/baz/foo' => [
                                    'label' => 'Depth 4',
                                    'items' => [],
                                ],
                                'bar/baz/bar' => [
                                    'label' => 'Depth 4 (no child)',
                                ],
                            ],
                        ],
                        'bar/qux' => ['hide' => true],
                    ]],
                    'qux' => [],
                ], 'bar/baz/bar', 'active', null, function ($label, $item) {
                    return [$label, $item];
                }, function ($label, $item, $sub, $li, $a) {
                    return [$label, $item, $sub, $li, $a];
                },
            ]],
        ];
    }

    /**
     * @dataProvider ulistProvider
     */
    public function testUlist($expected, array $args)
    {
        $this->assertEquals($expected, $this->html->ulist(...$args));
    }
}
