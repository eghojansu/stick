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

class ElementProvider
{
    public function attr()
    {
        return array(
            array('', null),
            array(' foo="bar" qux quux', array('foo' => 'bar', 'bar' => false, 'baz' => null, 'qux' => true, 'quux')),
            array(' foo="{\\"foo\\":1}"', array('foo' => array('foo' => 1))),
        );
    }

    public function tag()
    {
        return array(
            array('<br>', 'br'),
            array('<a href="#">Foo</a>', 'a', array('href' => '#'), true, 'Foo'),
        );
    }
}
