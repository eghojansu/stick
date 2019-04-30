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

class PaginationListProvider
{
    public function build()
    {
        return array(
            'invalid page' => array(
                '',
                0,
                1,
            ),
            'no page' => array(
                '',
                1,
                0,
            ),
            array(
                '<li class="active"><a href="/?page=1">1</a></li>',
                1,
                1,
                array(
                    'ALIAS' => 'foo',
                ),
            ),
            array(
                '<li class="active"><a href="/?page=1">1</a></li>'.
                '<li><a href="/?page=2">2</a></li>',
                1,
                2,
                array(
                    'ALIAS' => 'foo',
                ),
            ),
            array(
                '<li><a href="/?page=1">1</a></li>'.
                '<li class="active"><a href="/?page=2">2</a></li>'.
                '<li><a href="/?page=3">3</a></li>',
                2,
                3,
                array(
                    'ALIAS' => 'foo',
                ),
            ),
            array(
                '<li class="active"><a href="/?page=1&bar=baz">1</a></li>',
                1,
                1,
                array(
                    'ALIAS' => 'foo',
                    'GET' => array('bar' => 'baz'),
                ),
            ),
            array(
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
                5,
                9,
                array(
                    'ALIAS' => 'foo',
                ),
            ),
            array(
                '<li class="active"><a href="/bar/baz?page=1">1</a></li>',
                1,
                1,
                array(
                    'ALIAS' => 'bar',
                    'PARAMS' => array('bar' => 'baz'),
                ),
            ),
            array(
                '<li class="active"><a href="/baz/bar/baz?page=1">1</a></li>',
                1,
                1,
                array(
                    'ALIAS' => 'baz',
                    'PARAMS' => array('baz' => array('bar', 'baz')),
                ),
            ),
            array(
                '<li class="active"><a href="/qux/qux/bar/baz?page=1">1</a></li>',
                1,
                1,
                array(
                    'ALIAS' => 'qux',
                    'PARAMS' => array('qux' => 'qux', 'quux' => array('bar', 'baz')),
                ),
            ),
        );
    }
}
