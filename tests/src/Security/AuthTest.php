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

use Fal\Stick\App;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\Security\SimpleUser;
use Fal\Stick\Security\SimpleUserTransformer;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $auth;
    private $app;

    public function setUp()
    {
        $this->app = new App();
        $provider = new InMemoryUserProvider(new SimpleUserTransformer());

        $this->auth = new Auth($this->app, $provider, new PlainPasswordEncoder());
    }

    private function createUser($roles = null, $expired = false)
    {
        return new SimpleUser('1', 'foo', 'bar', $roles, $expired);
    }

    public function testIsLogged()
    {
        $this->assertFalse($this->auth->isLogged());
    }

    public function testGetOptions()
    {
        $this->assertEquals(array(
            'loginPath' => '/login',
            'redirect' => '/',
            'rules' => array(),
            'roleHierarchy' => array(),
        ), $this->auth->getOptions());
    }

    public function testSetOptions()
    {
        $this->assertEquals(array(
            'loginPath' => '/foo',
            'redirect' => '/',
            'rules' => array(),
            'roleHierarchy' => array(),
        ), $this->auth->setOptions(array(
            'loginPath' => '/foo',
        ))->getOptions());
    }

    public function testGetSessionUserId()
    {
        $this->assertNull($this->auth->getSessionUserId());
    }

    public function testGetUser()
    {
        $this->app->one('auth_load_user', function ($event) {
            $event->setUser($this->createUser());
        });

        $user = $this->auth->getUser();
        $this->assertEquals('1', $user->getId());

        // second call
        $this->assertSame($user, $this->auth->getUser());

        // reset user
        $this->auth->logout();
        $this->auth->getProvider()->addUser('foo', 'bar');
        $this->app->set('SESSION.user_login_id', 'foo');
        $user = $this->auth->getUser();
        $this->assertEquals('foo', $user->getId());
        $this->assertEquals('foo', $user->getUsername());
    }

    public function testSetUser()
    {
        $user = $this->createUser();

        $this->assertSame($user, $this->auth->setUser($user)->getUser());
    }

    public function testLogin()
    {
        $user = $this->createUser();
        $this->app->one('auth_login', function ($event) {
            $event->getUser()->setCredentialsExpired(true);
        });

        $user = $this->auth->login($user)->getUser();
        $this->assertTrue($user->isCredentialsExpired());
        $this->assertEquals($user->getId(), $this->app->get('SESSION.user_login_id'));
    }

    public function testLogout()
    {
        $user = $this->createUser();
        $user = $this->auth->login($user)->getUser();
        $this->assertEquals($user->getId(), $this->app->get('SESSION.user_login_id'));
        $this->auth->logout();
        $this->assertNull($this->app->get('SESSION.user_login_id'));
    }

    public function testGetProvider()
    {
        $this->assertInstanceOf(InMemoryUserProvider::class, $this->auth->getProvider());
    }

    public function testGetEncoder()
    {
        $this->assertInstanceOf(PlainPasswordEncoder::class, $this->auth->getEncoder());
    }

    public function attemptProvider()
    {
        return array(
            array(true, 'foo', 'bar'),
            array(false, 'bar', 'baz', 'Your credentials is expired.'),
            array(false, 'foo', 'baz', 'Invalid credentials.'),
        );
    }

    /**
     * @dataProvider attemptProvider
     */
    public function testAttempt($expected, $username, $password, $expectedMessage = null)
    {
        $provider = $this->auth->getProvider();
        $provider->addUser('foo', 'bar');
        $provider->addUser('bar', 'baz', array('credentialsExpired' => true));

        $this->assertEquals($expected, $this->auth->attempt($username, $password, $message));
        $this->assertEquals($expectedMessage, $message);
    }

    public function isGrantedProvider()
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

    /**
     * @dataProvider isGrantedProvider
     */
    public function testIsGranted($expected, $user, $roles)
    {
        $this->auth->setUser($user);
        $this->auth->setOptions(array(
            'roleHierarchy' => array(
                'ROLE_ADMIN' => 'ROLE_USER',
                'ROLE_SUPER_ADMIN' => array('ROLE_ADMIN', 'ROLE_FOO'),
            ),
        ));

        $this->assertEquals($expected, $this->auth->isGranted($roles));
    }

    public function guardProvider()
    {
        $admin = $this->createUser('ROLE_ADMIN');

        return array(
            array(null, false, $admin, '/'),
            array('Home', true, $admin, '/login'),
            array(null, false, null, '/login'),
            array('Login', true, null, '/'),
        );
    }

    /**
     * @dataProvider guardProvider
     */
    public function testGuard($expected, $expectedBool, $user, $path)
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->app->route('GET /', function () {
            return 'Home';
        });
        $this->app->route('GET /login', function () {
            return 'Login';
        });

        $this->auth->setUser($user);
        $this->auth->setOptions(array(
            'rules' => array(
                '/' => 'ROLE_ADMIN',
            ),
        ));

        $this->app->set('PATH', $path);
        $this->assertEquals($expectedBool, $this->auth->guard());
        $this->assertEquals($expected, $this->app->get('RESPONSE'));
    }
}
