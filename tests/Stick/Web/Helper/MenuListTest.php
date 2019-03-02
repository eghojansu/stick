<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 13, 2019 01:34
 */

namespace Fal\Stick\Test\Web\Helper;

use Fal\Stick\Web\Helper\MenuList;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStack;
use Fal\Stick\Web\Router\Router;
use Fal\Stick\Web\UrlGenerator;
use PHPUnit\Framework\TestCase;

class MenuListTest extends TestCase
{
    private $list;

    public function setUp()
    {
        $router = new Router();
        $requestStack = new RequestStack();
        $urlGenerator = new UrlGenerator($requestStack, $router);

        $router
            ->route('GET foo /foo', 'foo')
            ->route('GET bar /bar/@bar', 'foo')
            ->route('GET baz /baz/@bar*', 'foo')
            ->route('GET qux /qux/@qux/@quux*', 'foo')
        ;
        $requestStack->push(Request::create('/'));

        $this->list = new MenuList($urlGenerator);
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild($items, $activeRoute, $config, $expected)
    {
        $this->assertEquals($expected, $this->list->build($items, $activeRoute, $config));
    }

    public function buildProvider()
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
                            'args' => array('bar' => 1),
                        ),
                        'Baz 234' => array(
                            'route' => 'baz',
                            'args' => array('bar' => array(2, 3, 4)),
                        ),
                    ),
                ),
                'Qux' => array(
                    'items' => array(
                        'Qux 1 1' => array(
                            'route' => 'qux',
                            'args' => array('qux' => 1, 'quux' => 1),
                        ),
                        'Qux 1 234' => array(
                            'route' => 'qux',
                            'args' => array('qux' => 1, 'quux' => array(2, 3, 4)),
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
