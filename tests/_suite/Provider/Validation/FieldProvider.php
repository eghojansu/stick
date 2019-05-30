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

class FieldProvider
{
    public function parse()
    {
        return array(
            array(
                array(),
                '',
            ),
            array(
                array(
                    'foo' => array(),
                ),
                'foo',
            ),
            array(
                array(
                    'foo' => array(),
                    'bar' => array(),
                ),
                'foo|bar',
            ),
            array(
                array(
                    'foo' => array(1),
                    'bar' => array('arg'),
                    'baz' => array(array(0, 1, 2)),
                    'qux' => array(array('foo' => 'bar')),
                    'quux' => array(1, 'arg', array(0, 1, 2), array('foo' => 'bar')),
                ),
                'foo:1|bar:arg|baz:[0,1,2]|qux:{"foo":"bar"}|quux:1,arg,[0,1,2],{"foo":"bar"}',
            ),
        );
    }

    public function getSize()
    {
        return array(
            'length' => array(
                3,
                'bar',
            ),
            'integer' => array(
                11,
                '11',
            ),
            'integer size' => array(
                2,
                11,
                array(false),
            ),
            'array' => array(
                1,
                array('foo'),
            ),
            'file' => array(
                1,
                'foo',
                array(),
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                            'size' => 1024,
                        ),
                    ),
                ),
            ),
        );
    }
}
