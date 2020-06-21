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

namespace Ekok\Stick\Tests\Security;

use Ekok\Stick\Fw;
use Ekok\Stick\Security\Auth;
use Ekok\Stick\Security\InMemoryUserProvider;
use Ekok\Stick\Security\Jwt;
use Ekok\Stick\Security\PlainPasswordEncoder;
use Ekok\Stick\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Security\Auth
 */
final class AuthTest extends TestCase
{
    private $fw;
    private $auth;

    protected function setup(): void
    {
        $this->fw = new Fw();
        $provider = new InMemoryUserProvider(function ($user) {
            return SimpleUser::fromArray($user);
        });
        $provider->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')));
        $provider->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')));
        $provider->addUser(new SimpleUser('3', 'baz', 'qux', array('baz'), true));
        $provider->addUser(new SimpleUser('4', 'qux', 'quux', array('qux'), false, true));

        $this->auth = new Auth($this->fw, $provider, new PlainPasswordEncoder(), array(
            'remember_session' => true,
        ));
    }

    protected function teardown(): void
    {
        $this->fw->rem('SESSION');
    }

    public function testHasUser()
    {
        $this->assertFalse($this->auth->hasUser());
    }

    public function testError()
    {
        $this->assertNull($this->auth->error());
    }

    public function testSetUser()
    {
        $user = new SimpleUser('1', 'foo', 'bar');

        $this->assertSame($user, $this->auth->setUser($user)->getUser());
    }

    public function testLogout()
    {
        $this->auth->login('foo', 'bar');

        // this will be intercepted
        $this->fw->on('auth.logout', function ($auth) {
            return true;
        });
        $this->auth->logout();
        $this->assertEquals('1', $this->auth->getUser()->getId());

        // remove interceptor
        $this->fw->off('auth.logout');
        $this->auth->logout();
        $this->assertNull($this->auth->getUser());
    }

    /** @dataProvider getUserProvider */
    public function testGetUser($expected, $hive = null, $loader = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($loader) {
            $this->fw->on('auth.user_load', $loader);
        }

        $this->assertEquals($expected, $this->auth->getUser());
        // second call
        $this->assertEquals($expected, $this->auth->getUser());
    }

    public function testGetOriginalUser()
    {
        $this->fw->set('SESSION.usid', '1');

        $this->assertEquals('foo', $this->auth->getOriginalUser()->getUsername());
    }

    public function testGetUserRoles()
    {
        $this->fw->set('SESSION.usid', '1');
        $this->auth->setOptions(array(
            'role_hierarchy' => array(
                'foo' => 'foobar',
            ),
        ));

        $expected = array(
            'foo',
            'foobar',
        );

        $this->assertEquals($expected, $this->auth->getUserRoles());
    }

    /** @dataProvider isGrantedProvider */
    public function testIsGranted($expected, $roles, $hive = null, $voter = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($voter) {
            $this->fw->on('auth.vote', $voter);
        }

        $this->assertEquals($expected, $this->auth->isGranted($roles));
    }

    public function testDenyAccessUnlessGranted()
    {
        $this->auth->setUser(new SimpleUser('1', 'foo', 'bar', array('foo')));
        $actual = $this->auth->denyAccessUnlessGranted('foo');

        // as allowed
        $this->assertNull($actual);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Access denied.');

        $this->auth->denyAccessUnlessGranted('bar');
    }

    /** @dataProvider loginProvider */
    public function testLogin($expected, $message, $username, $password)
    {
        $this->auth->setOptions(array('remember_cookie' => true));

        $this->assertEquals($expected, $this->auth->login($username, $password));
        $this->assertEquals($message, $this->auth->error());
    }

    /** @dataProvider basicProvider */
    public function testBasic(bool $expected, string $output = null, array $options = null, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($options) {
            $this->auth->setOptions($options);
        }

        $this->fw->on('fw.error', function ($fw, $error) {
            return $error['message'];
        });

        $this->assertEquals($expected, $this->auth->basic());
        $this->assertEquals($output, $this->fw->get('CONTENT'));
    }

    /** @dataProvider jwtProvider */
    public function testJwt(bool $expected, string $output = null, array $options = null, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($options) {
            $this->auth->setOptions($options);
        }

        $this->fw->on('fw.error', function ($fw, $error) {
            return $error['message'];
        });

        $this->assertEquals($expected, $this->auth->jwt(new Jwt('foo')));
        $this->assertEquals($output, $this->fw->get('CONTENT'));
    }

    /** @dataProvider guardProvider */
    public function testGuard(bool $expected, string $output = null, array $options = null, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($options) {
            $this->auth->setOptions($options);
        }

        $this->fw->on('fw.reroute', function ($fw, $url) {
            $fw->set('CONTENT', "rerouted to {$url}");

            return true;
        });

        $this->assertEquals($expected, $this->auth->guard());
        $this->assertEquals($output, $this->fw->get('CONTENT'));
    }

    /** @dataProvider impersonateProvider */
    public function testImpersonate($expected, $message, $username)
    {
        $this->assertEquals($expected, $this->auth->impersonate($username));
        $this->assertEquals($message, $this->auth->error());
    }

    public function testOriginate()
    {
        $this->auth->impersonate('foo');

        $this->assertEquals('foo', $this->auth->getUser()->getUsername());

        $this->auth->originate();
        $this->assertNull($this->auth->getUser());
    }

    public function testGetOptions()
    {
        $expected = array(
            'excludes' => array(),
            'rules' => array(),
            'role_hierarchy' => array(),
            'remember_session' => true,
            'remember_cookie' => false,
            'remember_lifetime' => '1 week',
        );

        $this->assertEquals($expected, $this->auth->getOptions());
    }

    public function testSetOptions()
    {
        $expected = array(
            'excludes' => array(),
            'rules' => array(),
            'role_hierarchy' => array(),
            'remember_session' => false,
            'remember_cookie' => false,
            'remember_lifetime' => '1 week',
        );

        $this->auth->setOptions(array('remember_session' => false));

        $this->assertEquals($expected, $this->auth->getOptions());
    }

    public function getUserProvider()
    {
        return array(
            array(null),
            array(new SimpleUser('1', 'foo', 'bar'), null, function ($auth) {
                $auth->setUser(new SimpleUser('1', 'foo', 'bar'));
            }),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'COOKIE.usid' => '1',
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'SESSION.usid' => '1',
            )),
            array(new SimpleUser('1', 'foo', 'bar', array('foo')), array(
                'SESSION.as_usid' => '1',
            )),
        );
    }

    public function isGrantedProvider()
    {
        return array(
            array(false, 'foo'),
            array(true, 'foo', array(
                'SESSION.usid' => '1',
            )),
            array(true, 'foo', array(
                'SESSION.usid' => '2',
            )),
            array(true, 'bar', array(
                'SESSION.usid' => '2',
            )),
            array(true, 'foo,bar', array(
                'SESSION.usid' => '2',
            )),
            array(false, 'foo', null, function () {
                return false;
            }),
        );
    }

    public function loginProvider()
    {
        return array(
            array(true, null, 'foo', 'bar'),
            array(false, 'Empty credentials.', 'foo', null),
            array(false, 'Invalid credentials.', 'foobar', 'bar'),
            array(false, 'Credentials is expired.', 'baz', 'qux'),
            array(false, 'Credentials is disabled.', 'qux', 'quux'),
        );
    }

    public function basicProvider()
    {
        return array(
            'default' => array(
                true,
            ),
            'require authentication but not given' => array(
                false,
                'Unauthorized access.',
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
            ),
            'require authentication and given' => array(
                true,
                null,
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
                array(
                    'SERVER.HTTP_AUTHORIZATION' => 'Basic '.base64_encode('foo:bar'),
                ),
            ),
            'require authentication and but has no authorization' => array(
                false,
                'Unauthorized access.',
                array(
                    'rules' => array(
                        '/' => 'bar',
                    ),
                ),
                array(
                    'SERVER.HTTP_AUTHORIZATION' => 'Basic '.base64_encode('foo:bar'),
                ),
            ),
        );
    }

    public function jwtProvider()
    {
        $jwt = new Jwt('foo');
        $user = array(
            'id' => '1',
            'username' => 'foo',
            'expired' => false,
            'disabled' => false,
            'roles' => array('foo'),
        );

        return array(
            'default' => array(
                true,
            ),
            'require authentication but not given' => array(
                false,
                'Unauthorized access.',
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
            ),
            'require authentication and given' => array(
                true,
                null,
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
                array(
                    'SERVER.HTTP_AUTHORIZATION' => 'Bearer '.$jwt->encode($user),
                ),
            ),
            'require authentication and but has no authorization' => array(
                false,
                'Unauthorized access.',
                array(
                    'rules' => array(
                        '/' => 'bar',
                    ),
                ),
                array(
                    'SERVER.HTTP_AUTHORIZATION' => 'Bearer '.$jwt->encode($user),
                ),
            ),
            'invalid jwt key' => array(
                false,
                'Signature verification failed.',
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
                array(
                    'SERVER.HTTP_AUTHORIZATION' => 'Bearer '.(new Jwt('bar'))->encode($user),
                ),
            ),
        );
    }

    public function guardProvider()
    {
        return array(
            'default' => array(
                true,
            ),
            'require authentication but not login yet' => array(
                false,
                'rerouted to /login',
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
            ),
            'require authentication and already login' => array(
                true,
                null,
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
                array(
                    'SESSION.usid' => '1',
                ),
            ),
            'require authentication and but has no authorization' => array(
                false,
                'rerouted to /login',
                array(
                    'rules' => array(
                        '/' => 'bar',
                    ),
                ),
                array(
                    'SESSION.usid' => '1',
                ),
            ),
            'has been login but access login path again' => array(
                true,
                null,
                array(
                    'rules' => array(
                        '/' => 'foo',
                    ),
                ),
                array(
                    'SESSION.usid' => '1',
                    'PATH' => '/login',
                ),
            ),
            'not login but access excluded path' => array(
                true,
                null,
                array(
                    'excludes' => array('/'),
                ),
            ),
        );
    }

    public function impersonateProvider()
    {
        return array(
            array(true, null, 'foo'),
            array(false, 'User not found.', 'unknown'),
        );
    }
}
