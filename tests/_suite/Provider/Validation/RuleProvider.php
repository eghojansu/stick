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

class RuleProvider
{
    public function laravel()
    {
        return array(
            'accepted' => array(
                true,
                'accepted',
                'true',
            ),
            'accepted on' => array(
                true,
                'accepted',
                'on',
            ),
            'accepted false' => array(
                false,
                'accepted',
                'false',
            ),
            'after' => array(
                true,
                'after',
                'tomorrow',
                array('today'),
            ),
            'after false' => array(
                false,
                'after',
                'yesterday',
                array('today'),
            ),
            'after false 2' => array(
                false,
                'after',
                'today',
                array('today'),
            ),
            'afterOrEqual' => array(
                true,
                'afterOrEqual',
                'today',
                array('today'),
            ),
            'afterOrEqual false' => array(
                false,
                'afterOrEqual',
                'yesterday',
                array('today'),
            ),
            'alpha' => array(
                true,
                'alpha',
                'alpha',
            ),
            'alpha false' => array(
                false,
                'alpha',
                'alpha -',
            ),
            'aldash' => array(
                true,
                'aldash',
                '_aldash-',
            ),
            'aldash false' => array(
                false,
                'aldash',
                '_aldash-1',
            ),
            'alnum' => array(
                true,
                'alnum',
                'alnum123',
            ),
            'alnum false' => array(
                false,
                'alnum',
                'alnum123@',
            ),
            'array' => array(
                true,
                'array',
                array(),
            ),
            'array false' => array(
                false,
                'array',
                'foo',
            ),
            'before' => array(
                true,
                'before',
                'yesterday',
                array('today'),
            ),
            'before false' => array(
                false,
                'before',
                'tomorrow',
                array('today'),
            ),
            'beforeOrEqual' => array(
                true,
                'beforeOrEqual',
                'today',
                array('today'),
            ),
            'beforeOrEqual false' => array(
                false,
                'beforeOrEqual',
                'tomorrow',
                array('today'),
            ),
            'between' => array(
                true,
                'between',
                1,
                array(1, 2),
            ),
            'between false' => array(
                false,
                'between',
                3,
                array(1, 2),
            ),
            'bool' => array(
                true,
                'bool',
                '0',
            ),
            'bool false' => array(
                false,
                'bool',
            ),
            'confirmed' => array(
                true,
                'confirmed',
                'yes',
                array(),
                array(
                    'foo' => 'yes',
                    'foo_confirmation' => 'yes',
                ),
            ),
            'confirmed custom field' => array(
                true,
                'confirmed',
                'yes',
                array('bar'),
                array(
                    'foo' => 'yes',
                    'bar' => 'yes',
                ),
            ),
            'confirmed false' => array(
                false,
                'confirmed',
                'yes',
            ),
            'convert' => array(
                '1991-10-20 00:00:00',
                'convert',
                'Oct 20, 1991',
                array('Y-m-d H:i:s'),
            ),
            'date' => array(
                true,
                'date',
                'Oct 20, 1991',
            ),
            'date false' => array(
                false,
                'date',
            ),
            'dateEquals' => array(
                true,
                'dateEquals',
                'tomorrow',
                array('tomorrow'),
            ),
            'dateEquals false' => array(
                false,
                'dateEquals',
                'today',
                array('tomorrow'),
            ),
            'dateFormat' => array(
                true,
                'dateFormat',
                'Oct 20, 1991',
                array('M d, Y'),
            ),
            'dateFormat false' => array(
                false,
                'dateFormat',
                '1991-10-20',
                array('M d, Y'),
            ),
            'different' => array(
                true,
                'different',
                'bar', // not used
                array('bar'),
                array(
                    'foo' => 'bar',
                    'bar' => 'foo',
                ),
            ),
            'different false' => array(
                false,
                'different',
                'foo', // not used
                array('bar'),
                array(
                    'foo' => 'foo',
                    'bar' => 'foo',
                ),
            ),
            'digits' => array(
                true,
                'digits',
                11,
                array(2),
            ),
            'digits false' => array(
                false,
                'digits',
                1,
                array(2),
            ),
            'digitsBetween' => array(
                true,
                'digitsBetween',
                11,
                array(1, 2),
            ),
            'digitsBetween false' => array(
                false,
                'digitsBetween',
                111,
                array(1, 2),
            ),
            'digitsBetween false type' => array(
                false,
                'digitsBetween',
                'foo',
                array(1, 2),
            ),
            'distinct' => array(
                true,
                'distinct',
                array('foo', 'bar'),
            ),
            'distinct false' => array(
                false,
                'distinct',
                array('foo', 'bar', 'foo'),
            ),
            'email' => array(
                true,
                'email',
                'foo@foo.com',
            ),
            'email false' => array(
                false,
                'email',
                'foo@foo',
            ),
            'endsWith' => array(
                true,
                'endsWith',
                'foobar',
                array('bar'),
            ),
            'endsWith false' => array(
                false,
                'endsWith',
                'foobaz',
                array('bar'),
            ),
            'equalto' => array(
                true,
                'equalto',
                '1',
                array(1),
            ),
            'equalto loose' => array(
                true,
                'equalto',
                1,
                array(1),
            ),
            'equalto strict' => array(
                false,
                'equalto',
                '1',
                array(1, true),
            ),
            'file' => array(
                true,
                'file',
                'foo',
                array(),
                null,
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                        ),
                    ),
                ),
            ),
            'gt' => array(
                true,
                'gt',
                11,
                array(10),
            ),
            'gt false' => array(
                false,
                'gt',
                10,
                array(10),
            ),
            'gte' => array(
                true,
                'gte',
                10,
                array(10),
            ),
            'gte false' => array(
                false,
                'gte',
                9,
                array(10),
            ),
            'image' => array(
                true,
                'image',
                'foo',
                array(),
                null,
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                            'type' => 'image/png',
                        ),
                    ),
                ),
            ),
            'in' => array(
                true,
                'in',
                'foo',
                array('foo'),
            ),
            'in false' => array(
                false,
                'in',
                'bar',
                array('foo'),
            ),
            'inField' => array(
                true,
                'inField',
                'foo', // not used
                array('bar'),
                array(
                    'foo' => 'foo',
                    'bar' => array('foo'),
                ),
            ),
            'inField false' => array(
                false,
                'inField',
                'bar', // not used
                array('bar'),
                array(
                    'foo' => 'bar',
                    'bar' => array('foo'),
                ),
            ),
            'integer' => array(
                true,
                'integer',
                '11',
            ),
            'integer false' => array(
                false,
                'integer',
                'foo',
            ),
            'ip' => array(
                true,
                'ip',
                '30.88.29.1',
            ),
            'ip false' => array(
                false,
                'ip',
                '172.300.256.100',
            ),
            'ipv4' => array(
                true,
                'ipv4',
                '30.88.29.1',
            ),
            'ipv4 false' => array(
                false,
                'ipv4',
                '172.300.256.100',
            ),
            'ipv6' => array(
                true,
                'ipv6',
                '2001:DB8:0:0:8:800:200C:417A',
            ),
            'ipv6 false' => array(
                false,
                'ipv6',
                'FF02:0000:0000:0000:0000:0000:0000:0000:0001',
            ),
            'lt' => array(
                true,
                'lt',
                9,
                array(10),
            ),
            'lt false' => array(
                false,
                'lt',
                10,
                array(10),
            ),
            'lte' => array(
                true,
                'lte',
                10,
                array(10),
            ),
            'lte false' => array(
                false,
                'lte',
                11,
                array(10),
            ),
            'max' => array(
                true,
                'max',
                'foo',
                array(3),
            ),
            'max false' => array(
                false,
                'max',
                'foobar',
                array(3),
            ),
            'mimes' => array(
                true,
                'mimes',
                'foo',
                array('png'),
                null,
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                            'type' => 'image/png',
                        ),
                    ),
                ),
            ),
            'mimeTypes' => array(
                true,
                'mimeTypes',
                'foo',
                array('image/png'),
                null,
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                            'type' => 'image/png',
                        ),
                    ),
                ),
            ),
            'mimeTypes false' => array(
                false,
                'mimeTypes',
            ),
            'min' => array(
                true,
                'min',
                'foo',
                array(3),
            ),
            'min false' => array(
                false,
                'min',
                'f',
                array(3),
            ),
            'notequalto' => array(
                true,
                'notequalto',
                '2',
                array(1),
            ),
            'notequalto strict' => array(
                true,
                'notequalto',
                '1',
                array(1, true),
            ),
            'notIn' => array(
                true,
                'notIn',
                'foo',
                array('bar'),
            ),
            'notIn false' => array(
                false,
                'notIn',
                'foo',
                array('foo'),
            ),
            'notInField' => array(
                true,
                'notInField',
                'bar', // not used
                array('bar'),
                array(
                    'foo' => 'bar',
                    'bar' => array('foo'),
                ),
            ),
            'notInField false' => array(
                false,
                'notInField',
                'foo',
                array('bar'),
                array(
                    'foo' => 'foo',
                    'bar' => array('foo'),
                ),
            ),
            'notRegex' => array(
                true,
                'notRegex',
                'foo',
                array('/bar/'),
            ),
            'notRegex false' => array(
                false,
                'notRegex',
                'foo',
                array('/foo/'),
            ),
            'optional' => array(
                true,
                'optional',
            ),
            'numeric' => array(
                true,
                'numeric',
                '11',
            ),
            'numeric false' => array(
                false,
                'numeric',
                'foo',
            ),
            'regex' => array(
                true,
                'regex',
                'foo',
                array('/foo/'),
            ),
            'regex with quote' => array(
                true,
                'regex',
                'foo',
                array('"/foo/"'),
            ),
            'regex false' => array(
                false,
                'regex',
                'foo',
                array('/bar/'),
            ),
            'rejected' => array(
                true,
                'rejected',
                'off',
            ),
            'rejected false' => array(
                false,
                'rejected',
                'on',
            ),
            'required' => array(
                true,
                'required',
            ),
            'required false' => array(
                false,
                'required',
                '',
            ),
            'same' => array(
                true,
                'same',
                'foo',
                array('bar'),
                array(
                    'foo' => 'foo',
                    'bar' => 'foo',
                ),
            ),
            'same false' => array(
                false,
                'same',
                'bar',
                array('bar'),
                array(
                    'foo' => 'bar',
                    'bar' => 'foo',
                ),
            ),
            'size' => array(
                true,
                'size',
                'foo',
                array(3),
            ),
            'size int' => array(
                true,
                'size',
                111,
                array(3),
            ),
            'size array' => array(
                true,
                'size',
                array('foo', 'bar', 'baz'),
                array(3),
            ),
            'size file' => array(
                true,
                'size',
                'foo',
                array(3),
                null,
                array(
                    'FILES' => array(
                        'foo' => array(
                            'error' => UPLOAD_ERR_OK,
                            'type' => 'image/png',
                            'size' => 3072,
                        ),
                    ),
                ),
            ),
            'size false' => array(
                false,
                'size',
                'foobar',
                array(3),
            ),
            'startsWith' => array(
                true,
                'startsWith',
                'foobar',
                array('foo'),
            ),
            'startsWith false' => array(
                false,
                'startsWith',
                'barbar',
                array('foo'),
            ),
            'string' => array(
                true,
                'string',
                'foo',
            ),
            'timezone' => array(
                true,
                'timezone',
                'Asia/Jakarta',
            ),
            'timezone false' => array(
                false,
                'timezone',
                'foo/bar',
            ),
            'trim' => array(
                'foo',
                'trim',
                'foo ',
            ),
            'url' => array(
                true,
                'url',
                'http://example.com',
            ),
            'url false' => array(
                false,
                'url',
                'example',
            ),
        );
    }

    public function mapper()
    {
        return array(
            'exists' => array(
                true,
                'exists',
                array('user'),
            ),
            'exists alternative connection' => array(
                true,
                'exists',
                array('alt.user'),
            ),
            'exists false' => array(
                false,
                'exists',
                array('user'),
                'username',
                'quux',
            ),
            'exists false' => array(
                false,
                'exists',
                array('profile'),
                'fullname',
            ),
            'unique' => array(
                true,
                'unique',
                array('user'),
                'username',
                'quux',
            ),
            'unique self id' => array(
                true,
                'unique',
                array('user', 1),
                'username',
                'foo',
            ),
            'unique self id defined id column' => array(
                true,
                'unique',
                array('user', 1, 'id'),
                'username',
                'bar',
            ),
            'unique self id alternative connection' => array(
                true,
                'unique',
                array('alt.user', 1),
                'username',
                'foo',
            ),
            'unique false' => array(
                false,
                'unique',
                array('user'),
                'username',
                'foo',
            ),
        );
    }

    public function auth()
    {
        return array(
            'auth' => array(
                true,
                1,
            ),
            'auth none' => array(
                true,
                0,
            ),
            'auth alternative' => array(
                true,
                1,
                'bar',
                array('alt'),
            ),
            'auth false' => array(
                false,
                1,
                'baz',
            ),
        );
    }
}
