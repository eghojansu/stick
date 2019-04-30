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

namespace Fal\Stick\Test\Security;

use Fal\Stick\Fw;
use Fal\Stick\Security\Jwt;
use Fal\Stick\Security\Auth;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\TestSuite\Classes\SimpleUser;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

class AuthTest extends MyTestCase
{
    private $fw;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->fw->set('QUIET', true);
    }

    public function teardown(): void
    {
        header_remove();
        $this->fw->rem('SESSION');
    }

    protected function createInstance()
    {
        $provider = new InMemoryUserProvider();
        $provider
            ->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')))
            ->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')))
            ->addUser(new SimpleUser('3', 'baz', 'qux', array('baz'), true));

        return new Auth($this->fw, $provider, new PlainPasswordEncoder());
    }

    public function testIsLogged()
    {
        $this->assertFalse($this->auth->isLogged());
    }

    public function testError()
    {
        $this->assertNUll($this->auth->error());
    }

    public function testSetUser()
    {
        $user = new SimpleUser('1', 'foo', 'bar');

        $this->assertSame($user, $this->auth->setUser($user)->getUser());
    }

    public function testLogout()
    {
        $this->fw->set('POST', array(
            'username' => 'foo',
            'password' => 'bar',
        ));
        $this->auth->login(true);

        // this will be intercepted
        $this->fw->on('auth.logout', function ($auth) {
            return false;
        });
        $this->auth->logout();
        $this->assertEquals('1', $this->auth->getUser()->getId());

        // remove interceptor
        $this->fw->on('auth.logout', null);
        $this->auth->logout();
        $this->assertNUll($this->auth->getUser());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthProvider::getUser
     */
    public function testGetUser($expected, $hive = null, $loader = null)
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        $this->fw->on('auth.loaduser', $loader);

        $this->assertEquals($expected, $this->auth->getUser());
        // second call
        $this->assertEquals($expected, $this->auth->getUser());
    }

    public function testGetUserRoles()
    {
        $this->fw->set('SESSION.user_login_id', '1');
        $this->auth->options->resolve(array(
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
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthProvider::isGranted
     */
    public function testIsGranted($expected, $roles, $hive = null, $voter = null)
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        $this->fw->on('auth.vote', $voter);

        $this->assertEquals($expected, $this->auth->isGranted($roles));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthProvider::login
     */
    public function testLogin($expected, $message, $username, $password, $login = true)
    {
        $this->auth->options->set('remember_cookie', true);
        $this->fw->set('POST', compact('username', 'password'));

        $this->assertEquals($expected, $this->auth->login($login));
        $this->assertEquals($message, $this->auth->error());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthProvider::guard
     */
    public function testGuard($expected, $redirect, $options = null, $hive = null, $exception = null)
    {
        $this->fw->on('fw.reroute', function ($fw, $url) {
            $fw->set('rerouted', $url);
        });

        if ($hive) {
            $this->fw->mset($hive);
        }

        if ($options) {
            $this->auth->options->resolve($options);
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->auth->guard();

            return;
        }

        $this->assertEquals($expected, $this->auth->guard());
        $this->assertEquals($redirect, $this->fw->get('rerouted'));
    }

    public function testDenyAccessUnlessGranted()
    {
        $this->auth->denyAccessUnlessGranted('foo');

        $this->assertEquals("HTTP 403 (GET /)\nAccess denied.\n", $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthProvider::basic
     */
    public function testBasic($expected, $output, $hive)
    {
        $this->fw->mset($hive);

        $this->assertEquals($expected, $this->auth->basic());
        $this->assertEquals($output, $this->fw->get('OUTPUT'));
    }

    public function testJwt()
    {
        $jwt = new Jwt('foo');
        $toUser = function (array $user) {
            return new SimpleUser($user['id'], $user['username'], null, $user['roles'], $user['expired']);
        };
        $user = array(
            'id' => '1',
            'username' => 'foo',
            'roles' => array('admin'),
            'expired' => false,
        );
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjEiLCJ1c2VybmFtZSI6ImZvbyIsInJvbGVzIjpbImFkbWluIl0sImV4cGlyZWQiOmZhbHNlfQ.q_g29MKCotCDEUaGGmwqcRzu8uXdmCKU8RWTCc60hxY';
        $this->fw->set('HEADERS.Authorization', 'Bearer '.$token);

        $this->assertFalse($this->auth->jwt($jwt, $toUser));
        $this->assertEquals('foo', $this->auth->getUser()->getUsername());
    }
}
