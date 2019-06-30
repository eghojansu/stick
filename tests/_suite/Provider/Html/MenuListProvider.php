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

namespace Fal\Stick\TestSuite\Provider\Html;

class MenuListProvider
{
    public function build()
    {
        return array(
            array(
                '',
                array(),
            ),
            array(
                '<ul>'.
                '<li><a href="/foo">Foo</a></li>'.
                '</ul>',
                array(
                    'Foo' => 'foo',
                ),
            ),
            array(
                '<ul>'.
                '<li class="active"><a href="/foo">Foo</a></li>'.
                '<li class="active">'.
                    '<a href="#">Bar</a>'.
                    '<ul class="foo">'.
                        '<li class="active"><a href="/foo">Bar 1</a></li>'.
                    '</ul>'.
                    '</li>'.
                '</ul>',
                array(
                    'Foo' => 'foo',
                    'Bar' => array(
                        'items' => array(
                            'Bar 1' => 'foo',
                        ),
                    ),
                ),
                'foo',
                array(
                    'parent_wrapper_attr' => array('class' => 'foo'),
                ),
            ),
            'with roles' => array(
                '<ul>'.
                '<li><a href="/foo">Foo</a></li>'.
                '<li><a href="/foo">Foo Too</a></li>'.
                '<li>'.
                    '<a href="#">Bar</a>'.
                    '<ul>'.
                        '<li><a href="/bar/bar">Bar bar</a></li>'.
                    '</ul>'.
                    '</li>'.
                '</ul>',
                array(
                    'Foo' => 'foo',
                    'Foo Too' => array(
                        'route' => 'foo',
                        'roles' => 'foo',
                    ),
                    'Bar' => array(
                        'items' => array(
                            'Bar bar' => array(
                                'route' => 'bar',
                                'args' => 'bar=bar',
                                'roles' => array('bar'),
                            ),
                            'Bar baz' => array(
                                'route' => 'bar',
                                'args' => 'bar=baz',
                                'roles' => 'baz',
                            ),
                        ),
                    ),
                ),
            ),
            array(
                '<ul>'.
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
                array(
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
                                'args' => array('baz' => 1),
                            ),
                            'Baz 234' => array(
                                'route' => 'baz',
                                'args' => array('baz' => array(2, 3, 4)),
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
                ),
            ),
            'custom content' => array(
                '<ul>'.
                '<li>Custom</li>'.
                '</ul>',
                array(
                    'Foo' => array(
                        'content' => 'Custom',
                    ),
                ),
            ),
        );
    }
}
