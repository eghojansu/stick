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
            'valid' => array(
                array(
                    'success' => true,
                    'errors' => array(),
                    'data' => array(
                        'foo' => 'bar',
                        'bar' => 'bar ',
                    ),
                ),
                array(
                    'foo' => 'bar ',
                    'bar' => 'bar ',
                ),
                array(
                    'foo' => 'trim|required',
                    'bar' => 'required',
                ),
            ),
            'error' => array(
                array(
                    'success' => false,
                    'errors' => array(
                        'foo' => 'This value should not be blank.',
                        'bar' => 'This value should not be blank.',
                        'date' => "This value should be after 'tomorrow'.",
                    ),
                    'data' => array(),
                ),
                array(
                    'date' => 'today',
                ),
                array(
                    'foo' => 'required',
                    'bar' => 'required',
                    'date' => 'required|after:tomorrow',
                ),
            ),
            'error custom' => array(
                array(
                    'success' => false,
                    'errors' => array(
                        'foo' => 'Custom error',
                    ),
                    'data' => array(),
                ),
                array(),
                array(
                    'foo' => 'required',
                ),
                array(
                    'foo.required' => 'Custom error',
                ),
            ),
            'optional' => array(
                array(
                    'success' => true,
                    'errors' => array(),
                    'data' => array(
                        'bar' => 'barval',
                    ),
                ),
                array(
                    'bar' => 'barval',
                ),
                array(
                    'foo' => 'optional',
                    'bar' => 'required',
                ),
            ),
            'required if' => array(
                array(
                    'success' => true,
                    'errors' => array(),
                    'data' => array(
                        'foo' => '',
                        'qux' => 'quxval',
                    ),
                ),
                array(
                    'foo' => ' ',
                    'bar' => 'barval',
                    'baz' => 'bazval',
                    'qux' => 'quxval',
                ),
                array(
                    'foo' => 'trim',
                    'bar' => 'requiredif:foo',
                    'qux' => 'requiredif:baz,bazval',
                ),
            ),
        );
    }
}
