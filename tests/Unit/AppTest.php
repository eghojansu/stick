<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick\App;
use Fal\Stick\Logger;
use Fal\Stick\ResponseErrorException;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Test\fixture\autoload\LoadAClass;
use Fal\Stick\Test\fixture\controller\AnyController;
use Fal\Stick\Test\fixture\controller\MapGetController;
use Fal\Stick\Test\fixture\mapper\MyCompositUserMapper;
use Fal\Stick\Test\fixture\mapper\MyUserMapper;
use Fal\Stick\Test\fixture\services\DoNotRegisterClass;
use Fal\Stick\Test\fixture\services\ImplementNoMethodInterface;
use Fal\Stick\Test\fixture\services\NoConstructorClass;
use Fal\Stick\Test\fixture\services\NoMethodInterface;
use Fal\Stick\Test\fixture\services\ReqDateTimeClass;
use Fal\Stick\Test\fixture\services\VariadicArgClass;
use Fal\Stick\Test\fixture\services\WithConstructorArgClass;
use Fal\Stick\Test\fixture\services\WithConstructorClass;
use Fal\Stick\Test\fixture\services\WithConstructorDefaultArgClass;
use PHPUnit\Framework\TestCase;
use autoload\LoadBClass;

class AppTest extends TestCase
{
    private $app;
    private $init = [];

    public function setUp()
    {
        foreach (explode('|', App::GLOBALS) as $global) {
            $this->init['globals'][$global] = $GLOBALS['_'.$global] ?? null;
        }
        $this->init['tz'] = date_default_timezone_get();

        $this->app = new App();
    }

    public function tearDown()
    {
        header_remove();

        foreach ($this->init['globals'] as $global => $value) {
            $GLOBALS['_'.$global] = $value;
        }
        date_default_timezone_set($this->init['tz']);

        $this->app->service('logger')->clear();
    }

    public function testAgent()
    {
        $this->assertEquals('', $this->app->agent());
    }

    public function testAjax()
    {
        $this->assertEquals(false, $this->app->ajax());
    }

    public function testIp()
    {
        $this->assertEquals('', $this->app->ip());
    }

    public function testBlacklisted()
    {
        // We skip real ip checking test
        // because its need internet connection and real blacklisted ip
        // We also ignore code coverage for that part
        $this->assertFalse($this->app->blacklisted());
    }

    public function autoloadProvider()
    {
        return [
            [LoadAClass::class, true],
            [LoadBClass::class, true],
            // doesn't exists
            [LoadCClass::class, false],
        ];
    }

    /**
     * @dataProvider autoloadProvider
     */
    public function testAutoload($class, $expected)
    {
        $this->app->set('NAMESPACE', [
            '\\' => FIXTURE,
            'Fal\\Stick\\Test\\' => ROOT,
        ]);

        if ($expected) {
            $this->assertFalse(class_exists($class, false));
        }

        $this->assertEquals($expected, $this->app->autoload($class));
        $this->assertEquals($expected, class_exists($class, false));
    }

    public function testOverrideRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'put';

        $this->assertEquals('PUT', $this->app->overrideRequestMethod()->get('REQ.METHOD'));
        $this->assertEquals('PUT', $_SERVER['REQUEST_METHOD']);
    }

    public function emulateCliProvider()
    {
        return [
            [
                true,
                ['foo', 'bar', '-opt', '-uvw=baz', '--qux=quux'],
                '/foo/bar',
                'o=&p=&t=&u=&v=&w=baz&qux=quux',
            ],
            [
                true,
                ['/foo/bar?baz=qux#fragment'],
                '/foo/bar',
                'baz=qux',
                'fragment',
            ],
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider emulateCliProvider
     */
    public function testEmulateCliRequest($cli, $args = [], $path = '/', $query = '', $fragment = '')
    {
        $argv = array_merge([$_SERVER['argv'][0]], $args);
        $_SERVER['argv'] = $argv;
        $_SERVER['argc'] = count($argv);

        $this->app->set('REQ.CLI', $cli);
        $this->app->emulateCliRequest();

        $this->assertEquals('GET', $this->app->get('REQ.METHOD'));
        $this->assertEquals($path, $this->app->get('REQ.PATH'));
        $this->assertEquals($query, $this->app->get('REQ.QUERY'));
        $this->assertEquals($fragment, $this->app->get('REQ.FRAGMENT'));
    }

    public function testGroup()
    {
        $this->app->group(['route' => 'foo', 'prefix' => '/foo'], function ($app) {
            $app->route('GET bar /bar', function () {
                return 'foobar';
            });

            $app->route('GET baz /baz', function () {
                return 'foobaz';
            });

            $app->group(['prefix' => '/bleh'], function ($app) {
                $app->route('GET /what', function () {
                    return 'fooblehwhat';
                });
            });
        });
        $this->app->group(['prefix' => '/qux'], function ($app) {
            $app->route('GET /quux', function () {
                return 'quxquux';
            });
        });
        $this->app->set('QUIET', true);

        $this->app->mock('GET foobar');
        $this->assertEquals('foobar', $this->app->get('RES.CONTENT'));

        $this->app->mock('GET foobaz');
        $this->assertEquals('foobaz', $this->app->get('RES.CONTENT'));

        $this->app->mock('GET /qux/quux');
        $this->assertEquals('quxquux', $this->app->get('RES.CONTENT'));

        $this->app->mock('GET /foo/bleh/what');
        $this->assertEquals('fooblehwhat', $this->app->get('RES.CONTENT'));
    }

    public function routeProvider()
    {
        return [
            [
                'GET', null, '/', 0, 'GET /', 'foo',
            ],
            [
                'GET', null, '/', App::REQ_AJAX, 'GET / ajax', 'foo',
            ],
            [
                'GET', null, '/', App::REQ_CLI, 'GET / CLI', 'foo',
            ],
            [
                'GET|POST', 'foo', '/', 0, 'GET|POST foo /', 'bar',
            ],
            [
                'GET|POST', 'foo', '/', 0, 'POST foo', 'bar', ['GET foo /', 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRoute($verbs, $alias, $path, $type, $pattern, $callback, $before = null)
    {
        $expected = [];
        foreach (explode('|', $verbs) as $verb) {
            $expected[$path][$type][$verb] = [$callback, 0, 0, $alias];
        }

        if ($before) {
            $this->app->route(...$before);
        }

        $this->assertEquals($expected, $this->app->route($pattern, $callback)->get('_ROUTES'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route rule should contain at least request method and path, given "foo"
     */
    public function testRouteException()
    {
        $this->app->route('foo', 'bar');
    }

    public function mapProvider()
    {
        return [
            [
                null, '/', 0, '/', 'FakeController',
                null, '/', App::REQ_AJAX, '/ ajax', new MapGetController(), 'map',
            ],
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($alias, $path, $type, $pattern, $class, $prefix = '')
    {
        $str = is_string($class);
        $expected = [];
        foreach (explode('|', App::VERBS) as $verb) {
            $callback = $str ? $class.'->'.$prefix.$verb : [$class, $prefix.$verb];
            $expected[$path][0][$verb] = [$callback, 0, 0, $alias];
        }

        if ($prefix) {
            $this->app->set('PREMAP', $prefix);
        }

        $this->assertEquals($expected, $this->app->map($pattern, $class)->get('_ROUTES'));
    }

    public function redirectProvider()
    {
        return [
            ['GET', null, '/', 0, 'GET /', '/foo', true],
            ['POST', 'foo', '/', App::REQ_SYNC, 'POST foo / sync', '/foo', true],
        ];
    }

    /**
     * @dataProvider redirectProvider
     */
    public function testRedirect($verb, $alias, $path, $type, $pattern, $url, $permanent)
    {
        $set = $this->app->redirect($pattern, $url, $permanent)->get('_ROUTES.'.$path.'.'.$type.'.'.$verb);

        $this->assertNotEmpty($set);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Url cannot be empty
     */
    public function testRedirectException()
    {
        $this->app->redirect('GET /', '');
    }

    public function testRedirectOnAction()
    {
        $this->app->redirect('GET /', '/foo');
        $this->app->route('GET /foo', function () {
            return 'redirected';
        });

        $this->expectOutputString('redirected');
        $this->app->mock('GET / cli');
    }

    public function testStatus()
    {
        $this->app->status(200);
        $this->assertEquals(200, $this->app->get('RES.CODE'));
        $this->assertEquals('OK', $this->app->get('RES.STATUS'));
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Unsupported http code: 600
     */
    public function testStatusException()
    {
        $this->app->status(600);
    }

    public function expireProvider()
    {
        return [
            [
                1,
                [
                    'Cache-Control' => 'max-age=1',
                ],
            ],
            [
                0,
                [
                    'Pragma' => 'no-cache',
                ],
            ],
        ];
    }

    /**
     * @dataProvider expireProvider
     */
    public function testExpire($secs, $checks)
    {
        $this->app->expire($secs);

        foreach ($checks as $key => $value) {
            $this->assertEquals($value, $this->app->get('RES.HEADERS.'.$key));
        }
    }

    public function aliasProvider()
    {
        return [
            ['/', 'foo', null, 'GET foo /'],
            ['/bar', 'foo', ['foo' => 'bar'], 'GET foo /{foo}'],
            ['/foo/1', 'foo', ['id' => '1'], 'GET foo /foo/{id:digit}'],
        ];
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testAlias($expected, $alias, $args, $pattern)
    {
        $this->app->route($pattern, 'foo');

        $this->assertEquals($expected, $this->app->alias($alias, $args));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route "foo" does not exists
     */
    public function testAliasException()
    {
        $this->app->alias('foo');
    }

    public function buildProvider()
    {
        return [
            [
                '/foo',
            ],
            [
                'foo', '/', 'GET foo /',
            ],
            [
                'foo(foo=bar)', '/bar', 'GET foo /{foo}',
            ],
            [
                'foo#baz(bar=bar)', '/foo/bar#baz', 'GET foo /foo/{bar}',
            ],
            [
                'foo(foo=bar,bar=baz)', '/foo/bar/baz', 'GET foo /foo/{foo}/{bar:alpha}',
            ],
        ];
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild($expr, $expected = null, $pattern = null)
    {
        if ($pattern) {
            $this->app->route($pattern, 'foo');
        }

        $this->assertEquals($expected ?? $expr, $this->app->build($expr));
    }

    public function testOn()
    {
        $this->app->on('foo', function () {
        })->on('foo', function () {
        })->on('bar', function () {
        });
        $this->assertCount(2, $this->app->get('_LISTENERS'));
        $this->assertCount(2, $this->app->get('_LISTENERS.foo'));
        $this->assertCount(1, $this->app->get('_LISTENERS.bar'));
    }

    public function triggerProvider()
    {
        return [
            [
                false, 'foo',
            ],
            [
                true, 'foo', null, ['foo' => function () {
                }],
            ],
            [
                false, 'foo', null, ['foo' => function () {
                    return true;
                }],
            ],
            [
                true, 'foo', null, ['foo' => function () {
                    return false;
                }],
            ],
            [
                true, 'foo', null, [
                    'foo' => function (App $app) {
                        $app->set('foo.0', 1);
                    },
                    'foo.' => function (App $app) {
                        $app->set('foo.1', 2);
                    },
                    'foo..' => function (App $app) {
                        $app->set('foo.2', 3);
                    },
                ], ['foo', [1, 2, 3]],
            ],
            [
                false, 'foo', null, [
                    'foo' => function (App $app) {
                        $app->set('foo.0', 1);
                    },
                    'foo.' => function (App $app) {
                        $app->set('foo.1', 2);

                        return true;
                    },
                    'foo..' => function (App $app) {
                        $app->set('foo.2', 3);
                    },
                ], ['foo', [1, 2]],
            ],
        ];
    }

    /**
     * @dataProvider triggerProvider
     */
    public function testTrigger($expected, $event, $args = null, $rules = null, $check = null)
    {
        if ($rules) {
            foreach ($rules as $key => $rule) {
                $this->app->on(trim($key, '.'), $rule);
            }
        }

        $this->assertEquals($expected, $this->app->trigger($event, $args));

        if ($check) {
            $this->assertEquals($check[1], $this->app->get($check[0]));
        }
    }

    public function rerouteProvider()
    {
        return [
            [
                '/', true, true, [['GET /', function () {
                    return 'foo';
                }]], '/foo/',
            ],
            [
                '/', true, true, [['GET /', function (App $app) {
                    $app->reroute('/bar');
                }], ['GET /bar', function () {
                    return 'bar';
                }]], '/bar/',
            ],
            [
                null, true, true, [['GET /', function () {
                    return 'foo';
                }]], '/foo/',
            ],
            [
                ['home', ['foo' => 'bar']], true, true, [['GET home /{foo}', function ($foo) {
                    return $foo;
                }]], '/bar/',
            ],
            [
                '/foo', true, false, [['GET /foo', function () {
                    return 'foo';
                }]],
            ],
        ];
    }

    /**
     * @dataProvider rerouteProvider
     */
    public function testReroute($url, $permanent, $cli, $routes, $expected = null)
    {
        $this->app->set('REQ.CLI', $cli);
        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        if ($cli) {
            $this->expectOutputRegex($expected);
            $this->app->reroute($url, $permanent);
        } else {
            $this->app->reroute($url, $permanent);
            $this->assertStringEndsWith($url, $this->app->get('RES.HEADERS.Location'));
        }
    }

    public function testRerouteInterception()
    {
        $this->app->on(App::EVENT_REROUTE, function (App $app, $url, $permanent) {
            $app->set('rerouted', $url);
            $app->set('permanent', $permanent);
        });
        $this->app->reroute('/foo');

        $this->assertStringEndsWith('/foo', $this->app->get('rerouted'));
        $this->assertFalse($this->app->get('permanent'));
    }

    public function runProvider()
    {
        return [
            [
                'foo', [], [['GET /', function () {
                    return 'foo';
                }]],
            ],
            [
                'foo fixed', ['REQ.PATH' => '/foo/'], [['GET /foo', function () {
                    return 'foo fixed';
                }]],
            ],
            [
                '{"foo":"bar"}', [], [['GET /', function () {
                    return ['foo' => 'bar'];
                }]],
            ],
            [
                'foo', [], [['GET /', function () {
                    return function () {
                        echo 'foo';
                    };
                }]],
            ],
            [
                'foo', ['REQ.CLI' => false], [['GET /', function (App $app) {
                    $app->set('RES.HEADERS.Content-Type', 'text/plain');
                    $app->set('COOKIE.foo', 'bar');

                    return 'foo';
                }]],
            ],
            [
                'foo', [], [['GET /', function () {
                    return 'foo';
                }, 0, 5]],
            ],
            [
                'foo', ['REQ.CLI' => false], [['GET / sync', function () {
                    return 'foo';
                }]],
            ],
            [
                'bar', ['REQ.PATH' => '/bar'], [
                    ['GET /foo', function () {
                        return 'foo';
                    }],
                    ['GET /bar', function () {
                        return 'bar';
                    }],
                ],
            ],
            [
                'foo', ['REQ.PATH' => '/foo'], [['GET /foo', function () {
                    return 'foo';
                }]],
            ],
            [
                'foobar', ['REQ.PATH' => '/foo/bar', 'REQ.METHOD' => 'POST'], [['POST /foo/{bar}', function ($bar) {
                    return 'foo'.$bar;
                }]],
            ],
            [
                'foo1', ['REQ.PATH' => '/foo/1'], [['GET /foo/{id:digit}', function ($id) {
                    return 'foo'.$id;
                }]],
            ],
            [
                'foo', ['REQ.PATH' => '/any/foo'], [['GET /{method}/{input}', AnyController::class.'->{method}']],
            ],
            [
                'foo', [], [], '/No route specified/',
            ],
            [
                'foo', [], [['GET /', function () {
                    throw new \Exception('bar');
                }]], '/bar/',
            ],
            [
                'foo', [], [['GET /', function () {
                    throw new ResponseErrorException('Generated from exception', 404);
                }]], '/Generated from exception/',
            ],
            [ // route do not exists
                'foo', [], [['GET /foo', function () {
                    return 'foo';
                }]], '/Not Found/',
            ],
            [ // invalid mode
                'foo', [], [['GET / sync', function () {
                    return 'foo';
                }]], '/Not Found/',
            ],
            [ // invalid request method
                'foo', ['REQ.CLI' => false], [['POST /', function () {
                    return 'foo';
                }]], '/Method Not Allowed/',
            ],
            [ // invalid controller (unable to call)
                'foo', [], [['GET /', AnyController::class.'->fakeMethod']], '/Method Not Allowed/',
            ],
            [ // invalid controller (class does not exists)
                'foo', [], [['GET /', 'FakeController->method']], '/Not Found/',
            ],
            [ // cached
                'foo', [], [['GET /', function () {
                    return 'foo';
                }, 1]],
            ],
        ];
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($expected, $sets, $routes, $error = null)
    {
        $this->app->mset($sets);

        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        if ($this->app->get('QUIET')) {
            $this->app->run();

            if ($error) {
                $this->assertContains($error, $this->app->get('ERROR'));
            } else {
                $this->assertEmpty($this->app->get('ERROR'));
                $this->assertEquals($expected, $this->app->get('RES.CONTENT'));
            }
        } else {
            if ($error) {
                $this->expectOutputRegex($error);
                $this->app->run();
            } else {
                $this->expectOutputString($expected);
                $this->app->run();
                $this->assertEmpty($this->app->get('ERROR'));
            }
        }
    }

    public function testRunHeaders()
    {
        $this->app->route('GET /', function () {
            return 'foo';
        });
        $this->app->mset([
            'QUIET' => true,
            'REQ.HEADERS.Origin' => 'foo',
            'CORS.ORIGIN' => 'foo',
            'CORS.CREDENTIALS' => ['foo'],
            'CORS.EXPOSE' => 'foo',
        ]);
        $this->app->run();

        $output = $this->app->get('RES.CONTENT');
        $headers = $this->app->get('RES.HEADERS');
        $expected = [
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Credentials',
            'Access-Control-Expose-Headers',
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
            'Pragma',
            'Cache-Control',
            'Expires',
        ];
        $this->assertEquals('foo', $output);
        $this->assertEquals($expected, array_keys($headers));

        // preflight
        $this->app->mset([
            'REQ.CLI' => false,
            'REQ.METHOD' => 'OPTIONS',
            'REQ.HEADERS.Access-Control-Request-Method' => 'foo',
            'CORS.HEADERS' => 'foo',
            'CORS.TTL' => 10,
        ]);
        $this->app->run();

        $output = $this->app->get('RES.CONTENT');
        $headers = $this->app->get('RES.HEADERS');
        $expected = [
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Credentials',
            'Allow',
            'Access-Control-Allow-Methods',
            'Access-Control-Allow-Headers',
            'Access-Control-Max-Age',
        ];
        $this->assertEquals('', $output);
        $this->assertEquals($expected, array_keys($headers));
    }

    public function testRunBoot()
    {
        $this->app->route('GET /', function (App $app) {
            return 'from controller';
        });
        $this->app->on(App::EVENT_BOOT, function (App $app) {
            if (isset($app['booted'])) {
                ++$app['booted'];
            }

            $app['booted'] = 1;
        });
        $this->app->set('QUIET', true);

        $this->app->run();
        $this->assertEquals('from controller', $this->app->get('RES.CONTENT'));
        $this->assertEquals(1, $this->app->get('booted'));

        // call once again
        $this->app->run();
        $this->assertEquals('from controller', $this->app->get('RES.CONTENT'));
        $this->assertEquals(1, $this->app->get('booted'));
    }

    public function testRunPreRoute()
    {
        $this->app->route('GET /', function () {
            return 'from controller';
        });
        $this->app->on(App::EVENT_PREROUTE, function (App $app) {
            echo 'from event';
        });

        $this->expectOutputString('from event');
        $this->app->run();
    }

    public function testRunPostRoute()
    {
        $this->app->route('GET /', function () {
            return 'from controller';
        });
        $this->app->on(App::EVENT_POSTROUTE, function (App $app) {
            echo 'from event';
        });

        $this->expectOutputString('from event');
        $this->app->run();
    }

    public function testRunCached()
    {
        $this->app->mset([
            'QUIET' => true,
            'CACHE' => 'auto',
        ]);
        $this->app->route('GET /', function () {
            return 'foo';
        }, 10);

        $this->app->run();

        $this->assertEquals('foo', $this->app->get('RES.CONTENT'));

        // second run
        $this->app->run();
        $this->assertEquals('foo', $this->app->get('RES.CONTENT'));

        // third run, with modified date check
        $this->app->set('REQ.HEADERS.If-Modified-Since', gmdate('r', strtotime('+1 minute')));
        $this->app->run();
        $this->assertEquals('', $this->app->get('RES.CONTENT'));
        $this->assertEquals(304, $this->app->get('RES.CODE'));
    }

    public function runMapperProvider()
    {
        return [
            [
                'my mapper was constructed in dry state',
                '/',
                ['GET /', function (MyUserMapper $mapper) {
                    return 'my mapper was constructed in '.($mapper->dry() ? 'dry' : 'wet').' state';
                }],
            ],
            [
                'username is foo',
                '/1',
                ['GET /{mapper}', function (MyUserMapper $mapper) {
                    return 'username is '.$mapper->get('username');
                }],
            ],
            [
                'username is bar',
                '/2',
                ['GET /{mapper}', function (MyUserMapper $mapper) {
                    return 'username is '.$mapper->get('username');
                }],
            ],
            [
                'username is bar',
                '/4',
                ['GET /{mapper}', function (MyUserMapper $mapper) {
                }], 'Record is not found (GET /4)',
            ],
            [
                'username is foo and password is bar',
                '/foo/bar',
                ['GET /{mapper}/{password}', function (MyCompositUserMapper $mapper) {
                    return 'username is '.$mapper->get('username').' and password is '.$mapper->get('password');
                }],
            ],
            [
                'username is bar and password is baz',
                '/bar/baz',
                ['GET /{mapper}/{password}', function (MyCompositUserMapper $mapper) {
                    return 'username is '.$mapper->get('username').' and password is '.$mapper->get('password');
                }],
            ],
            [
                null,
                '/bar/qux',
                ['GET /{mapper}/{password}', function (MyCompositUserMapper $mapper) {
                }], 'Record is not found (GET /bar/qux)',
            ],
            [
                null,
                '/bar',
                ['GET /{mapper}', function (MyCompositUserMapper $mapper) {
                }], 'Insufficient primary keys value, expect value of "username, password"',
            ],
        ];
    }

    /**
     * @dataProvider runMapperProvider
     */
    public function testRunMapper($expected, $path, $route = null, $error = null)
    {
        $this->app->set('QUIET', true)->set('CMAPPER', true)->set('REQ.PATH', $path);
        $this->app->rule(Connection::class, [
            'args' => [
                'cache' => '%cache%',
                'logger' => '%logger%',
                'options' => [
                    'driver' => 'sqlite',
                    'location' => ':memory:',
                    'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT NULL,
    `password` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE `composit_user` (
    `username` TEXT NOT NULL,
    `password` TEXT NOT NULL,
    `active` INTEGER NOT NULL DEFAULT 1,
    PRIMARY KEY (username, password)
);
insert into user (username) values ("foo"), ("bar"), ("baz");
insert into composit_user (username, password) values ("foo","bar"), ("bar","baz"), ("baz","qux");
SQL1
                ],
            ],
        ]);

        if ($route) {
            $this->app->route(...$route);
        }

        $this->app->run();

        if ($error) {
            $this->assertEquals($error, $this->app->get('ERROR.text'));
        } else {
            $this->assertEquals($expected, $this->app->get('RES.CONTENT'));
        }
    }

    public function mockProvider()
    {
        return [
            [
                '/foo/', [['GET /', function () {
                    return 'foo';
                }]], 'GET /',
            ],
            [
                '/bar/', [['GET home /', function (App $app) {
                    return $app['GET.foo'];
                }]], 'GET home', ['foo' => 'bar'],
            ],
            [
                '/bar/', [['POST /', function (App $app) {
                    return $app['POST.foo'];
                }]], 'POST /', ['foo' => 'bar'],
            ],
            [
                '/foo/', [['GET /', function (App $app) {
                    return 'foo';
                }]], 'GET /', null, ['Origin' => 'foo'],
            ],
        ];
    }

    /**
     * @dataProvider mockProvider
     */
    public function testMock($expected, $routes, $pattern, $args = null, $headers = null, $body = null)
    {
        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        $this->expectOutputRegex($expected);
        $this->app->mock($pattern, $args, $headers, $body);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mock pattern should contain at least request method and path, given "get"
     */
    public function testMockException()
    {
        $this->app->mock('get');
    }

    public function errorProvider()
    {
        return [
            [
                500, null, '/HTTP 500/',
            ],
            [
                404, 'Page not found', '/Page not found/', ['REQ.CLI' => false],
            ],
            [
                403, 'Forbidden', '/Forbidden/', ['REQ.CLI' => false, 'REQ.AJAX' => true],
            ],
        ];
    }

    /**
     * @dataProvider errorProvider
     */
    public function testError($code, $message, $pattern, $env = null)
    {
        if ($env) {
            $this->app->mset($env);
        }

        $this->expectOutputRegex($pattern);
        $this->app->error($code, $message);
    }

    public function testErrorQuiet()
    {
        $this->app->set('QUIET', true);
        $this->app->error(500, 'Internal server error');
        $this->assertContains('Internal server error', $this->app->get('ERROR'));
    }

    public function testErrorListener()
    {
        $this->app->on(App::EVENT_ERROR, function (App $app, $error) {
            $app->set('myerror', $error['text']);
        });
        $this->app->error(500, 'Internal server error');
        $this->assertEquals('Internal server error', $this->app->get('myerror'));

        // listener trigger error
        $message = 'Error triggered from app error listener';

        $this->app->clear('_LISTENERS.'.App::EVENT_ERROR);
        $this->app->set('QUIET', true);
        $this->app->on(App::EVENT_ERROR, function () use ($message) {
            throw new \Exception($message);
        });

        $this->app->error(404, 'Internal server error');
        $this->assertEquals(500, $this->app->get('RES.CODE'));
        $this->assertEmpty($this->app->get('RES.CONTENT'));
    }

    public function testErrorEnsureLogged()
    {
        $this->app->set('LOG.THRESHOLD', Logger::LEVEL_DEBUG)->set('QUIET', true);

        $this->assertEmpty($this->app->service('logger')->files());

        $this->app->error(404);

        $this->assertNotEmpty($this->app->service('logger')->files());
    }

    public function grabProvider()
    {
        return [
            [
                [new WithConstructorDefaultArgClass(), 'foo'],
                WithConstructorDefaultArgClass::class.'->foo', true,
            ],
            [
                [WithConstructorDefaultArgClass::class, 'foo'],
                WithConstructorDefaultArgClass::class.'->foo', false,
            ],
            [
                [WithConstructorDefaultArgClass::class, 'foo'],
                WithConstructorDefaultArgClass::class.'::foo', true,
            ],
            [
                'foo', 'foo', true,
            ],
        ];
    }

    /**
     * @dataProvider grabProvider
     */
    public function testGrab($expected, $def, $create)
    {
        $this->assertEquals($expected, $this->app->grab($def, $create));
    }

    public function callProvider()
    {
        return [
            [
                'foo', 'trim', ['  foo  '],
            ],
            [
                'foo', WithConstructorDefaultArgClass::class.'->getId',
            ],
            [
                'barfoobaz', WithConstructorDefaultArgClass::class.'->getId', ['bar', 'baz'],
            ],
            [
                'barfoobaz', WithConstructorDefaultArgClass::class.'->getId', ['suffix' => 'baz', 'prefix' => 'bar'],
            ],
            [
                'barfoobaz', [new WithConstructorDefaultArgClass(), 'getId'], ['suffix' => 'baz', 'prefix' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider callProvider
     */
    public function testCall($expected, $def, $args = null)
    {
        $this->assertEquals($expected, $this->app->call($def, $args));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Call to undefined method Fal\Stick\Test\fixture\services\WithConstructorDefaultArgClass::foo
     */
    public function testCallException()
    {
        $this->app->call(WithConstructorDefaultArgClass::class.'->foo');
    }

    /**
     * @expectedException \BadFunctionCallException
     * @expectedExceptionMessage Call to undefined function foo
     */
    public function testCallException2()
    {
        $this->app->call('foo');
    }

    public function ruleProvider()
    {
        return [
            [
                ['class' => 'foo', 'service' => true],
                'foo',
            ],
            [
                ['class' => 'bar', 'service' => true],
                'foo',
                'bar',
            ],
            [
                ['class' => 'foo', 'use' => 'bar', 'service' => true],
                'foo',
                ['use' => 'bar'],
            ],
            [
                ['class' => 'bar', 'boot' => 'baz', 'service' => false],
                'foo',
                ['class' => 'bar', 'boot' => 'baz', 'service' => false],
            ],
            [
                ['class' => AnyController::class, 'service' => true],
                'foo',
                new AnyController(),
            ],
        ];
    }

    /**
     * @dataProvider ruleProvider
     */
    public function testRule($expected, $id, $rule = null)
    {
        $this->assertEquals($expected, $this->app->rule($id, $rule)->get('_SERVICE_RULES.'.$id));
    }

    public function testService()
    {
        // get self
        $this->assertEquals($this->app, $this->app->service('app'));
        $this->assertEquals($this->app, $this->app->service(App::class));

        // unregistered class
        $first = $this->app->service(DoNotRegisterClass::class);
        $this->assertInstanceOf(DoNotRegisterClass::class, $first);
        $this->assertNotEquals($first, $this->app->service(DoNotRegisterClass::class));

        // Registered class
        $this->app->rule('rdt', ReqDateTimeClass::class);
        $rdt = $this->app->service('rdt');
        $this->assertInstanceOf(ReqDateTimeClass::class, $rdt);
        $this->assertEquals($rdt, $this->app->service('rdt'));
        $this->assertEquals($rdt, $this->app->service(ReqDateTimeClass::class));
        $this->assertNotEquals($rdt->dt, $this->app->service(\DateTime::class));
    }

    public function createProvider()
    {
        return [
            [
                NoConstructorClass::class,
            ],
            [
                NoConstructorClass::class, null, null, [
                    'boot' => function ($obj) {
                        $obj->id = 'booted';
                    },
                ],
            ],
            [
                WithConstructorClass::class,
            ],
            [
                WithConstructorDefaultArgClass::class,
            ],
            [
                WithConstructorArgClass::class, 'foo', null, [
                    'class' => WithConstructorArgClass::class,
                    'args' => ['id' => 'foo'],
                ],
            ],
            [
                WithConstructorArgClass::class, null, null, [
                    'args' => ['id' => '%SEED%'],
                ],
            ],
            [
                ReqDateTimeClass::class,
            ],
            [
                ReqDateTimeClass::class, null, ['dt' => new \DateTime()],
            ],
            [
                ReqDateTimeClass::class, null, [new \DateTime()],
            ],
            [
                ReqDateTimeClass::class, null, null, [
                    'args' => ['dt' => \DateTime::class],
                ],
            ],
            [
                ReqDateTimeClass::class, null, null, [
                    'args' => ['dt' => '%mydt%'],
                ], [
                    'mydt' => [
                        'class' => \DateTime::class,
                    ],
                ],
            ],
            [
                \DateTime::class,
            ],
            [
                VariadicArgClass::class, null, [1, 2, 3],
            ],
            [
                NoMethodInterface::class, null, null, [
                    'use' => ImplementNoMethodInterface::class,
                ],
            ],
            [
                ReqDateTimeClass::class, 'foo', null, [
                    'class' => ReqDateTimeClass::class,
                    'constructor' => function () {
                        return new ReqDateTimeClass(new \DateTime());
                    },
                ],
            ],
            [
                ReqDateTimeClass::class, null, null, function () {
                    return new ReqDateTimeClass(new \DateTime());
                },
            ],
        ];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($classname, $id = null, $args = null, $rule = null, $register = null)
    {
        $useId = $id ?? $classname;

        if ($rule) {
            $this->app->rule($useId, $rule);
        }

        if ($register) {
            foreach ($register as $id => $rule) {
                $this->app->rule($id, $rule);
            }
        }

        $init = $this->app->create($useId, $args);
        $this->assertInstanceOf($classname, $init);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to create instance for "Fal\Stick\Test\fixture\services\NoMethodInterface". Please provide instantiable version of Fal\Stick\Test\fixture\services\NoMethodInterface
     */
    public function testCreateException()
    {
        $this->app->create(NoMethodInterface::class);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Constructor of "Fal\Stick\Test\fixture\services\ReqDateTimeClass" should return instance of Fal\Stick\Test\fixture\services\ReqDateTimeClass
     */
    public function testCreateException2()
    {
        $this->app->rule(ReqDateTimeClass::class, function () {});

        $this->app->create(ReqDateTimeClass::class);
    }

    public function testHive()
    {
        $this->assertNotEmpty($this->app->hive());
    }

    public function testConfig()
    {
        $this->app->config(FIXTURE.'config.php');
        $this->app->set('QUIET', true);

        $this->assertEquals('bar', $this->app->get('foo'));
        $this->assertEquals('baz', $this->app->get('bar'));
        $this->assertEquals('quux', $this->app->get('qux'));
        $this->assertEquals(range(1, 3), $this->app->get('arr'));
        $this->assertEquals('config', $this->app->get('sub'));

        $this->app->mock('GET /');
        $this->assertEquals('registered from config', $this->app->get('RES.CONTENT'));

        $this->app->mock('GET /foo');
        $this->assertStringEndsWith('/', $this->app->get('RES.HEADERS.Location'));

        $this->app->mock('GET /bar');
        $this->assertEquals('foo', $this->app->get('RES.CONTENT'));

        $this->app->mock('GET /group/index');
        $this->assertEquals('group index', $this->app->get('RES.CONTENT'));

        $this->assertTrue($this->app->trigger('foo'));
        $this->assertInstanceOf(NoConstructorClass::class, $this->app->service('foo'));
    }

    public function testRef()
    {
        $this->assertNull($this->app->ref('foo'));
        $this->assertNull($this->app->ref('REQ.foo'));
        $this->assertNull($this->app->ref('REQ.foo.bar', false));

        $ref = &$this->app->ref('REQ.METHOD');
        $this->assertEquals('GET', $ref);

        $ref = 'POST';
        $this->assertEquals('POST', $this->app->ref('REQ.METHOD'));

        $var = ['foo' => ['bar' => 'baz']];
        $this->assertEquals('baz', $this->app->ref('foo.bar', false, $var));

        $this->assertNull($this->app->ref('SESSION.foo'));
    }

    public function testExists()
    {
        $this->assertTrue($this->app->exists('REQ'));
        $this->assertFalse($this->app->exists('foo'));
    }

    public function testGet()
    {
        $ref = &$this->app->get('REQ.METHOD');
        $this->assertEquals('GET', $ref);

        $ref = 'POST';
        $this->assertEquals('POST', $this->app->get('REQ.METHOD'));
    }

    public function setProvider()
    {
        return [
            [
                'bar', 'foo',
            ],
            [
                'bar', 'REQ.foo',
            ],
            [
                'bar', 'GET.foo',
            ],
            [
                'bar', 'POST.foo',
            ],
            [
                'Asia/Makassar', 'TZ',
            ],
            [
                'ASCII', 'ENCODING',
            ],
            [
                '/foo', 'JAR.PATH',
            ],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($expected, $key, $value = null)
    {
        $this->assertEquals($expected, $this->app->set($key, $value ?? $expected)->get($key));
    }

    public function clearProvider()
    {
        return [
            [
                'foo', ['foo', 'bar'],
            ],
            [
                'foo.bar.qux',
            ],
            [
                'foo.bar', ['foo', ['bar' => 'baz', 'qux' => 'quux']], 'foo.qux', 'quux',
            ],
            [
                'REQ.METHOD', null, null, 'GET',
            ],
            [
                'GET.foo', ['GET.foo', 'bar'], 'GET', [],
            ],
            [
                'POST.foo', ['POST.foo', 'bar'], 'POST', [],
            ],
            [
                'SESSION.foo', ['SESSION.foo', 'bar'], 'SESSION', [],
            ],
            [
                'SESSION', ['SESSION.foo', 'bar'], 'SESSION', [],
            ],
            [
                'COOKIE.foo', ['COOKIE.foo', 'bar'], null, '',
            ],
        ];
    }

    /**
     * @dataProvider clearProvider
     */
    public function testClear($key, $sets = null, $get = null, $expected = null)
    {
        if ($sets) {
            $this->app->set(...$sets);
        }

        $value = $this->app->clear($key)->get($get ?? $key);

        if ('COOKIE' === strstr($get ?? $key, '.', true)) {
            $value = array_shift($value);
        }

        $this->assertEquals($expected, $value);
    }

    public function testMset()
    {
        $this->assertEquals('bar', $this->app->mset(['foo' => 'bar'])->get('foo'));
        $this->assertEquals('bar', $this->app->mset(['foo' => 'bar'], 'prefix.')->get('prefix.foo'));
    }

    public function testMclear()
    {
        $this->assertNull($this->app->set('foo', 'bar')->mclear(['foo'])->get('foo'));
    }

    public function testFlash()
    {
        $this->app->set('foo', 'bar');

        $this->assertEquals('bar', $this->app->flash('foo'));
        $this->assertNull($this->app->flash('foo'));
    }

    public function testArrayAccess()
    {
        $this->app['foo'] = 'bar';
        $this->assertEquals('bar', $this->app['foo']);
        $this->assertEquals('GET', $this->app['REQ.METHOD']);
        $this->assertEquals('GET', $this->app['REQ']['METHOD']);
        unset($this->app['foo']);
        $this->assertFalse(isset($this->app['foo']));
    }
}
