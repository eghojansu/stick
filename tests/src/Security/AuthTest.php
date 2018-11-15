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
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fixture\SimpleUser;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $auth;
    private $fw;

    public function setUp()
    {
        $this->fw = new Fw();
        $this->auth();
    }

    public function tearDown()
    {
        unset($this->fw['SESSION']);
    }

    private function auth(array $options = null)
    {
        $this->auth = new Auth($this->fw, new InMemoryUserProvider(), new PlainPasswordEncoder(), $options);
    }

    private function createUser($roles = null, $expired = false)
    {
        return new SimpleUser('1', 'foo', 'bar', explode('|', (string) $roles), $expired);
    }

    public function testIsLogged()
    {
        $this->assertFalse($this->auth->isLogged());
    }

    public function testGetOptions()
    {
        $this->assertEquals(array(
            'redirect' => '/',
            'login' => '/login',
            'logout' => null,
            'excludes' => null,
            'rules' => array(),
            'roleHierarchy' => array(),
            'lifetime' => 1541932314,
        ), $this->auth->getOptions());
    }

    public function testGetSessionUserId()
    {
        $this->assertNull($this->auth->getSessionUserId());
        $this->fw['SESSION']['user_login_id'] = 1;
        $this->assertEquals(1, $this->auth->getSessionUserId());
    }

    public function testGetCookieUserId()
    {
        $this->assertNull($this->auth->getCookieUserId());
        $this->fw['COOKIE']['user_login_id'] = 1;
        $this->assertEquals(1, $this->auth->getCookieUserId());
        $this->fw['COOKIE']['user_login_id'] = array(2, 2);
        $this->assertEquals(2, $this->auth->getCookieUserId());
    }

    public function testGetUser()
    {
        $this->fw->one('auth.load_user', function () {
            return $this->createUser();
        });

        $user = $this->auth->getUser();
        $this->assertEquals('1', $user->getId());

        // second call
        $this->assertSame($user, $this->auth->getUser());

        $this->auth->getProvider()->addUser(new SimpleUser('foo', 'foo', 'bar'));

        // reset user
        $this->auth->logout();
        $this->fw['SESSION']['user_login_id'] = 'foo';
        $user = $this->auth->getUser();
        $this->assertEquals('foo', $user->getId());
        $this->assertEquals('foo', $user->getUsername());
        $this->assertTrue($this->auth->isGranted('IS_AUTHENTICATED_FULLY'));
        $this->assertFalse($this->auth->isGranted('IS_AUTHENTICATED_REMEMBERED'));

        // reset again
        $this->auth->logout();
        $this->fw['COOKIE']['user_login_id'] = 'foo';
        $user = $this->auth->getUser();
        $this->assertEquals('foo', $user->getId());
        $this->assertEquals('foo', $user->getUsername());
        $this->assertTrue($this->auth->isGranted('IS_AUTHENTICATED_REMEMBERED'));
        $this->assertFalse($this->auth->isGranted('IS_AUTHENTICATED_FULLY'));
    }

    public function testSetUser()
    {
        $user = $this->createUser();

        $this->assertSame($user, $this->auth->setUser($user)->getUser());
    }

    public function testLogin()
    {
        $user = $this->createUser();
        $this->fw->one('auth.login', function ($user) {
            $user->setCredentialsExpired(true);
        });

        $user = $this->auth->login($user, true)->getUser();
        $this->assertTrue($user->isCredentialsExpired());
        $this->assertEquals($user->getId(), $this->fw['SESSION']['user_login_id']);
        $this->assertEquals(array($user->getId(), 1541932314), $this->fw['COOKIE']['user_login_id']);
    }

    public function testLogout()
    {
        $user = $this->createUser();
        $user = $this->auth->login($user)->getUser();
        $this->assertEquals($user->getId(), $this->fw['SESSION']['user_login_id']);
        $this->auth->logout();
        $this->assertFalse(isset($this->fw['SESSION']['user_login_id']));
    }

    public function testGetProvider()
    {
        $this->assertInstanceOf(InMemoryUserProvider::class, $this->auth->getProvider());
    }

    public function testGetEncoder()
    {
        $this->assertInstanceOf(PlainPasswordEncoder::class, $this->auth->getEncoder());
    }

    /**
     * @dataProvider getAttempts
     */
    public function testAttempt($expected, $username, $password, $expectedMessage = null)
    {
        $provider = $this->auth->getProvider();
        $provider->addUser(new SimpleUser('foo', 'foo', 'bar'));
        $provider->addUser(new SimpleUser('bar', 'bar', 'baz', null, true));

        $this->assertEquals($expected, $this->auth->attempt($username, $password, null, $message));
        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @dataProvider getAccess
     */
    public function testIsGranted($expected, $user, $roles)
    {
        $this->auth(array(
            'roleHierarchy' => array(
                'ROLE_ADMIN' => 'ROLE_USER',
                'ROLE_SUPER_ADMIN' => array('ROLE_ADMIN', 'ROLE_FOO'),
            ),
        ));
        $this->auth->setUser($user);
        $mRoles = $roles ? explode('|', $roles) : array();

        $this->assertEquals($expected, $this->auth->isGranted(...$mRoles));
    }

    public function testIsGrantedAnon()
    {
        $this->assertTrue($this->auth->isGranted('IS_AUTHENTICATED_ANONYMOUSLY'));
    }

    /**
     * @dataProvider getGuards
     */
    public function testGuard($path, $user, $expected, $expectedBool)
    {
        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = $path;

        $this->fw->route('GET /admin-area', function () {
            return 'Admin Area';
        });
        $this->fw->route('GET /login', function () {
            return 'Login';
        });

        $this->auth(array(
            'rules' => array(
                '/admin-area' => 'ROLE_ADMIN',
                '/login' => 'ROLE_ANONYMOUS',
            ),
            'logout' => '/logout',
            'redirect' => '/admin-area',
            'excludes' => '/exclude',
        ));
        $this->auth->setUser($user);

        $this->assertEquals($expectedBool, $this->auth->guard());
        $this->assertEquals($expected, $this->fw['OUTPUT']);
    }

    public function getAttempts()
    {
        return array(
            array(true, 'foo', 'bar'),
            array(false, 'bar', 'baz', 'Your credentials is expired.'),
            array(false, 'foo', 'baz', 'Invalid credentials.'),
        );
    }

    public function getAccess()
    {
        return array(
            array(false, $this->createUser(), 'ROLE_USER'),
            array(false, $this->createUser(), null),
            array(false, null, 'ROLE_USER'),
            array(true, $this->createUser('ROLE_USER'), 'ROLE_USER'),
            array(true, $this->createUser('ROLE_ADMIN'), 'ROLE_USER|ROLE_ADMIN'),
            array(true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_FOO|ROLE_USER|ROLE_ADMIN|ROLE_SUPER_ADMIN'),
            array(true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_FOO'),
            array(true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_USER'),
            array(true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_ADMIN'),
            array(true, $this->createUser('ROLE_SUPER_ADMIN'), 'ROLE_SUPER_ADMIN'),
            array(true, $this->createUser('ROLE_FOO'), 'ROLE_FOO'),
            array(true, $this->createUser('ROLE_USER|ROLE_FOO'), 'ROLE_FOO'),
        );
    }

    public function getGuards()
    {
        $admin = $this->createUser('ROLE_ADMIN');

        return array(
            // Not login yet, trying access admin area
            array('/admin-area', null, 'Login', true),
            // Has been login, access admin area
            array('/admin-area', $admin, null, false),
            // Has been login, access login page
            array('/login', $admin, 'Admin Area', true),
            // Not login yet, access login page
            array('/login', null, null, false),
            // Has been login, access logout
            array('/logout', $admin, 'Admin Area', true),
            // Access excluded path
            array('/exclude', null, null, false),
        );
    }
}
