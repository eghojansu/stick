<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Security;

use Fal\Stick\App;
use Fal\Stick\Cache;
use Fal\Stick\Database\Sql;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\SqlUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\Security\SimpleUser;
use Fal\Stick\Security\SimpleUserTransformer;
use Fal\Stick\Security\UserInterface;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $auth;
    private $app;

    public function setUp()
    {
        $this->app = new App;
        $this->app['EVENT.REROUTE'] = function(App $app, $url) {
            $app['rerouted'] = $url;
        };
        $cache = new Cache('', 'test', TEMP . 'cache/');
        $cache->reset();

        $db = new Sql($cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'debug' => true,
            'commands' => [
                <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT null,
    `password` TEXT NOT NULL DEFAULT NULL,
    `roles` TEXT NOT NULL,
    `expired` INTEGER NOT NULL
);
insert into user (username,password,expired,roles)
    values ("foo","bar",0,"role_foo,role_bar"), ("baz","qux",1,"role_foo")
SQL1
,
            ],
        ]);
        $this->auth = new Auth(
            $this->app,
            new SqlUserProvider($db, new SimpleUserTransformer()),
            new PlainPasswordEncoder
        );
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testAttempt()
    {
        $this->auth->setOption(['redirect'=>'/secure']);

        $this->assertTrue($this->auth->attempt('foo','bar', $message));
        $this->assertNull($message);

        $this->assertFalse($this->auth->attempt('baz','qux', $message));
        $this->assertEquals(Auth::CREDENTIAL_EXPIRED, $message);
        $this->assertNull($this->app['rerouted']);

        $this->assertFalse($this->auth->attempt('foo','quux', $message));
        $this->assertEquals(Auth::CREDENTIAL_INVALID, $message);
        $this->assertNull($this->app['rerouted']);

        $this->assertFalse($this->auth->attempt('foox','quux', $message));
        $this->assertEquals(Auth::CREDENTIAL_INVALID, $message);
        $this->assertNull($this->app['rerouted']);
    }

    public function testLogin()
    {
        $user = $this->auth->getProvider()->findById('1');
        $this->auth->login($user);
        $this->assertEquals($user, $this->auth->getUser());
        $this->assertEquals('1', $this->app['SESSION.user_login_id']);
    }

    public function testLoginEvent()
    {
        $user = $this->auth->getProvider()->findById('1');
        $user2 = $this->auth->getProvider()->findById('2');

        $this->app->set('EVENT.LOGIN', function() {
            return true;
        });
        $this->auth->login($user);
        $this->assertEquals($user, $this->auth->getUser());
        $this->assertEquals('1', $this->app['SESSION.user_login_id']);
        $this->app->clear('SESSION.user_login_id');

        $this->app->set('EVENT.LOGIN', function($user, Auth $auth) use ($user2) {
            $auth->setUser(clone $user2);
        });
        $this->auth->login($user);
        $this->assertEquals($user2, $this->auth->getUser());
        $this->assertNull($this->app['SESSION.user_login_id']);
    }

    public function testLogout()
    {
        $user = $this->auth->getProvider()->findById('1');
        $this->auth->login($user);
        $this->assertEquals('1', $this->app['SESSION.user_login_id']);

        $this->auth->logout();
        $this->assertNull($this->app['SESSION.user_login_id']);

        $this->app['EVENT.LOGOUT'] = function($user, Auth $auth) {
            $auth->setUser(null);
        };
        $this->auth->logout();
        $this->assertNull($this->auth->getUser());
    }

    public function testGetUser()
    {
        $this->assertNull($this->auth->getUser());

        $user = $this->auth->getProvider()->findById('1');
        $user2 = $this->auth->getProvider()->findById('2');
        $this->auth->setUser($user);

        $this->assertEquals($user, $this->auth->getUser());

        $this->auth->logout();
        $this->app['EVENT.LOADUSER'] = function(Auth $auth) use ($user2) {
            $auth->setUser(clone $user2);
        };
        $this->assertEquals($user2, $this->auth->getUser());

        $this->auth->logout();
        $this->app->clear('EVENT.LOADUSER');
        $this->app['SESSION.user_login_id'] = '1';
        $this->assertEquals($user, $this->auth->getUser());
    }

    public function testSetUser()
    {
        $user = $this->auth->getProvider()->findById('1');
        $this->auth->setUser($user);

        $this->assertEquals($user, $this->auth->getUser());

        $this->auth->setUser(null);
        $this->assertNull($this->auth->getUser());
    }

    public function testIsLogged()
    {
        $this->assertFalse($this->auth->isLogged());
        $user = $this->auth->getProvider()->findById('1');
        $this->auth->setUser($user);

        $this->assertTrue($this->auth->isLogged());
    }

    public function testGuard()
    {
        $secure = '/secure';
        $this->auth->setOption(['rules'=>[$secure=>'role_bar'],'redirect'=>$secure]);

        $user = $this->auth->getProvider()->findById('1');
        $this->auth->setUser($user);
        $this->app['PATH'] = '/login';

        $this->auth->guard();
        $this->assertEquals($secure, $this->app->flash('rerouted'));

        // valid access
        $this->app['PATH'] = $secure;
        $this->auth->guard();
        $this->assertNull($this->app->flash('rerouted'));

        // on un-protected path
        $this->app['PATH'] = '/foo';
        $this->auth->guard();
        $this->assertNull($this->app->flash('rerouted'));

        // reset, access secure
        $this->auth->setUser(null);
        $this->app['PATH'] = $secure;
        $this->auth->guard();
        $this->assertEquals('/login', $this->app->flash('rerouted'));

        // access login
        $this->app['PATH'] = '/login';
        $this->auth->guard();
        $this->assertNull($this->app->flash('rerouted'));
    }

    public function testIsGranted()
    {
        $user = $this->auth->getProvider()->findById('1');
        $this->auth->setUser($user);

        $this->assertTrue($this->auth->isGranted('role_foo'));
        $this->assertTrue($this->auth->isGranted('role_bar'));
        $this->assertTrue($this->auth->isGranted('role_foo,role_bar'));
        $this->assertTrue($this->auth->isGranted(['role_foo','role_bar']));

        $this->assertFalse($this->auth->isGranted(null));
        $this->assertFalse($this->auth->isGranted(''));
        $this->assertFalse($this->auth->isGranted('bar'));
        $this->assertFalse($this->auth->isGranted(['bar']));

        $user2 = $this->auth->getProvider()->findById('2');
        $this->auth->setUser($user2);
        $this->assertFalse($this->auth->isGranted('role_bar'));
        $this->auth->setOption(['roleHierarchy'=>['role_foo'=>'role_bar']]);
        $this->assertTrue($this->auth->isGranted('role_bar'));
    }

    public function testGetProvider()
    {
        $this->assertInstanceOf(SqlUserProvider::class, $this->auth->getProvider());
    }

    public function testGetEncoder()
    {
        $this->assertInstanceOf(PlainPasswordEncoder::class, $this->auth->getEncoder());
    }

    public function testGetOption()
    {
        $init = [
            'loginPath' => '/login',
            'redirect' => '/',
            'rules' => [],
            'roleHierarchy' => [],
        ];

        $this->assertEquals($init, $this->auth->getOption());
    }

    public function testSetOption()
    {
        $this->assertContains('foo', $this->auth->setOption(['homepage'=>'foo'])->getOption());
    }
}
