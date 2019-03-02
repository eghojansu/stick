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

namespace Fal\Stick\Test\Web\Security;

use Fal\Stick\TestSuite\TestCase;
use Fal\Stick\Web\Request;
use Fixture\SimpleUser;

class AuthTest extends TestCase
{
    public function setup()
    {
        $this->prepare()->auth->getProvider()
            ->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')))
            ->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')))
            ->addUser(new SimpleUser('3', 'baz', 'qux', array('baz'), true));
    }

    public function teardown()
    {
        $this->session->destroy();
    }

    public function testGetProvider()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Security\\InMemoryUserProvider', $this->auth->getProvider());
    }

    public function testGetEncoder()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Security\\PlainPasswordEncoder', $this->auth->getEncoder());
    }

    public function testGetOptions()
    {
        $expected = array(
            'excludes' => array(),
            'logout' => array(),
            'rules' => array(),
            'role_hierarchy' => array(),
            'lifetime' => '1 week',
        );

        $this->assertEquals($expected, $this->auth->getOptions());
    }

    public function testSetOptions()
    {
        $expected = array(
            'excludes' => array('/foo'),
            'logout' => array(),
            'rules' => array(),
            'role_hierarchy' => array(),
            'lifetime' => '1 week',
        );

        $this->assertEquals($expected, $this->auth->setOptions(array('excludes' => '/foo'))->getOptions());
    }

    public function testGetCookieUserId()
    {
        $this->assertNull($this->auth->getCookieUserId());
    }

    public function testGetSessionUserId()
    {
        $this->assertNull($this->auth->getSessionUserId());
    }

    public function testIsLogged()
    {
        $this->assertFalse($this->auth->isLogged());
    }

    public function testLogout()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Response', $this->auth->logout());
    }

    public function testLogin()
    {
        $user = new SimpleUser('1', 'foo', 'bar');
        $remember = true;

        $this->assertInstanceOf('Fal\\Stick\\Web\\Response', $response = $this->auth->login($user, $remember));
        $this->assertTrue($response->isOK());
    }

    /**
     * @dataProvider getUserProvider
     */
    public function testGetUser($expected, $sets)
    {
        if (isset($sets['loaduser'])) {
            $this->eventDispatcher->on('auth.loaduser', $sets['loaduser']);
        }

        if (isset($sets['session'])) {
            $this->session->set('user_login_id', $sets['session']);
        }

        if (isset($sets['request'])) {
            $this->requestStack->push($sets['request']);
        }

        $this->assertEquals($expected, $this->auth->getUser());

        // second call
        $this->assertEquals($expected, $this->auth->getUser());
    }

    public function testGetUserRoles()
    {
        $this->session->set('user_login_id', '1');
        $this->auth->setOptions(array(
            'role_hierarchy' => array(
                'foo' => 'foobar',
            ),
        ));

        $expected = array(
            'IS_AUTHENTICATED_ANONYMOUSLY',
            'foo',
            'foobar',
        );

        $this->assertEquals($expected, $this->auth->getUserRoles());
    }

    /**
     * @dataProvider isGrantedProvider
     */
    public function testIsGranted($expected, $roles, $sets = null)
    {
        if (isset($sets['vote'])) {
            $this->eventDispatcher->on('auth.vote', $sets['vote']);
        }

        if (isset($sets['session'])) {
            $this->session->set('user_login_id', $sets['session']);
        }

        $this->assertEquals($expected, $this->auth->isGranted($roles));
    }

    /**
     * @dataProvider attemptProvider
     */
    public function testAttempt($expected, $expectedMessage, $username, $password, $remember = false, $message = null)
    {
        if ($expected) {
            $this->assertInstanceOf($expected, $this->auth->attempt($username, $password, $remember, $message));
        } else {
            $this->assertEquals($expected, $this->auth->attempt($username, $password, $remember, $message));
        }

        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @dataProvider guardProvider
     */
    public function testGuard($expected, $redirect, $request, $options = null, $sets = null)
    {
        if (isset($sets['request'])) {
            $this->requestStack->push($sets['request']);
        }

        if (isset($sets['session'])) {
            $this->session->set('user_login_id', $sets['session']);
        }

        if ($options) {
            $this->auth->setOptions($options);
        }

        if ($expected) {
            $this->assertInstanceOf($expected, $actual = $this->auth->guard($request));

            if ($redirect) {
                $this->assertEquals($redirect, $actual->getTargetUrl());
            }
        } else {
            $this->assertEquals($expected, $this->auth->guard($request));
        }
    }

    public function testDenyAccessUnlessGranted()
    {
        $this->expectException('Fal\\Stick\\Web\\Exception\\ForbiddenException');
        $this->expectExceptionMessage('Access denied.');

        $this->auth->denyAccessUnlessGranted('foo');
    }

    public function getUserProvider()
    {
        $request = Request::create('/');
        $request->cookies->set('user_login_id', '1');

        return array(
            array(null, array()),
            array(new SimpleUser('1', 'foo', 'bar'), array(
                'loaduser' => function ($event) {
                    $event->setUser(new SimpleUser('1', 'foo', 'bar'));
                },
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'request' => $request,
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'session' => '1',
            )),
        );
    }

    public function isGrantedProvider()
    {
        return array(
            array(false, 'foo'),
            array(true, 'foo', array(
                'session' => 1,
            )),
            array(true, 'foo', array(
                'session' => 2,
            )),
            array(true, 'bar', array(
                'session' => 2,
            )),
            array(true, 'foo,bar', array(
                'session' => 2,
            )),
            array(false, 'foo', array(
                'vote' => function ($event) {
                    $event->stopPropagation();
                },
            )),
        );
    }

    public function attemptProvider()
    {
        return array(
            array(null, 'Invalid credentials.', 'foobar', 'bar'),
            array('Fal\\Stick\\Web\\Response', null, 'foo', 'bar', true),
            array(null, 'Your credentials is expired.', 'baz', 'qux'),
        );
    }

    public function guardProvider()
    {
        $data = array();

        // default
        $data[] = array(null, null, Request::create('/foo'));

        // in excludes path
        $data[] = array(null, null, Request::create('/foo'), array(
            'excludes' => '/foo',
        ));

        // in logout path
        $request = Request::create('/logout');
        $request->cookies->set('user_login_id', '1');
        $data[] = array('Fal\\Stick\\Web\\Response', 'http://localhost/foo', $request, array(
            'logout' => array('/logout' => '/foo'),
        ), array(
            'request' => $request,
        ));

        // in login path, not login
        $data[] = array(null, null, Request::create('/login'), array(
            'rules' => array(
                '/foo' => 'foo',
            ),
        ));

        // in login path, has been login
        $data[] = array('Fal\\Stick\\Web\\Response', 'http://localhost/', Request::create('/login'), array(
            'rules' => array(
                '/foo' => 'foo',
            ),
        ), array(
            'session' => 1,
            'request' => Request::create('/'),
        ));

        // violation
        $data[] = array('Fal\\Stick\\Web\\Response', 'http://localhost/please-login', Request::create('/restricted'), array(
            'rules' => array(
                '/restricted' => array(
                    'roles' => 'foo',
                    'login' => '/please-login',
                ),
            ),
        ), array(
            'request' => Request::create('/'),
        ));

        return $data;
    }
}
