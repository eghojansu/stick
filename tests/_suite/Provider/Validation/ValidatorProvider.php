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
                        'baz' => null,
                    ),
                ),
                array(
                    'foo' => 'bar ',
                    'bar' => 'bar ',
                ),
                array(
                    'foo' => 'trim|required',
                    'bar' => 'required',
                    'baz' => 'optional',
                ),
            ),
            'error' => array(
                array(
                    'success' => false,
                    'errors' => array(
                        'foo' => 'This value should not be blank.',
                        'bar' => 'This value should not be blank.',
                    ),
                    'data' => array(
                        'foo' => null,
                        'bar' => null,
                    ),
                ),
                array(),
                array(
                    'foo' => 'required',
                    'bar' => 'required',
                ),
            ),
            'error custom' => array(
                array(
                    'success' => false,
                    'errors' => array(
                        'foo' => 'Custom error',
                    ),
                    'data' => array(
                        'foo' => null,
                    ),
                ),
                array(),
                array(
                    'foo' => 'required',
                ),
                array(
                    'foo.required' => 'Custom error',
                ),
            ),
        );
    }
}
