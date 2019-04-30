<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Validation;

class ValidatorProvider
{
    public function validate()
    {
        return array(
            array(
                true,
                array(),
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                array('foo' => 'required'),
            ),
            'optional and value empty' => array(
                true,
                array(),
                array(),
                array('foo' => ''),
                array('foo' => 'optional'),
            ),
            'optional and value not empty' => array(
                true,
                array(),
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                array('foo' => 'optional'),
            ),
            array(
                false,
                array('foo' => array('This value should not be blank.')),
                array(),
                array(),
                array('foo' => 'required'),
            ),
            array(
                false,
                array('foo' => array('Please fill foo!')),
                array(),
                array(),
                array('foo' => 'required'),
                array('foo.required' => 'Please fill foo!'),
            ),
            array(
                true,
                array(),
                array('foo' => 'bar', 'bar' => 'baz'),
                array('foo' => 'bar', 'bar' => 'baz', 'baz' => 'qux'),
                array('foo' => 'required', 'bar' => 'required'),
            ),
            array(
                'Validation rule not exists: foo.',
                array(),
                array(),
                array(),
                array('foo' => 'foo'),
                null,
                'LogicException',
            ),
        );
    }
}
