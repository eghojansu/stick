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

use Fal\Stick\Core;
use Fal\Stick\Html;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    private $html;

    public function setUp()
    {
        $this->html = new Html($this->fw = new Core('phpunit-test'));
    }

    public function testAttr()
    {
        $attr = array(
            'foo' => 'bar',
            'bar' => null,
            'baz' => false,
            'qux' => true,
            'quux',
        );
        $expected = ' foo="bar" qux quux';

        $this->assertEquals($expected, $this->html->attr($attr));
    }

    /**
     * @dataProvider tagProvider
     */
    public function testTag($expected, $tag, $attr = null, $pair = false, $content = null)
    {
        $this->assertEquals($expected, $this->html->tag($tag, $attr, $pair, $content));
    }

    /**
     * @dataProvider paginationProvider
     */
    public function testPagination($alias, $page, $max, $config, $content, $query = null)
    {
        $this->fw
            ->route('GET foo /', 'foo')
            ->route('GET bar /bar/@bar', 'bar')
            ->route('GET baz /baz/@baz*', 'baz')
            ->route('GET qux /qux/@qux/@quux*', 'qux')
        ;
        $this->fw->set('ALIAS', $alias);
        $this->fw->set('GET', $query);

        $expected = $content ? '<nav aria-label="Page navigation"><ul class="pagination">'.$content.'</ul></nav>' : $content;

        $this->assertEquals($expected, $this->html->pagination($page, $max, $config));
    }

    /**
     * @dataProvider ulinksProvider
     */
    public function testUlinks($items, $activeRoute, $config, $expected)
    {
        $this->fw
            ->route('GET foo /foo', 'foo')
            ->route('GET bar /bar/@bar', 'foo')
            ->route('GET baz /baz/@bar*', 'foo')
            ->route('GET qux /qux/@qux/@quux*', 'foo')
        ;

        $this->assertEquals($expected, $this->html->ulinks($items, $activeRoute, $config));
    }

    public function tagProvider()
    {
        return array(
            array('<form>', 'form'),
            array('<form method="post">', 'form', array('method' => 'post')),
            array('<div>text</div>', 'div', null, true, 'text'),
        );
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
}
