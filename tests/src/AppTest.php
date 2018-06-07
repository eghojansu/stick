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

namespace Fal\Stick\Test;

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
        $this->init['tz'] = date_default_timezone_get();

        $this->app = new App();
    }

    public function tearDown()
    {
        $_COOKIE = [];
        header_remove();
        date_default_timezone_set($this->init['tz']);

        $this->app->get('logger')->clear();
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
        $this->app['NAMESPACE'] = [
            '\\' => FIXTURE,
            'Fal\\Stick\\Test\\' => ROOT,
        ];

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

        $this->app->overrideRequestMethod();

        $this->assertEquals('PUT', $this->app['VERB']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
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

        $this->app['CLI'] = $cli;
        $this->app->emulateCliRequest();

        $this->assertEquals('GET', $this->app['VERB']);
        $this->assertEquals($path, $this->app['PATH']);
        $this->assertEquals($query, $this->app['QUERY']);
        $this->assertEquals($fragment, $this->app['FRAGMENT']);
    }

    public function testGroup()
    {
        $this->app->group(['name' => 'foo', 'prefix' => '/foo'], function ($app) {
            $app->route('GET bar /bar', function () {
                return 'foobar';
            });

            $app->route('GET baz /baz', function () {
                return 'foobaz';
            });

            $app->group(['prefix' => '/bleh', 'mode' => 'cli'], function ($app) {
                $app->route('GET /what', function (App $app) {
                    return 'fooblehwhat, cli mode = '.var_export($app['CLI'], true);
                });
            });
        });
        $this->app->group(['prefix' => '/qux'], function ($app) {
            $app->route('GET /quux', function () {
                return 'quxquux';
            });
        });
        $this->app->group(['prefix' => '/any'], function ($app) {
            $app->group(['class' => AnyController::class], function ($app) {
                $app->route('GET /string/{input}', 'any');
            });
            $app->group(['class' => new AnyController()], function ($app) {
                $app->route('GET /instance/{input}', 'any');
            });
        });
        $this->app['QUIET'] = true;

        $this->app->mock('GET foobar');
        $this->assertEquals('foobar', $this->app['OUTPUT']);

        $this->app->mock('GET foobaz');
        $this->assertEquals('foobaz', $this->app['OUTPUT']);

        $this->app->mock('GET /qux/quux');
        $this->assertEquals('quxquux', $this->app['OUTPUT']);

        $this->app->mock('GET /foo/bleh/what cli');
        $this->assertEquals('fooblehwhat, cli mode = true', $this->app['OUTPUT']);

        $this->app->mock('GET /any/string/bar');
        $this->assertEquals('bar', $this->app['OUTPUT']);

        $this->app->mock('GET /any/instance/baz');
        $this->assertEquals('baz', $this->app['OUTPUT']);
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

        $this->app->route($pattern, $callback);

        $this->assertEquals($expected, $this->app['_ROUTES']);
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
            $this->app['PREMAP'] = $prefix;
        }

        $this->app->map($pattern, $class);

        $this->assertEquals($expected, $this->app['_ROUTES']);
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
        $this->app->redirect($pattern, $url, $permanent);
        $set = $this->app['_ROUTES'][$path][$type][$verb];

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
        $this->assertEquals(200, $this->app['CODE']);
        $this->assertEquals('OK', $this->app['STATUS']);
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
            $this->assertEquals($value, $this->app['RESPONSE'][$key]);
        }
    }

    public function pathProvider()
    {
        return [
            [],
            ['/'],
            ['/bar'],
            ['/foo/1'],
            [null, 'home', ['bar' => 'baz']],
            [null, 'home', ['bar' => 'baz'], true],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testPath($path = null, $alias = null, $args = null, $abs = false)
    {
        $req = $this->app;
        $req->route('GET home /home/{bar}', 'foo');
        $host = $abs ? $req['SCHEME'].'://'.$req['HOST'].(in_array($req['PORT'], [80, 443]) ? '' : (':'.$req['PORT'])) : '';
        $expected = $host.$req['BASE'].$req['ENTRY'].($alias ? $req->alias($alias, $args) : $path ?? $req['PATH']);

        $this->assertEquals($expected, $this->app->path($alias ?? $path ?? $req['PATH'], $args, $abs));
    }

    public function testEllapsedTime()
    {
        $this->assertRegExp('/^[\d\.\,]+ seconds$/', $this->app->ellapsedTime());
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
        $this->assertCount(2, $this->app['_LISTENERS']);
        $this->assertCount(2, $this->app['_LISTENERS']['foo']);
        $this->assertCount(1, $this->app['_LISTENERS']['bar']);
    }

    public function testOne()
    {
        $this->app->one('foo', function () {
        })->one('foo', function () {
        })->one('bar', function () {
        });

        $this->assertCount(2, $this->app['_ONCE']);
        $this->assertTrue($this->app['_ONCE']['foo']);
        $this->assertTrue($this->app['_ONCE']['bar']);
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
                        $app['foo'][0] = 1;
                    },
                    'foo.' => function (App $app) {
                        $app['foo'][1] = 2;
                    },
                    'foo..' => function (App $app) {
                        $app['foo'][2] = 3;
                    },
                ], ['foo', [1, 2, 3]],
            ],
            [
                false, 'foo', null, [
                    'foo' => function (App $app) {
                        $app['foo'][0] = 1;
                    },
                    'foo.' => function (App $app) {
                        $app['foo'][1] = 2;

                        return true;
                    },
                    'foo..' => function (App $app) {
                        $app['foo'][2] = 3;
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
            $this->assertEquals($check[1], $this->app[$check[0]]);
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
        $this->app['CLI'] = $cli;
        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        if ($cli) {
            $this->expectOutputRegex($expected);
            $this->app->reroute($url, $permanent);
        } else {
            $this->app->reroute($url, $permanent);
            $this->assertStringEndsWith($url, $this->app['RESPONSE']['Location']);
        }
    }

    public function testRerouteInterception()
    {
        $this->app->on(App::EVENT_REROUTE, function (App $app, $url, $permanent) {
            $app['rerouted'] = $url;
            $app['permanent'] = $permanent;
        });
        $this->app->reroute('/foo');

        $this->assertStringEndsWith('/foo', $this->app['rerouted']);
        $this->assertFalse($this->app['permanent']);
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
                'foo fixed', ['PATH' => '/foo/'], [['GET /foo', function () {
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
                'foo', ['CLI' => false], [['GET /', function (App $app) {
                    $app['RESPONSE']['Content-Type'] = 'text/plain';
                    $app['COOKIE']['foo'] = 'bar';

                    return 'foo';
                }]],
            ],
            [
                'foo', [], [['GET /', function () {
                    return 'foo';
                }, 0, 5]],
            ],
            [
                'foo', ['CLI' => false], [['GET / sync', function () {
                    return 'foo';
                }]],
            ],
            [
                'bar', ['PATH' => '/bar'], [
                    ['GET /foo', function () {
                        return 'foo';
                    }],
                    ['GET /bar', function () {
                        return 'bar';
                    }],
                ],
            ],
            [
                'foo', ['PATH' => '/foo'], [['GET /foo', function () {
                    return 'foo';
                }]],
            ],
            [
                'foobar', ['PATH' => '/foo/bar', 'VERB' => 'POST'], [['POST /foo/{bar}', function ($bar) {
                    return 'foo'.$bar;
                }]],
            ],
            [
                'foo1', ['PATH' => '/foo/1'], [['GET /foo/{id:digit}', function ($id) {
                    return 'foo'.$id;
                }]],
            ],
            [
                'foo', ['PATH' => '/any/foo'], [['GET /{method}/{input}', AnyController::class.'->{method}']],
            ],
        ];
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($expected, $sets, $routes)
    {
        $this->app->mset($sets);

        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        if ($this->app['QUIET']) {
            $this->app->run();

            $this->assertEmpty($this->app['ERROR']);
            $this->assertEquals($expected, $this->app['OUTPUT']);
        } else {
            $this->expectOutputString($expected);
            $this->app->run();
            $this->assertEmpty($this->app['ERROR']);
        }
    }

    public function runErrorProvider()
    {
        return [
            [
                'No route specified', [], [],
            ],
            [
                'bar', [], [['GET /', function () {
                    throw new \Exception('bar');
                }]],
            ],
            [
                'Generated from exception', [], [['GET /', function () {
                    throw new ResponseErrorException('Generated from exception', 404);
                }]],
            ],
            [ // route do not exists
                'Not Found', [], [['GET /foo', 'foo']],
            ],
            [ // invalid mode
                'Not Found', [], [['GET / sync', 'foo']],
            ],
            [ // invalid request method
                'Method Not Allowed', ['CLI' => false], [['POST /', 'foo']],
            ],
            [ // invalid controller (unable to call)
                'Method Not Allowed', [], [['GET /', AnyController::class.'->fakeMethod']],
            ],
            [ // invalid controller (class does not exists)
                'Not Found', [], [['GET /', 'FakeController->method']],
            ],
            [ // runtime error by bad logic
                'Division by zero', [], [['GET /', function () {
                    return 1 / 0;
                }]],
            ],
            [ // Call error on handler
                'Not Found', [], [['GET /', function (App $app) {
                    return $app->error(404, 'Not Found');
                }]],
            ],
        ];
    }

    /**
     * @dataProvider runErrorProvider
     */
    public function testRunError($expected, $sets, $routes, array $error = [])
    {
        $this->app->mset(['QUIET' => true] + $sets);

        foreach ($routes as $route) {
            $this->app->route(...$route);
        }

        $this->app->run();

        $appError = $this->app['ERROR'];
        $this->assertNotEmpty($appError);

        if ($expected) {
            $this->assertContains($expected, $this->app['OUTPUT']);
        }

        $expectedError = array_merge($appError, $error);
        $this->assertEquals($appError, $expectedError);
    }

    public function testRunHeaders()
    {
        $this->app->route('GET /', function () {
            return 'foo';
        });
        $this->app->mset([
            'QUIET' => true,
            'HEADERS' => ['Origin' => 'foo'],
            'CORS' => [
                'ORIGIN' => 'foo',
                'CREDENTIALS' => ['foo'],
                'EXPOSE' => 'foo',
            ],
        ]);
        $this->app->run();

        $output = $this->app['OUTPUT'];
        $headers = $this->app['RESPONSE'];
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
        $this->app->mclear('RESPONSE', 'OUTPUT', 'CODE', 'STATUS', 'ERROR');
        $this->app->mset([
            'CLI' => false,
            'VERB' => 'OPTIONS',
            'HEADERS' => [
                'Access-Control-Request-Method' => 'foo',
            ],
            'CORS' => [
                'HEADERS' => 'foo',
                'TTL' => 10,
            ],
        ]);
        $this->app->run();

        $output = $this->app['OUTPUT'];
        $headers = $this->app['RESPONSE'];
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
        $this->app['QUIET'] = true;

        $this->app->run();
        $this->assertEquals('from controller', $this->app['OUTPUT']);
        $this->assertEquals(1, $this->app['booted']);

        // call once again
        $this->app->run();
        $this->assertEquals('from controller', $this->app['OUTPUT']);
        $this->assertEquals(1, $this->app['booted']);
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

        $this->assertEquals('foo', $this->app['OUTPUT']);

        // second run
        $this->app->mclear('RESPONSE', 'OUTPUT', 'CODE', 'STATUS', 'ERROR');
        $this->app->run();
        $this->assertEquals('foo', $this->app['OUTPUT']);

        // third run, with modified date check
        $this->app->mclear('RESPONSE', 'OUTPUT', 'CODE', 'STATUS', 'ERROR');
        $this->app['HEADERS']['If-Modified-Since'] = gmdate('r', strtotime('+1 minute'));
        $this->app->run();
        $this->assertEquals('', $this->app['OUTPUT']);
        $this->assertEquals(304, $this->app['CODE']);
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
                }], 'Record of user not found (GET /4)',
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
                }], 'Record of composit_user not found (GET /bar/qux)',
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
        $this->app->mset([
            'QUIET' => true,
            'MAPPER' => true,
            'PATH' => $path,
        ]);
        $this->app->set(Connection::class, [
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
            $this->assertEquals($error, $this->app['ERROR']['text']);
        } else {
            $this->assertEquals($expected, $this->app['OUTPUT']);
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
                    return $app['GET']['foo'];
                }]], 'GET home', ['foo' => 'bar'],
            ],
            [
                '/bar/', [['POST /', function (App $app) {
                    return $app['POST']['foo'];
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
                404, 'Page not found', '/Page not found/', ['CLI' => false],
            ],
            [
                403, 'Forbidden', '/Forbidden/', ['CLI' => false, 'AJAX' => true],
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
        $this->app['QUIET'] = true;
        $this->app->error(500, 'Internal server error');
        $this->assertContains('Internal server error', $this->app['ERROR']);
    }

    public function testErrorListener()
    {
        $this->app->one(App::EVENT_ERROR, function (App $app, $error) {
            $app['myerror'] = $error['text'];
        });
        $this->app->error(500, 'Internal server error');
        $this->assertEquals('Internal server error', $this->app['myerror']);

        // listener trigger error
        $message = 'Error triggered from app error listener';

        $this->app['QUIET'] = true;
        $this->app->on(App::EVENT_ERROR, function () use ($message) {
            throw new \Exception($message);
        });

        $this->app->error(404, 'Internal server error');
        $this->assertEquals(500, $this->app['CODE']);
        $this->assertContains($message, $this->app['ERROR']);
        $this->assertContains($message, $this->app['OUTPUT']);
    }

    public function testErrorEnsureLogged()
    {
        $this->app['THRESHOLD'] = Logger::LEVEL_DEBUG;
        $this->app['QUIET'] = true;

        $this->assertEmpty($this->app->get('logger')->files());

        $this->app->error(404);

        $this->assertNotEmpty($this->app->get('logger')->files());
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

    public function setProvider()
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
     * @dataProvider setProvider
     */
    public function testSet($expected, $id, $rule = null)
    {
        $this->app->set($id, $rule);

        $this->assertEquals($expected, $this->app['_SERVICE_RULES'][$id]);
    }

    public function testGet()
    {
        // get self
        $this->assertEquals($this->app, $this->app->get('app'));
        $this->assertEquals($this->app, $this->app->get(App::class));

        // unregistered class
        $first = $this->app->get(DoNotRegisterClass::class);
        $this->assertInstanceOf(DoNotRegisterClass::class, $first);
        $this->assertNotEquals($first, $this->app->get(DoNotRegisterClass::class));

        // Registered class
        $this->app->set('rdt', ReqDateTimeClass::class);
        $rdt = $this->app->get('rdt');
        $this->assertInstanceOf(ReqDateTimeClass::class, $rdt);
        $this->assertEquals($rdt, $this->app->get('rdt'));
        $this->assertEquals($rdt, $this->app->get(ReqDateTimeClass::class));
        $this->assertNotEquals($rdt->dt, $this->app->get(\DateTime::class));
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
            $this->app->set($useId, $rule);
        }

        if ($register) {
            foreach ($register as $id => $rule) {
                $this->app->set($id, $rule);
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
        $this->app->set(ReqDateTimeClass::class, function () {});

        $this->app->create(ReqDateTimeClass::class);
    }

    public function testHive()
    {
        $this->assertNotEmpty($this->app->hive());
    }

    public function testConfig()
    {
        $this->app->config(FIXTURE.'config.php');
        $this->app['QUIET'] = true;

        $this->assertEquals('bar', $this->app['foo']);
        $this->assertEquals('baz', $this->app['bar']);
        $this->assertEquals('quux', $this->app['qux']);
        $this->assertEquals(range(1, 3), $this->app['arr']);
        $this->assertEquals('config', $this->app['sub']);

        $this->app->mock('GET /');
        $this->assertEquals('registered from config', $this->app['OUTPUT']);

        $this->app->mock('GET /foo');
        $this->assertStringEndsWith('/', $this->app['RESPONSE']['Location']);

        $this->app->mock('GET /bar');
        $this->assertEquals('foo', $this->app['OUTPUT']);

        $this->app->mock('GET /group/index');
        $this->assertEquals('group index', $this->app['OUTPUT']);

        $this->assertTrue($this->app->trigger('foo'));
        $this->assertInstanceOf(NoConstructorClass::class, $this->app->get('foo'));
    }

    public function testMset()
    {
        $this->app->mset(['foo' => 'bar']);
        $this->assertEquals('bar', $this->app['foo']);
    }

    public function testMclear()
    {
        $this->app['foo'] = 'bar';
        $this->app->mclear('foo');
        $this->assertNull($this->app['foo']);
    }

    public function testFlash()
    {
        $this->app['foo'] = 'bar';

        $this->assertEquals('bar', $this->app->flash('foo'));
        $this->assertNull($this->app->flash('foo'));
    }

    public function testSend()
    {
        $this->app['OUTPUT'] = 'foo';

        $this->expectOutputString('foo');
        $this->app->send();
    }

    public function sendContentProvider()
    {
        return [
            [
                '', ['QUIET' => true],
            ],
            [
                'foo', ['OUTPUT' => 'foo'],
            ],
            [
                'foo', ['OUTPUT' => 'foo', 'KBPS' => 10],
            ],
        ];
    }

    /**
     * @dataProvider sendContentProvider
     */
    public function testSendContent($expected, $sets)
    {
        $this->app->mset($sets);

        $this->expectOutputString($expected);
        $this->app->sendContent();
    }

    public function sendHeadersProvider()
    {
        return [
            [
                [],
            ],
            [
                [
                    'RESPONSE' => ['Location' => '/foo'],
                    'CLI' => false,
                ],
                [
                    '/^Location: \/foo$/' => 1,
                ],
            ],
            [
                [
                    'CLI' => false,
                    'COOKIE' => ['foo' => ['bar']],
                ],
                [
                    '/^Set-Cookie: foo=bar/' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider sendHeadersProvider
     */
    public function testSendHeaders($sets, $checks = null)
    {
        $this->app->mset($sets);

        $this->assertEquals($this->app, $this->app->sendHeaders());

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();

            foreach ($checks ?? [] as $pattern => $expected) {
                $checked = preg_grep($pattern, $headers);

                $this->assertCount($expected, $checked);
            }
        }
    }

    public function commitStateProvider()
    {
        return [
            [
                [],
            ],
            [
                ['SESSION' => ['foo' => 'bar'], 'COOKIE' => ['bar' => 'baz']],
                ['foo' => 'bar'],
                ['bar' => 'baz'],
                'bar',
            ],
        ];
    }

    /**
     * @dataProvider commitStateProvider
     */
    public function testCommitState($sets, $session = [], $cookie = [], $removeCookie = null)
    {
        $this->app->mset($sets);
        $this->app->sendHeaders();

        $this->assertEquals($session, $_SESSION);
        $this->assertEquals($cookie, $_COOKIE);
        $this->assertEquals($session, $this->app['SESSION']);
        $this->assertEquals($cookie, $this->app['COOKIE']);

        if ($removeCookie) {
            $this->assertTrue(isset($this->app['COOKIE'][$removeCookie]));
            $this->assertTrue(isset($_COOKIE[$removeCookie]));

            unset($this->app['COOKIE'][$removeCookie]);
            // modify the initial value manually
            $prop = new \ReflectionProperty($this->app, 'init');
            $prop->setAccessible(true);
            $prop->setValue($this->app, ['COOKIE' => ['bar' => 'baz']] + $prop->getValue($this->app));
            $this->app->sendHeaders();

            $this->assertFalse(isset($this->app['COOKIE'][$removeCookie]));
            $this->assertFalse(isset($_COOKIE[$removeCookie]));
        }
    }

    public function testTracify()
    {
        $this->assertNotEmpty($this->app->tracify());
    }

    public function testOffsetExists()
    {
        $this->assertTrue($this->app->offsetExists('PACKAGE'));
        $this->assertFalse($this->app->offsetExists('foo'));
    }

    public function testOffsetGet()
    {
        $this->assertEquals(App::PACKAGE, $this->app->offsetGet('PACKAGE'));
        $this->assertNull($this->app->offsetGet('foo'));
    }

    public function testOffsetSet()
    {
        $this->app->offsetSet('foo', 'bar');
        $this->assertEquals('bar', $this->app->offsetGet('foo'));

        $set = 'Asia/Makassar';
        $this->app->offsetSet('TZ', $set);
        $this->assertEquals($set, $this->app->offsetGet('TZ'));
        $this->assertEquals($set, date_default_timezone_get());

        $set = 'ASCII';
        $this->app->offsetSet('ENCODING', $set);
        $this->assertEquals($set, $this->app->offsetGet('ENCODING'));
        $this->assertEquals($set, ini_get('default_charset'));

        $set = '';
        $this->app->offsetSet('ENTRY', $set);
        $this->assertEquals($set, $this->app->offsetGet('ENTRY'));
    }

    public function testOffsetUnset()
    {
        $this->app['foo'] = 'bar';
        $this->app->offsetUnset('PACKAGE');
        $this->assertEquals(App::PACKAGE, $this->app->offsetGet('PACKAGE'));
        $this->assertEquals('bar', $this->app->offsetGet('foo'));

        $this->app->offsetUnset('foo');
        $this->assertNull($this->app->offsetGet('foo'));

        $this->app->offsetSet('COOKIE', ['bar' => 'baz']);
        $this->app->offsetUnset('COOKIE');
        $this->assertEquals([], $this->app->offsetGet('COOKIE'));
    }
}
