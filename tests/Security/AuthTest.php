<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Security;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fixture\SimpleUser;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $auth;
    private $core;

    public function setup()
    {
        $this->auth = new Auth($this->core = new Core(), new InMemoryUserProvider(), new PlainPasswordEncoder());
    }

    public function testGetProvider()
    {
        $this->assertInstanceOf('Fal\\Stick\\Security\\InMemoryUserProvider', $this->auth->getProvider());
    }

    public function testGetEncoder()
    {
        $this->assertInstanceOf('Fal\\Stick\\Security\\PlainPasswordEncoder', $this->auth->getEncoder());
    }

    public function testGetOptions()
    {
        $this->assertEquals([
            'excludes'      => [],
            'logout'        => [],
            'rules'         => [],
            'roleHierarchy' => [],
            'lifetime'      => 1541932314,
        ], $this->auth->getOptions());
    }

    public function testSetOptions()
    {
        $this->auth->setOptions([
            'excludes' => '/foo',
            'lifetime' => null,
        ]);

        $this->assertEquals([
            'excludes'      => ['/foo'],
            'logout'        => [],
            'rules'         => [],
            'roleHierarchy' => [],
            'lifetime'      => 1541932314,
        ], $this->auth->getOptions());
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
        $this->assertSame($this->auth, $this->auth->logout());
    }

    public function testSetUser()
    {
        $this->assertEquals('foo', $this->auth->setUser(new SimpleUser(1, 'foo', 'bar'))->getUser()->getUsername());
    }

    public function testLogin()
    {
        $this->assertEquals('foo', $this->auth->login(new SimpleUser(1, 'foo', 'bar'), true)->getUser()->getUsername());
        $this->assertEquals(1, $this->core->get('SESSION.user_login_id'));
        $this->assertEquals(1, $this->core->get('COOKIE_SET.user_login_id.arguments.1'));
    }

    /**
     * @dataProvider guardProvider
     */
    public function testGuard($path, $user, $expected, $expectedBool)
    {
        $this->core->set('QUIET', true);
        $this->core->set('PATH', $path);

        $this->core->route('GET /admin-area', function () {
            return 'Admin Area';
        });
        $this->core->route('GET /login', function () {
            return 'Login';
        });

        $this->auth->setOptions([
            'rules' => [
                '/admin-area' => [
                    'roles' => 'ROLE_ADMIN',
                    'home'  => '/admin-area',
                ],
            ],
            'logout' => [
                '/logout' => '/login',
            ],
            'excludes' => '/exclude',
        ]);
        $this->auth->setUser($user);

        $this->assertEquals($expectedBool, $this->auth->guard());
        $this->assertEquals($expected, $this->core->get('OUTPUT'));
    }

    /**
     * @dataProvider isGrantedProvider
     */
    public function testIsGranted($expected, $user, $roles)
    {
        $this->auth->setOptions([
            'roleHierarchy' => [
                'ROLE_ADMIN'       => 'ROLE_USER',
                'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_FOO'],
            ],
        ]);
        $this->auth->setUser($user);

        $this->assertEquals($expected, $this->auth->isGranted($roles));
    }

    /**
     * @dataProvider attemptProvider
     */
    public function testAttempt($expected, $username, $password, $expectedMessage = null)
    {
        $provider = $this->auth->getProvider();
        $provider->addUser(new SimpleUser('foo', 'foo', 'bar'));
        $provider->addUser(new SimpleUser('bar', 'bar', 'baz', null, true));

        $this->assertEquals($expected, $this->auth->attempt($username, $password, null, $message));
        $this->assertEquals($expectedMessage, $message);
    }

    public function testGetUser()
    {
        $this->core->one('auth.load_user', function ($event) {
            $event->setUser($this->createUser());
        });

        $user = $this->auth->getUser();
        $this->assertEquals('1', $user->getId());

        // second call
        $this->assertSame($user, $this->auth->getUser());

        $this->auth->getProvider()->addUser(new SimpleUser('foo', 'foo', 'bar'));

        // reset user
        $this->auth->logout();
        $this->core->set('SESSION.user_login_id', 'foo');
        $user = $this->auth->getUser();
        $this->assertEquals('foo', $user->getId());
        $this->assertEquals('foo', $user->getUsername());
        $this->assertTrue($this->auth->isGranted('IS_AUTHENTICATED_FULLY'));
        $this->assertFalse($this->auth->isGranted('IS_AUTHENTICATED_REMEMBERED'));

        // reset again
        $this->auth->logout();
        $this->core->set('COOKIE.user_login_id', 'foo');
        $user = $this->auth->getUser();
        $this->assertEquals('foo', $user->getId());
        $this->assertEquals('foo', $user->getUsername());
        $this->assertTrue($this->auth->isGranted('IS_AUTHENTICATED_REMEMBERED'));
        $this->assertFalse($this->auth->isGranted('IS_AUTHENTICATED_FULLY'));
    }

    public function testGetUserRoles()
    {
        $this->assertEquals(['IS_AUTHENTICATED_ANONYMOUSLY'], $this->auth->getUserRoles());
        $this->auth->logout();

        $expected = ['IS_AUTHENTICATED_ANONYMOUSLY', 'foo'];
        $this->auth->setUser($this->createUser('foo'));
        $this->assertEquals($expected, $this->auth->getUserRoles());
    }

    public function guardProvider()
    {
        $admin = $this->createUser('ROLE_ADMIN');

        return [
            // Not login yet, trying access admin area
            ['/admin-area', null, 'Login', true],
            // Has been login, access admin area
            ['/admin-area', $admin, null, false],
            // Has been login, access login page
            ['/login', $admin, 'Admin Area', true],
            // Not login yet, access login page
            ['/login', null, null, false],
            // Has been login, access logout
            ['/logout', $admin, 'Login', true],
            // Access excluded path
            ['/exclude', null, null, false],
        ];
    }

    public function isGrantedProvider()
    {
        return [
            [false, $this->createUser(), 'ROLE_USER'],
            [false, $this->createUser(), null],
            [false, null, 'ROLE_USER'],
            [true, $this->createUser('ROLE_USER'), 'ROLE_USER'],
            [true, $this->createUser('ROLE_ADMIN'), 'ROLE_USER|ROLE_ADMIN'],
            [true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_FOO|ROLE_USER|ROLE_ADMIN|ROLE_SUPER_ADMIN'],
            [true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_FOO'],
            [true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_USER'],
            [true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_ADMIN'],
            [true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_SUPER_ADMIN'],
            [true, $this->createUser('ROLE_FOO'), 'ROLE_FOO'],
            [true, $this->createUser('ROLE_USER|ROLE_FOO'), 'ROLE_FOO'],
        ];
    }

    public function attemptProvider()
    {
        return [
            [true, 'foo', 'bar'],
            [false, 'bar', 'baz', 'Your credentials is expired.'],
            [false, 'foo', 'baz', 'Invalid credentials.'],
        ];
    }

    private function createUser($roles = null, $expired = false)
    {
        return new SimpleUser('1', 'foo', 'bar', explode('|', (string) $roles), $expired);
    }
}
