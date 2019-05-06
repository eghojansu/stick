<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Security;

use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\TestSuite\Classes\SimpleUser;

class AuthProvider
{
    public function getUser()
    {
        return array(
            array(null),
            array(new SimpleUser('1', 'foo', 'bar'), null, function ($auth) {
                $auth->setUser(new SimpleUser('1', 'foo', 'bar'));
            }),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'COOKIE.user_login_id' => '1',
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'SESSION.user_login_id' => '1',
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'SESSION.impersonate_user_login_id' => '1',
            )),
        );
    }

    public function isGranted()
    {
        return array(
            array(false, 'foo'),
            array(true, 'foo', array(
                'SESSION.user_login_id' => 1,
            )),
            array(true, 'foo', array(
                'SESSION.user_login_id' => 2,
            )),
            array(true, 'bar', array(
                'SESSION.user_login_id' => 2,
            )),
            array(true, 'foo,bar', array(
                'SESSION.user_login_id' => 2,
            )),
            array(false, 'foo', null, function () {
                return false;
            }),
        );
    }

    public function login()
    {
        return array(
            array(true, null, 'foo', 'bar'),
            array(false, null, 'foo', 'bar', false),
            array(false, 'Empty credentials.', 'foo', null),
            array(false, 'Invalid credentials.', 'foobar', 'bar'),
            array(false, 'Your credentials is expired.', 'baz', 'qux'),
        );
    }

    public function guard()
    {
        return array(
            'default' => array(
                false,
                null,
            ),
            'at excluded path' => array(
                false,
                null,
                array('excludes' => '/'),
            ),
            'at login, and not login yet' => array(
                false,
                null,
                array(
                    'rules' => array(
                        '/foo' => 'foo',
                    ),
                ),
                array('PATH' => '/login'),
            ),
            'at login, but has been login' => array(
                true,
                '/dashboard',
                array(
                    'rules' => array(
                        '/foo' => array(
                            'home' => '/dashboard',
                            'roles' => 'foo',
                        ),
                    ),
                ),
                array(
                    'PATH' => '/login',
                    'SESSION.user_login_id' => 1,
                ),
            ),
            'violation' => array(
                true,
                '/login',
                array(
                    'rules' => array(
                        '/dashboard' => array(
                            'roles' => 'foo',
                            'login' => '/login',
                        ),
                    ),
                ),
                array(
                    'PATH' => '/dashboard',
                ),
            ),
            'exception' => array(
                'No roles for rule: /dashboard.',
                null,
                array(
                    'rules' => array(
                        '/dashboard' => array(
                            'login' => '/login',
                        ),
                    ),
                ),
                null,
                'LogicException',
            ),
        );
    }

    public function basic()
    {
        return array(
            array(
                true,
                MyTestCase::response('error.txt', array(
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Unauthorized',
                    '%code%' => 401,
                )),
                array(),
            ),
            array(
                false,
                '',
                array(
                    'HEADERS.Authorization' => 'realm='.base64_encode('foo:bar'),
                ),
            ),
            array(
                false,
                '',
                array(
                    'SERVER.PHP_AUTH_USER' => 'foo',
                    'SERVER.PHP_AUTH_PW' => 'bar',
                ),
            ),
        );
    }

    public function impersonateOn()
    {
        return array(
            array(true, null, 'foo'),
            array(false, 'User not found.', 'unknown'),
            array(false, 'User credentials is expired.', 'baz'),
        );
    }
}
