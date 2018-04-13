<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick as f;
use Fal\Stick\App;
use Fal\Stick\Cache;
use Fal\Stick\Template;
use Fal\Stick\Test\fixture\classes\DepA;
use Fal\Stick\Test\fixture\classes\DepDateTime;
use Fal\Stick\Test\fixture\classes\DepDepAIndB;
use Fal\Stick\Test\fixture\classes\FixCommon;
use Fal\Stick\Test\fixture\classes\FixController;
use Fal\Stick\Test\fixture\classes\FixResource;
use Fal\Stick\Test\fixture\classes\IndA;
use Fal\Stick\Test\fixture\classes\IndB;
use Fal\Stick\Test\fixture\classes\UserEntity;
use Fal\Stick\Test\fixture\classes\autoload\LoadA;
use PHPUnit\Framework\TestCase;
use classes\autoload\LoadB;

class AppTest extends TestCase
{
    private $app;

    public function setUp()
    {
        $_SERVER['argc'] = 1;
        $_SERVER['argv'] = [$_SERVER['argv'][0]];
        $_SERVER['CONTENT_LENGTH'] = 0;
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        $_SERVER['HTTP_FOO'] = 'bar';

        $this->app = new App();
        $this->app->sets([
            'TEMP' => TEMP,
            'HALT' => false,
        ]);
    }

    public function tearDown()
    {
        header_remove();

        $log = TEMP . 'error.log';
        if (file_exists($log)) {
            unlink($log);
        }

        $this->app->clears(explode('|', App::GLOBALS));
    }

    protected function registerRoutes()
    {
        $this->app
            ->route('GET foo /foo', function() {
                echo 'foo';
            })
            ->route('POST bar /bar', function() {
                echo 'bar';
            })
            ->route('POST qux /qux/{quux}', function($quux) {
                echo 'qux' . $quux;
            })
            ->route('PUT quux /quux/{corge}/{grault}', function($corge, $grault) {
                echo 'quux' . $corge . $grault;
            })
            ->route('GET /cli cli', function() {
                echo 'cli foo';
            })
            ->route('GET /sync sync', function() {
                echo 'sync foo';
            })
            ->route('GET custom /custom/{custom}', FixController::class . '->{custom}')
            ->route('GET invalidclass /invalidclass', 'InvalidClass->invalid')
            ->route('GET invalidmethod /invalidmethod', FixController::class .'->invalid')
            ->route('GET invalidfunction /invalidfunction', 'invalidfunction')
            ->route('GET emptycallback /emptycallback', null)
            ->route('GET /cookie', function(App $app) {
                $app->set('COOKIE.foo', 'bar');

                echo 'cookie';
            })
            ->route('GET /throttled', function() {
                echo 'throttled';
            }, 0, 1)
            ->route('GET /cached', function() {
                echo 'cached'.microtime(true);
            }, 5)
        ;
    }

    public function testConstruct()
    {
        $this->assertEquals('bar', $this->app['HEADERS.Foo']);
    }

    public function testConstructInCliMode()
    {
        $_SERVER['argv']   = [$_SERVER['argv'][0]];
        $_SERVER['argv'][] = 'foo';
        $_SERVER['argv'][] = 'bar';
        $_SERVER['argv'][] = '-opt';
        $_SERVER['argv'][] = '-uvw=baz';
        $_SERVER['argv'][] = '--qux=quux';
        $_SERVER['argc']   = 6;

        $app = new App();

        $this->assertEquals('/foo/bar', $app['PATH']);
        $this->assertEquals('o=&p=&t=&u=&v=&w=baz&qux=quux', $app['QUERY']);
    }

    public function testGetHive()
    {
        $this->app['foo'] = 'bar';
        $this->assertContains('bar', $this->app->getHive());
    }

    public function testGrab()
    {
        $this->assertEquals('foo', $this->app->grab('foo'));
        $this->assertEquals(['Foo','bar'], $this->app->grab('Foo::bar'));
        $this->assertEquals([new UserEntity,'getFirstName'], $this->app->grab(UserEntity::class . '->getFirstName'));
    }

    public function testAgent()
    {
        $this->assertEquals('foo', $this->app->agent(['User-Agent'=>'foo']));
        $this->assertEquals('bar', $this->app->agent(['X-Operamini-Phone-Ua'=>'bar']));
        $this->assertEquals('baz', $this->app->agent(['X-Skyfire-Phone'=>'baz']));
        $this->assertEquals('', $this->app->agent(null));
    }

    public function testAjax()
    {
        $this->assertFalse($this->app->ajax());
        $this->assertTrue($this->app->ajax(['X-Requested-With'=>'xmlhttprequest']));
    }

    public function testIp()
    {
        $this->assertEquals('foo', $this->app->ip(['Client-Ip'=>'foo']));
        $this->assertEquals('bar', $this->app->ip(['X-Forwarded-For'=>'bar']));
        $_SERVER['REMOTE_ADDR'] = 'baz';
        $this->assertEquals('baz', $this->app->ip());
        unset($_SERVER['REMOTE_ADDR']);
        $this->assertEquals('', $this->app->ip());
    }

    public function testStatus()
    {
        $this->assertEquals('Not Found', $this->app->status(404)->get('TEXT'));
    }

    public function testSendHeader()
    {
        $this->app['CLI'] = false;
        $this->app->header('Location', '/foo');
        $this->app->set('COOKIE.foo', 'bar', 5);
        $this->app->sendHeader();

        $this->assertEquals(['/foo'], $this->app->getHeader('location'));

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertNotEmpty(preg_grep('~^Location: /foo~', $headers));
            $this->assertNotEmpty(preg_grep('~^Set-Cookie: foo=bar~', $headers));
        }
    }

    public function testText()
    {
        $this->expectOutputString('foo');

        $this->app->text('foo');

        $this->assertEquals(['text/plain;charset=' . ini_get('default_charset')], $this->app->getHeader('Content-Type'));
        $this->assertEquals(['3'], $this->app->getHeader('Content-Length'));
    }

    public function testHtml()
    {
        $this->expectOutputString('foo');

        $this->app->html('foo');

        $this->assertEquals(['text/html;charset=' . ini_get('default_charset')], $this->app->getHeader('Content-Type'));
        $this->assertEquals(['3'], $this->app->getHeader('Content-Length'));
    }

    public function testJson()
    {
        $this->expectOutputString('{"foo":"bar"}');

        $this->app->json(['foo'=>'bar']);

        $this->assertEquals(['application/json;charset=' . ini_get('default_charset')], $this->app->getHeader('Content-Type'));
        $this->assertEquals(['13'], $this->app->getHeader('Content-Length'));
    }

    public function runProvider()
    {
        return [
            // valid routes
            ['GET /foo', 'foo'],
            ['POST bar', 'bar'],
            ['POST qux(quux=corge)', 'quxcorge'],
            ['PUT quux(corge=grault,grault=garply)', 'quuxgraultgarply'],
            ['GET /cli cli', 'cli foo'],
            ['GET /sync sync', 'sync foo'],

            // with fragment
            [['GET foo#fragmentfoo', ['foo'=>'bar']], 'foo', ['PATH'=>'/foo','QUERY'=>'foo=bar','FRAGMENT'=>'fragmentfoo']],
            [['POST /bar#fragmentbar', ['bar'=>'baz'], ['Content-Type'=>'text/html'], 'foo=bar'], 'bar', ['PATH'=>'/bar','FRAGMENT'=>'fragmentbar']],

            // invalid (extra trailing slash)
            ['GET /bar/', 'regex/Not Found/', ['TEXT'=>'Not Found','ERROR.status'=>'Not Found']],

            // invalid request method
            ['POST foo', 'regex/Method Not Allowed/', ['TEXT'=>'Method Not Allowed','PATH'=>'/foo']],

            // invalid mode
            ['GET /sync ajax', 'regex/Not Found/', ['PATH'=>'/sync']],

            // invalid class (not exists)
            ['GET invalidclass', 'regex/Not Found/', ['TEXT'=>'Not Found','PATH'=>'/invalidclass']],

            // invalid class method
            ['GET invalidmethod', 'regex/Not Found/', ['TEXT'=>'Not Found','PATH'=>'/invalidmethod']],

            // invalid function
            ['GET invalidfunction', 'regex/Internal Server Error/', ['TEXT'=>'Internal Server Error','PATH'=>'/invalidfunction']],

            // invalid callback
            ['GET emptycallback', 'regex/Internal Server Error/', ['TEXT'=>'Internal Server Error','PATH'=>'/emptycallback']],

            // pure not found
            ['GET /none', 'regex/Not Found/', ['TEXT'=>'Not Found','ERROR.status'=>'Not Found','PATH'=>'/none']],

            // with cookie send
            ['GET /cookie', 'cookie', ['RHEADERS.Set-Cookie'=>['foo=bar; Path=/; HttpOnly']]],
        ];
    }

    /** @dataProvider runProvider */
    public function testRun($args, $output, array $hive = null)
    {
        $args = (array) $args;

        $this->registerRoutes();

        if ($r = f\cutafter('regex', $output)) {
            $this->expectOutputRegex($r);
        } else {
            $this->expectOutputString($output);
        }

        $this->app->mock(...$args);

        foreach ((array) $hive as $key => $value) {
            $this->assertEquals($value, $this->app[$key]);
        }
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No route specified
     */
    public function testRunException()
    {
        $this->app->run();
    }

    public function testRunEventBeforeRoute()
    {
        $this->registerRoutes();
        $this->app->set('EVENT.BEFOREROUTE', function(App $app) {
            $app->set('RESPONSE', 'beforeroute');
        });
        $this->app['QUIET'] = true;
        $this->app->mock('GET /foo');
        $this->assertEquals('beforeroute', $this->app['RESPONSE']);
    }

    public function testRunEventAfterRoute()
    {
        $this->registerRoutes();
        $this->app->set('EVENT.AFTERROUTE', function(App $app) {
            $app->set('RESPONSE', 'afterroute');
        });
        $this->app['QUIET'] = true;
        $this->app->mock('GET /foo');
        $this->assertEquals('afterroute', $this->app['RESPONSE']);
    }

    public function testRunCache()
    {
        $this->registerRoutes();
        $this->app['QUIET'] = true;
        $this->app['CACHE'] = 'folder=' . TEMP . 'cache/';

        $this->app->mock('GET /cached');

        $cache = TEMP . 'cache/' . $this->app['SEED'] . '.' .
                 f\hash($this->app['METHOD'] . ' ' . $this->app['URI']) . '.url';
        $this->assertFileExists($cache);
        $init = $this->app['RESPONSE'];
        $this->assertContains('cached', $init);

        $this->app->mock('GET /cached');
        $this->assertEquals($init, $this->app['RESPONSE']);

        $this->app->mock('GET /cached', null, ['If-Modified-Since'=>gmdate('r', strtotime('+1 minute'))]);
        $this->assertEquals('Not Modified', $this->app['TEXT']);
    }

    public function testRunThrottled()
    {
        $this->registerRoutes();
        $this->expectOutputString('throttled');

        $start = microtime(true);
        $this->app->mock('GET /throttled');
        $end = microtime(true) - $start;

        $this->assertGreaterThan(1, $end);
    }

    public function testRunQuiet()
    {
        $this->registerRoutes();
        $this->app['QUIET'] = true;
        $this->app->mock('GET /foo');
        $this->assertEquals('foo', $this->app['RESPONSE']);
    }

    public function testRunCors()
    {
        $this->registerRoutes();

        $this->app->merge('CORS', [
            'origin' => 'example.com',
            'expose' => 'example.com',
            'headers' => 'example.com',
            'ttl' => 10,
        ], true);

        $this->expectOutputString('foo');

        $this->app->mock('GET /foo', null, ['Origin'=>'example.com']);
    }

    public function testRunCors403()
    {
        $this->registerRoutes();

        $this->app->merge('CORS', [
            'origin' => 'example.com',
            'expose' => 'example.com',
            'headers' => 'example.com',
            'ttl' => 10,
        ], true);

        $this->expectOutputRegex('/Method Not Allowed/');

        // 403
        $this->app->mock('POST /foo', null, ['Origin'=>'example.com']);

        $this->assertEquals('Method Not Allowed', $this->app['TEXT']);
    }

    public function testMock()
    {
        // Covered in testRun
        $this->assertTrue(true);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Mock pattern should contain at least request method and path, given "GET"
     */
    public function testMockException()
    {
        $this->app->mock('GET');
    }

    public function testReroute()
    {
        // in cli mode
        $this->registerRoutes();

        $this->expectOutputString('cli foo');

        $this->app->reroute('/cli', false, false);

        $this->assertEquals('/cli', $this->app['PATH']);
    }

    public function testRerouteWithHandler()
    {
        $this->registerRoutes();

        $this->app->set('EVENT.REROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });

        $this->expectOutputString('');

        $this->app->reroute('foo', false, false);

        $this->assertEquals(['/foo', false], $this->app['reroute']);
    }

    public function testRerouteWithHeader()
    {
        $this->registerRoutes();

        // only if xdebug extension enabled
        $this->assertTrue(true);

        if (function_exists('xdebug_get_headers')) {
            $this->app['CLI'] = false;

            $realm = $this->app['SCHEME'] . '://' . $this->app['HOST'];

            ob_start();

            $this->app->reroute(null, false, false);
            $this->assertContains("Location: $realm/", xdebug_get_headers());

            $this->app->reroute(['qux', ['quux'=>'corge']], false, false);
            $this->assertContains("Location: $realm/qux/corge", xdebug_get_headers());

            $this->app->reroute('quux(corge=grault,grault=garply)', false, false);
            $this->assertContains("Location: $realm/quux/grault/garply", xdebug_get_headers());

            ob_end_clean();
        }
    }

    public function testBuild()
    {
        $this->app->route('GET foo /foo/{bar}', function() {});

        $this->assertEquals('/foo/baz', $this->app->build('foo(bar=baz)'));
        $this->assertEquals('/foo/qux', $this->app->build('foo(bar=qux)'));
        $this->assertEquals('/foo/bar', $this->app->build('/foo/bar'));
    }

    public function testAlias()
    {
        $this->app->route('GET foo /foo/{bar}', function() {});

        $this->assertEquals('/foo/baz', $this->app->alias('foo', ['bar'=>'baz']));
        $this->assertEquals('/foo/qux', $this->app->alias('foo', ['bar'=>'qux']));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Alias "foo" does not exists
     */
    public function testAliasException()
    {
        $this->app->alias('foo');
    }

    public function testUnload()
    {
        // skipped
        $this->assertTrue(true);
    }

    public function testError()
    {
        $this->app['QUIET'] = true;
        $this->app['QUERY'] = 'foo=bar';

        $req = $this->app['METHOD'] . ' ' . $this->app['PATH'] . '?foo=bar';

        $this->app->error(404);
        $error = $this->app['ERROR'];

        $this->assertContains('Not Found', $error);
        $this->assertContains(404, $error);
        $this->assertContains("HTTP 404 ($req)", $error);

        $this->app->error(500);

        $error = $this->app['ERROR'];
        $this->assertContains(404, $error);

        $this->app->clear('ERROR');
        $this->app['EVENT.ERROR'] = function(App $app) {
            $app->set('foo_error.bar', 'baz');
        };

        $this->app->error(404);
        $this->assertEquals(['bar'=>'baz'], $this->app['foo_error']);
    }

    public function testErrorOutputAjax()
    {
        $this->app['AJAX'] = true;

        $this->expectOutputRegex('/"status":"Not Found"/');

        $this->app->error(404);
        $this->assertContains('application/json', $this->app->getHeader('Content-Type')[0]);
    }

    public function testErrorOutputHtml()
    {
        $this->expectOutputRegex('~<title>404 Not Found</title>~');

        $this->app->error(404);
        $this->assertContains('text/html', $this->app->getHeader('Content-Type')[0]);
    }

    public function testErrorLog()
    {
        $this->app['LOG_ERROR'] = [
            'enabled' => true,
            'type' => 3,
            'destination' => TEMP . 'error.log',
        ];
        $this->app['QUIET'] = true;

        $this->app->error(404);

        $this->assertFileExists($this->app['LOG_ERROR.destination']);
    }

    public function testHalt()
    {
        // skipped
        $this->assertTrue(true);
    }

    public function testExpire()
    {
        $this->app['CLI'] = false;

        $this->assertEquals($this->app, $this->app->expire(0));

        $this->app->expire(-1);
        $this->assertEquals([], $this->app->getHeader('Pragma'));

        $this->app['METHOD'] = 'POST';
        $this->app->expire(-1);
        $this->assertEquals('no-cache', $this->app->getHeader('Pragma')[0]);
    }

    public function testResources()
    {
        $class = FixResource::class;
        $this->app->resources(['foo', 'bar'], $class, 0, 0, ['index','store']);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 0, 0, 'foo_index'],
                    'POST' => ["{$class}->store", 0, 0, 'foo_store'],
                ],
            ],
            '/bar' => [
                0 => [
                    'GET' => ["{$class}->index", 0, 0, 'bar_index'],
                    'POST' => ["{$class}->store", 0, 0, 'bar_store'],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app['ROUTES']);
    }

    public function testResource()
    {
        $class = FixResource::class;
        $this->app->resource('foo', $class);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 0, 0, 'foo_index'],
                    'POST' => ["{$class}->store", 0, 0, 'foo_store'],
                ],
            ],
            '/foo/create' => [
                0 => [
                    'GET' => ["{$class}->create", 0, 0, 'foo_create'],
                ],
            ],
            '/foo/{foo}' => [
                0 => [
                    'GET' => ["{$class}->show", 0, 0, 'foo_show'],
                    'PUT' => ["{$class}->update", 0, 0, 'foo_update'],
                    'DELETE' => ["{$class}->destroy", 0, 0, 'foo_destroy'],
                ],
            ],
            '/foo/{foo}/edit' => [
                0 => [
                    'GET' => ["{$class}->edit", 0, 0, 'foo_edit'],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app['ROUTES']);

        $this->app->clear('ROUTES');

        $this->app->resource('foo', $class, 0, 0, ['index','store']);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 0, 0, 'foo_index'],
                    'POST' => ["{$class}->store", 0, 0, 'foo_store'],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app['ROUTES']);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Resource pattern should contain at least route name, given ""
     */
    public function testResourceException()
    {
        $this->app->resource('', FixResource::class);
    }

    public function testMaps()
    {
        $this->app->maps(['/', '/home'], FixController::class);

        $routes = $this->app['ROUTES'];

        $this->assertEquals(2, count($routes));
    }

    public function testMap()
    {
        $this->app->map('/', FixController::class);

        $routes = $this->app['ROUTES'];

        $this->assertEquals(1, count($routes));
    }

    public function testRedirects()
    {
        $this->app->redirects(['GET /foo', 'GET /bar'], '/baz');

        $routes = $this->app['ROUTES'];

        $this->assertEquals(2, count($routes));
    }

    public function testRedirect()
    {
        $this->app->redirect('GET /foo', '/bar');
        $this->app->set('EVENT.REROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });

        $routes = $this->app['ROUTES'];

        $this->assertEquals(1, count($routes));

        $this->app->mock('GET /foo');
        $this->assertEquals(['/bar', true], $this->app['reroute']);
    }

    public function testRoutes()
    {
        $this->app->routes(['GET /', 'GET dashboard /dashboard'], function() {});

        $routes = $this->app['ROUTES'];

        $this->assertEquals(2, count($routes));
    }

    public function testRoute()
    {
        $handler = 'fakeHandler';
        $this->app->route('GET /', $handler);
        $this->app->route('GET home /home', $handler);
        $this->app->route('GET|POST user /user ajax', $handler);
        $this->app->route('GET /profile sync', $handler);
        $this->app->route('GET /command cli', $handler);
        $this->app->route('GET /product/{keyword}', $handler);
        $this->app->route('GET /product/{id:digit}', $handler);
        $this->app->route('GET /category/{category:word}', $handler);
        $this->app->route('GET /post/{post:custom}', $handler);
        $this->app->route('GET /regex/(?<regex>[[:alpha:]])', $handler);

        $expected = [
            '/' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/home' => [
                0 => [
                    'GET' => [$handler, 0, 0, 'home'],
                ],
            ],
            '/user' => [
                2 => [
                    'GET' => [$handler, 0, 0, 'user'],
                    'POST' => [$handler, 0, 0, 'user'],
                ],
            ],
            '/profile' => [
                1 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/command' => [
                4 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/product/{keyword}' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/product/{id:digit}' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/category/{category:word}' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/post/{post:custom}' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
            '/regex/(?<regex>[[:alpha:]])' => [
                0 => [
                    'GET' => [$handler, 0, 0, null],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app['ROUTES']);
    }

    public function testRouteAlias()
    {
        $this->app->route('GET foo /foo', 'handler');
        $this->app->route('POST foo', 'handler');

        $routes = $this->app['ROUTES'];
        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ['handler', 0, 0, 'foo'],
                    'POST' => ['handler', 0, 0, 'foo'],
                ],
            ],
        ];

        $this->assertEquals($expected, $routes);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Route pattern should contain at least request method and path, given "GET"
     */
    public function testRouteInvalid()
    {
        $this->app->route('GET', function() {});
    }

    public function testConfig()
    {
        $this->app->config(FIXTURE . 'config.ini');

        $this->assertEquals('bar', $this->app['foo']);
        $this->assertEquals(['baz1','baz2'], $this->app['baz']);
        $this->assertEquals(['one'=>1,'two'=>true,'three'=>false,'four'=>null], $this->app['qux']);

        $this->assertEquals('bar', $this->app['section.foo']);
        $this->assertEquals('baz', $this->app['sec.foo.bar']);

        $this->assertEquals('foo', $this->app['glob']);

        $this->assertEquals([1,2,3], $this->app['numbers']);

        $routes = $this->app['ROUTES'];
        $aliases = $this->app['ALIASES'];

        $this->assertTrue(isset($routes['/route']));
        $this->assertTrue(isset($routes['/map']));
        $this->assertTrue(isset($routes['/redirect']));
        $this->assertTrue(isset($routes['/resource']));
        $this->assertTrue(isset($aliases['resource_index']));

        // mock registered route
        $this->app->mock('GET /route');
        $this->assertEquals('foo', $this->app['custom']);
    }

    public function testConfigEmpty()
    {
        $this->app->config(FIXTURE . 'config_empty.ini');

        $this->assertNull($this->app['foo']);
    }

    public function testConfigConfig()
    {
        $this->app['CONFIG_DIR'] = FIXTURE;
        $this->app->config(FIXTURE . 'configs.ini');

        $this->assertEquals('bar', $this->app['foo']);
        $this->assertEquals(1, count($this->app['ROUTES']));
    }

    public function testTrace()
    {
        $eol = "\n";
        $traceStr = $this->app->trace();
        $expected = '[tests/Unit/' . basename(__FILE__) . ':' . (__LINE__ - 1) .
                    '] '. App::class . '->trace()';
        $this->assertContains($expected, $traceStr);

        $traceStr2 = $this->app->trace($trace, false);
        $expected2 = __FILE__;
        $this->assertEquals('', $traceStr2);
        $this->assertContains($expected2, $trace[0]);
    }

    public function testHeaders()
    {
        $this->app->headers(['Location'=>'/foo','Content-Length'=>'0']);
        $this->assertEquals(['/foo'], $this->app->getHeader('Location'));
        $this->assertEquals(['0'], $this->app->getHeader('Content-Length'));
    }

    public function testHeader()
    {
        $this->app->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->app->getHeader('Location'));
    }

    public function testGetHeaders()
    {
        $this->app->header('Location', '/foo');
        $this->assertEquals(['Location'=>['/foo']], $this->app->getHeaders());
    }

    public function testGetHeader()
    {
        $this->app->header('Foo', 'foo');
        $this->app->header('bar', 'bar');
        $this->assertEquals(['foo'], $this->app->getHeader('foo'));
        $this->assertEquals(['foo'], $this->app->getHeader('Foo'));
        $this->assertEquals(['bar'], $this->app->getHeader('bar'));
    }

    public function testRemoveHeader()
    {
        $this->app->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->app->getHeader('Location'));
        $this->app->removeHeader('Location');
        $this->assertEmpty($this->app['RHEADERS']);

        $this->app->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->app->getHeader('location'));
        $this->app->removeHeader();
        $this->assertEmpty($this->app['RHEADERS']);
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->app->call('trim', ' foo '));
        $this->assertEquals('foobar', $this->app->call(FixCommon::class.'->prefixFoo', 'bar'));
        $this->assertEquals('quxquux', $this->app->call(FixCommon::class.'::prefixQux', 'quux'));
        $this->assertEquals('foobarbaz', $this->app->call(function(...$args) {
            return implode('', $args);
        }, ['foo','bar','baz']));
    }

    public function testService()
    {
        $this->assertEquals($this->app, $this->app->service('app'));
        $this->assertEquals($this->app, $this->app->service(App::class));

        $cache = $this->app->service('cache');
        $this->assertInstanceof(Cache::class, $cache);
        $this->assertEquals($cache, $this->app->service(Cache::class));

        $template = $this->app->service('template');
        $this->assertInstanceof(Template::class, $template);
        $this->assertEquals($template, $this->app->service(Template::class));

        $inda = $this->app->service(IndA::class);
        $this->assertInstanceof(IndA::class, $inda);

        // service
        $this->app->set('SERVICE.foo', DepA::class);
        $foo = $this->app->service('foo');
        $this->assertInstanceof(DepA::class, $foo);
        $this->assertEquals($foo, $this->app->service(DepA::class));

        // service with placeholder parameter
        $this->app->set('bar', 'baz');
        $this->app->set('SERVICE.bar', [
            'class' => DepDepAIndB::class,
            'params' => [
                'depa' => '%foo%',
                'indb' => IndB::class,
                'foo' => '%bar%'
            ],
        ]);
        $bar = $this->app->service('bar');
        $this->assertInstanceof(DepDepAIndB::class, $bar);
        $this->assertEquals($foo, $bar->depa);
        $this->assertEquals('baz', $bar->foo);

        // Service with global class dependency
        $depdt = $this->app->service(DepDateTime::class);
        $this->assertInstanceof(DepDateTime::class, $depdt);

        $dt = $this->app->service(\DateTime::class);
        $this->assertInstanceof(\DateTime::class, $dt);

        // service with closure constructor
        $this->app->set('SERVICE.closure', function(App $app) {
            return new \DateTime();
        });
        $closure = $this->app->service('closure');
        $this->assertInstanceof(\DateTime::class, $closure);

        $user = new UserEntity;
        $user->setFirstName('foo');
        $this->app->set('SERVICE.user', $user);
        $fromService = $this->app->service('user');
        $this->assertEquals($user, $fromService);
    }

    public function testService2()
    {
        $inda = new IndA;
        $depa = $this->app->service(DepA::class, [$inda]);
        $this->assertInstanceof(DepA::class, $depa);
        $this->assertEquals($inda, $depa->inda);
    }

    public function testRef()
    {
        $foo = $this->app->ref('foo');
        $this->assertNull($foo);

        $foo =& $this->app->ref('foo');
        $foo = 'bar';
        $this->assertEquals('bar', $this->app['foo']);

        $bar =& $this->app->ref('bar');
        $bar = new \StdClass;
        $bar->foo = 'baz';
        $this->assertEquals('baz', $this->app['bar.foo']);
        $this->assertNull($this->app['bar.baz']);
    }

    public function testGet()
    {
        $this->assertNull($this->app->get('foo'));
        $this->assertEquals('bar', $this->app->get('foo', 'bar'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->app->set('foo', 'bar')->get('foo'));
        $this->assertEquals('bar', $this->app->set('COOKIE.foo', 'bar')->get('COOKIE.foo'));
        $this->assertEquals('bar', $this->app->set('POST.foo', 'bar')->get('POST.foo'));
        $this->assertEquals('bar', $this->app->get('REQUEST.foo'));

        // update timezone
        $this->app->set('TZ', 'Asia/Jakarta');
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());

        // serializer
        $this->app->set('SERIALIZER', 'php');
        $raw = ['foo'=>'bar'];
        $serialized = serialize($raw);
        $this->assertEquals($serialized, f\serialize($raw));
        $this->assertEquals($raw, f\unserialize($serialized));

        // set cache
        $this->app->set('CACHE', '');
        $this->app->set('SEED', 'test');

        // URI
        $this->app->set('URI', '/foo');
        $this->assertEquals('/foo', $_SERVER['REQUEST_URI']);

        // JAR
        $this->assertEquals('foo.com', $this->app->set('JAR.domain', 'foo.com')->get('JAR.domain'));
        $this->assertEquals(true, $this->app->set('JAR.secure', true)->get('JAR.secure'));
        $this->assertEquals('foo.com', $this->app->set('JAR', $this->app['JAR'])->get('JAR.domain'));

        // SET COOKIE with domain
        $this->app->set('COOKIE.domain', 'foo');
        $this->assertEquals('foo', $_COOKIE['domain']);
        $this->assertContains('Domain=foo.com', $this->app->getHeader('Set-Cookie')[1]);
        $this->assertContains('Secure', $this->app->getHeader('Set-Cookie')[1]);
    }

    public function testExists()
    {
        $this->assertFalse($this->app->exists('foo'));
        $this->assertTrue($this->app->set('foo', 'bar')->exists('foo'));
    }

    public function testClear()
    {
        $this->assertFalse($this->app->set('foo', 'bar')->clear('foo')->exists('foo'));

        $this->assertFalse($this->app->exists('foo.bar'));
        $this->app->clear('foo.bar');
        $this->assertFalse($this->app->exists('foo.bar'));

        // obj remove
        $this->app->set('foo', new \StdClass);
        $this->assertFalse($this->app->exists('foo.bar'));
        $this->app->set('foo.bar', 'baz');
        $this->assertEquals('baz', $this->app['foo.bar']);
        $this->app->clear('foo.bar');
        $this->assertFalse($this->app->exists('foo.bar'));

        // reset
        $init = $this->app['URI'];

        // change
        $this->app['URI'] = '/foo';
        $this->assertNotEquals($init, $this->app['URI']);

        unset($this->app['URI']);
        $this->assertEquals($init, $this->app['URI']);

        // REQUEST
        $this->app->set('GET.foo', 'bar');
        $this->assertEquals('bar', $this->app['REQUEST.foo']);
        $this->app->clear('GET.foo');
        $this->assertNull($this->app['REQUEST.foo']);

        // SESSION
        $this->app->set('SESSION.foo', 'bar');
        $this->assertEquals('bar', $this->app['SESSION.foo']);
        $this->app->clear('SESSION.foo');
        $this->assertNull($this->app['SESSION.foo']);

        // SERVICE
        $this->app->set('SERVICE.foo', FixCommon::class);
        $this->assertEquals(['class'=>FixCommon::class, 'keep'=>true], $this->app['SERVICE.foo']);
        $this->app->clear('SERVICE.foo');
        $this->assertNull($this->app['SERVICE.foo']);

        // COOKIE
        $this->app->set('COOKIE.foo', 'bar');
        $this->assertContains('foo=bar', $this->app->getHeader('Set-Cookie')[0]);
        $this->assertTrue(isset($_COOKIE['foo']));
        $this->app->clear('COOKIE.foo');
        $this->assertContains('foo=bar', $this->app->getHeader('Set-Cookie')[0]);
        $this->assertFalse(isset($_COOKIE['foo']));

        // call cache clear
        $this->app->clear('CACHE');
    }

    public function testSets()
    {
        $this->assertEquals('bar', $this->app->sets(['foo'=>'bar'], 'baz.')->get('baz.foo'));
    }

    public function testClears()
    {
        $this->app->sets(['foo'=>'bar','bar'=>'foo']);
        $this->app->clears(['foo','bar']);

        $this->assertFalse($this->app->exists('foo'));
        $this->assertFalse($this->app->exists('bar'));
    }

    public function testFlash()
    {
        $this->assertEquals('bar', $this->app->set('foo','bar')->flash('foo'));
        $this->assertNull($this->app['foo']);
    }

    public function testCopy()
    {
        $this->assertEquals('bar', $this->app->set('foo', 'bar')->copy('foo', 'bar')->get('bar'));
    }

    public function testConcat()
    {
        $this->assertEquals('barbaz', $this->app->set('foo', 'bar')->concat('foo', 'baz'));
        $this->assertEquals('barbaz', $this->app->concat('foo', 'baz', '', true)->get('foo'));
    }

    public function testFlip()
    {
        $this->assertEquals(['baz'=>'bar'], $this->app->set('foo', ['bar'=>'baz'])->flip('foo'));
        $this->assertEquals(['baz'=>'bar'], $this->app->flip('foo', true)->get('foo'));
    }

    public function testPush()
    {
        $this->assertEquals(['bar'], $this->app->set('foo', [])->push('foo', 'bar')->get('foo'));
    }

    public function testPop()
    {
        $this->assertEquals('bar', $this->app->set('foo', ['bar'])->pop('foo'));
    }

    public function testUnshift()
    {
        $this->assertEquals(['bar'], $this->app->set('foo', [])->unshift('foo', 'bar')->get('foo'));
    }

    public function testShift()
    {
        $this->assertEquals('bar', $this->app->set('foo', ['bar'])->shift('foo'));
    }

    public function testMerge()
    {
        $this->assertEquals(['foo','bar'], $this->app->set('foo', ['foo'])->merge('foo', ['bar']));
        $this->assertEquals(['foo','bar'], $this->app->merge('foo', ['bar'], true)->get('foo'));
        $this->assertEquals(['bar'], $this->app->merge('bar', ['bar'], true)->get('bar'));
    }

    public function testExtend()
    {
        $this->assertEquals(['foo'=>'bar'], $this->app->set('foo', [])->extend('foo', ['foo'=>'bar']));
        $this->assertEquals(['foo'=>'bar'], $this->app->extend('foo', ['foo'=>'bar'], true)->get('foo'));
    }

    public function testOffsetget()
    {
        $this->app['foo'] = 'bar';
        $this->assertEquals('bar', $this->app['foo']);

        $this->app['ctr'] = 0;
        $this->app['ctr']++;
        $this->assertEquals(1, $this->app['ctr']);
    }

    public function testOffsetset()
    {
        $this->app['foo'] = 'bar';
        $this->assertEquals('bar', $this->app['foo']);
    }

    public function testOffsetexists()
    {
        $this->assertFalse(isset($this->app['foo']));
    }

    public function testOffsetunset()
    {
        unset($this->app['foo']);
        $this->assertFalse(isset($this->app['foo']));
    }

    public function testMagicget()
    {
        $this->app->foo = 'bar';
        $this->assertEquals('bar', $this->app->foo);
    }

    public function testMagicset()
    {
        $this->app->foo = 'bar';
        $this->assertEquals('bar', $this->app->foo);
    }

    public function testMagicexists()
    {
        $this->assertFalse(isset($this->app->foo));
    }

    public function testMagicunset()
    {
        unset($this->app->foo);
        $this->assertFalse(isset($this->app->foo));
    }

    public function autoloadProvider()
    {
        return [
            [LoadA::class, true],
            [LoadB::class, true],
            // doesn't exists
            [LoadC::class, false],
        ];
    }

    /** @dataProvider autoloadProvider */
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

    public function testTrigger()
    {
        $this->assertFalse($this->app->trigger('foo'));

        $handler = function(App $app) {
            $app['event foo is null'] = true;
        };
        $this->app['EVENT.foo'] = $handler;
        $this->assertTrue($this->app->trigger('foo'));
        $this->assertTrue($this->app['event foo is null']);
        $this->assertEquals($handler, $this->app['EVENT.foo']);
    }
}
