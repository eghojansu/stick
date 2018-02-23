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

use DateTime;
use Fal\Stick as f;
use Fal\Stick\App;
use Fal\Stick\Test\fixture\CommonClass;
use Fal\Stick\Test\fixture\ControllerClass;
use Fal\Stick\Test\fixture\DepA;
use Fal\Stick\Test\fixture\DepDateTime;
use Fal\Stick\Test\fixture\DepDepAIndB;
use Fal\Stick\Test\fixture\IndA;
use Fal\Stick\Test\fixture\IndB;
use Fal\Stick\Test\fixture\ResourceClass;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    private $app;
    private $init;

    public function setUp()
    {
        $_SERVER['argc'] = 1;
        $_SERVER['argv'] = [$_SERVER['argv'][0]];

        $this->app = new App();
        $this->app->sets([
            'TEMP'      => TEMP,
            'LOG_ERROR' => false,
            'HALT'      => false,
            'QUIET'     => true,
            'CACHE'     => 'folder',
        ]);
        $this->init = [
            '_GET'     => $_GET,
            '_POST'    => $_POST,
            '_COOKIE'  => [],
            '_FILES'   => $_FILES,
            '_ENV'     => $_ENV,
            '_REQUEST' => $_REQUEST,
            '_SERVER'  => $_SERVER,
        ];
    }

    public function tearDown()
    {
        $cache = $this->app->service('cache');
        $cache->reset();
        if (file_exists($dir = TEMP . 'cache')) {
            rmdir($dir);
        }

        foreach ($this->init as $key => $value) {
            $GLOBALS[$key] = $value;
        }
        header_remove();
        $this->app->service('response')->removeHeader();
    }

    public function testDispatch()
    {
        $this->app->set('EVENT.foo', function(App $app, $baz) {
            $app->set('foo', 'bar'.$baz);
        });
        $this->app->dispatch('foo', ['baz'=>'qux']);
        $this->assertEquals('barqux', $this->app->get('foo'));
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->app->call('trim', ' foo '));
        $this->assertEquals('foobar', $this->app->call(CommonClass::class.'->prefixFoo', 'bar'));
        $this->assertEquals('quxquux', $this->app->call(CommonClass::class.'::prefixQux', 'quux'));
        $this->assertEquals('foobarbaz', $this->app->call(function(...$args) {
            return implode('', $args);
        }, ['foo','bar','baz']));
    }

    public function testService()
    {
        $this->assertEquals($this->app, $this->app->service('app'));
        $this->assertEquals($this->app, $this->app->service(App::class));

        $inda = $this->app->service(IndA::class);
        $this->assertInstanceof(IndA::class, $inda);

        // service
        $this->app->set('SERVICE.foo', DepA::class);
        $foo = $this->app->service('foo');
        $this->assertInstanceof(DepA::class, $foo);

        // service with placeholder parameter
        $this->app->set('SERVICE.bar', [
            'class' => DepDepAIndB::class,
            'params' => [
                'depa' => '%foo%',
                'indb' => IndB::class,
            ],
        ]);
        $bar = $this->app->service('bar');
        $this->assertInstanceof(DepDepAIndB::class, $bar);
        $this->assertEquals($foo, $bar->depa);

        // Service with global class dependency
        $depdt = $this->app->service(DepDateTime::class);
        $this->assertInstanceof(DepDateTime::class, $depdt);

        $dt = $this->app->service(DateTime::class);
        $this->assertInstanceof(DateTime::class, $dt);
    }

    public function testHive()
    {
        $this->app->set('foo', 'foo');
        $this->assertContains('foo', $this->app->hive());
    }

    public function testRef()
    {
        $foo =& $this->app->ref('foo');
        $foo = 'bar';
        $this->assertEquals('bar', $this->app->get('foo'));

        $bar =& $this->app->ref('bar');
        $bar = new \StdClass;
        $bar->foo = 'baz';
        $this->assertEquals('baz', $this->app->get('bar.foo'));
        $this->assertNull($this->app->get('bar.baz'));
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

        // update SEED
        $this->assertEquals('foo', $this->app->set('SEED', 'foo')->get('SEED'));

        // update SERIALIZER
        $this->assertEquals('foo', $this->app->set('SERIALIZER', 'foo')->get('SERIALIZER'));

        // update timezone
        $this->assertEquals('Asia/Jakarta', $this->app->set('TZ', 'Asia/Jakarta')->get('TZ'));

        // service set
        $this->app->set('SERVICE.foo', CommonClass::class);
        $this->assertEquals(['class'=>CommonClass::class,'keep'=>true], $this->app->get('SERVICE.foo'));
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
        $this->assertEquals('baz', $this->app->get('foo.bar'));
        $this->app->clear('foo.bar');
        $this->assertFalse($this->app->exists('foo.bar'));

        // Remove service
        $this->app->set('SERVICE.foo', CommonClass::class);
        $foo = $this->app->service('foo');

        $this->assertInstanceof(CommonClass::class, $foo);
        $this->app->clear('SERVICE.foo');
        $this->assertNull($this->app->get('SERVICE.foo'));
    }

    public function testSets()
    {
        $this->assertEquals('bar', $this->app->sets(['foo'=>'bar'], 'baz.')->get('baz.foo'));
    }

    public function testOffsetget()
    {
        $this->app['foo'] = 'bar';
        $this->assertEquals('bar', $this->app['foo']);
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

    public function testAccessCache()
    {
        $this->app->set('CACHE', 'folder');
        $cache = $this->app->service('cache');
        $cache->set('foo', 'bar');
        $this->assertFileExists(TEMP.'cache/'.$this->app->SEED.'.foo');

        $cached = $cache->get('foo');
        $this->assertNotEmpty($cached);
        $this->assertEquals('bar', $cached[0]);

        $this->app->clear('CACHE');
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

        $this->assertEquals($expected, $this->app->get('ROUTES'));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid route pattern: GET
     */
    public function testRouteInvalid()
    {
        $this->app->route('GET', function() {});
    }

    public function testRoutes()
    {
        $this->app->routes(['GET /', 'GET dashboard /dashboard'], function() {});

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(2, count($routes));
    }

    public function testMap()
    {
        $this->app->map('/', ControllerClass::class);

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(1, count($routes));
    }

    public function testMaps()
    {
        $this->app->maps(['/', '/home'], ControllerClass::class);

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(2, count($routes));
    }

    public function testRedirect()
    {
        $this->app->redirect('GET /foo', '/bar');
        $this->app->set('EVENT.REROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(1, count($routes));

        $this->app->mock('GET /foo');
        $this->assertEquals(['/bar',true], $this->app->get('reroute'));
    }

    public function testRedirects()
    {
        $this->app->redirects(['GET /foo', 'GET /bar'], '/baz');

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(2, count($routes));
    }

    public function testResource()
    {
        $class = ResourceClass::class;
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

        $this->assertEquals($expected, $this->app->get('ROUTES'));

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

        $this->assertEquals($expected, $this->app->get('ROUTES'));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid resource pattern:
     */
    public function testResourceException()
    {
        $this->app->resource('', ResourceClass::class);
    }

    public function testResources()
    {
        $class = ResourceClass::class;
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

        $this->assertEquals($expected, $this->app->get('ROUTES'));
    }

    public function testRun()
    {
        $this->app->route('GET / cli', function(App $app) {
            $app->set('foo', ['bar','cli']);
        });

        $this->assertEquals($this->app, $this->app->run());
        $this->assertEquals(['bar','cli'], $this->app->get('foo'));
        $response = $this->app->service('response');
        $this->assertEquals('', $response->getBody());
        $this->assertNull($response->getOutput());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No route specified
     */
    public function testRunException()
    {
        $this->app->run();
    }

    public function testMock()
    {
        $this->app->route('GET foo /bar', function(App $app) {
            $app->set('foo', 'bar');
        });
        $this->app->route('POST /baz', function(App $app) {
            $app->set('bar', 'baz');
        });
        $this->app->route('GET /arg/{arg}', function(App $app, $arg) {
            $app->set('arg', $arg);
        });
        $this->app->route('GET sync /sync sync', function(App $app) {
            $app->set('sync', 'sync');
        });
        $this->app->route('GET custom /custom/{custom}', ControllerClass::class . '->{custom}');
        $this->app->route('GET invalidclass /invalidclass', 'InvalidClass->invalid');
        $this->app->route('GET invalidmethod /invalidmethod', ControllerClass::class .'->invalid');
        $this->app->route('GET invalidfunction /invalidfunction', 'invalidfunction');
        $this->app->route('GET emptycallback /emptycallback', null);

        $request = $this->app->service('request');

        // valid alias with fragment
        $this->app->mock('GET foo#fragmentbar', ['bar'=>'baz']);
        $this->assertEquals('/bar', $request['PATH']);
        $this->assertEquals('bar=baz', $request['QUERY']);
        $this->assertEquals('fragmentbar', $request['FRAGMENT']);
        $this->assertEquals('bar', $this->app->get('foo'));

        // valid url with fragment
        $this->app->mock('POST /baz#fragmentbaz', ['bar'=>'baz'], ['Content-Type'=>'text/html'], 'foo=bar');
        $this->assertEquals('/baz', $request['PATH']);
        $this->assertEquals('foo=bar', $request['BODY']);
        $this->assertEquals('fragmentbaz', $request['FRAGMENT']);
        $this->assertEquals('baz', $this->app->get('bar'));

        // valid url
        $this->app->mock('GET /arg/foo');
        $this->assertEquals('foo', $this->app->get('arg'));

        // valid alias with parameter
        $this->app->mock('GET custom(custom=custom)');
        $this->assertEquals('foo', $this->app->get('custom'));

        // valid alias with parameter
        $this->app->mock('GET custom(custom=custompair)');
        $this->assertEquals('foo', $this->app->get('custompair'));

        // valid sync
        $this->app->mock('GET sync sync');
        $this->assertEquals('sync', $this->app->get('sync'));

        // extra trailing slash
        $this->app->clear('foo');
        $this->app->mock('GET /bar/');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->app->clear('ERROR');

        // invalid request method
        $this->app->mock('POST foo');
        $this->assertEquals('Method Not Allowed', $this->app->get('ERROR.status'));
        $this->assertEquals('/bar', $request['PATH']);
        $this->app->clear('ERROR');

        // invalid mode
        $this->app->mock('GET sync ajax');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->assertEquals('/sync', $request['PATH']);
        $this->app->clear('ERROR');

        // invalid class (not exists)
        $this->app->mock('GET invalidclass');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->assertEquals('/invalidclass', $request['PATH']);
        $this->app->clear('ERROR');

        // invalid class method
        $this->app->mock('GET invalidmethod');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->assertEquals('/invalidmethod', $request['PATH']);
        $this->app->clear('ERROR');

        // invalid function
        $this->app->mock('GET invalidfunction');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->assertEquals('/invalidfunction', $request['PATH']);
        $this->app->clear('ERROR');

        // invalid callback
        $this->app->mock('GET emptycallback');
        $this->assertEquals('Internal Server Error', $this->app->get('ERROR.status'));
        $this->assertEquals('/emptycallback', $request['PATH']);
        $this->app->clear('ERROR');

        // pure not found
        $this->app->mock('GET /none');
        $this->assertEquals('Not Found', $this->app->get('ERROR.status'));
        $this->assertEquals('/none', $request['PATH']);
        $this->app->clear('ERROR');
    }

    public function testMockCors()
    {
        $response = $this->app->service('response');

        $this->app->sets([
            'origin' => 'example.com',
            'expose' => 'example.com',
            'headers' => 'example.com',
            'ttl' => 10,
        ], 'CORS.');

        $this->app->route('GET foo /bar', function(App $app) {
            $app->set('foo', 'bar');
        });

        $this->app->mock('GET foo', null, ['Origin'=>'example.com']);
        $this->assertEquals('bar', $this->app->get('foo'));

        // 404
        $this->app->mock('POST foo', null, ['Origin'=>'example.com']);
        $this->assertEquals('Method Not Allowed', $this->app->get('ERROR.status'));
        $this->app->clear('ERROR');
    }

    public function testMockCache()
    {
        $this->app->route('GET /outstring', function() {
            return 'outstring';
        });
        $this->app->route('GET /cached', function() {
            return 'cached';
        }, 100);
        $this->app->route('GET /outarray', function() {
            return ['out'=>'array'];
        });
        $closure = function() {
            $closure = 'name';
        };
        $this->app->route('GET /outclosure', function() use ($closure) {
            return $closure;
        });

        $response = $this->app->service('response');
        $request = $this->app->service('request');

        $this->app->mock('GET /outstring');
        $this->assertEquals('outstring', $response->getBody());

        $this->app->mock('GET /outarray');
        $this->assertEquals('{"out":"array"}', $response->getBody());

        $this->app->mock('GET /outclosure');
        $this->assertEquals('', $response->getBody());
        $this->assertEquals($closure, $response->getOutput());

        $this->app->mock('GET /cached');
        $this->assertEquals('cached', $response->getBody());
        $cache = TEMP . 'cache/' . $this->app['SEED'] . '.' . f\hash($request['METHOD'] . ' ' . $request['URI']) . '.url';
        $this->assertFileExists($cache);

        // access cache
        $this->app->mock('GET /cached');
        $this->assertFileExists($cache);
        $this->assertEquals('cached', $response->getBody());

        // access cache with If-Modified-Since
        $this->app->mock('GET /cached', null, ['If-Modified-Since'=>gmdate('r', strtotime('+1 minute'))]);
        $this->assertFileExists($cache);
        $this->assertEquals('', $response->getBody());
        $this->assertEquals('Not Modified', $response->getStatusText());
    }

    public function testMockOutput()
    {
        $this->expectOutputString('outstring');

        $this->app->route('GET /outstring', function() {
            return 'outstring';
        });
        $this->app->set('QUIET', false);

        $this->app->mock('GET /outstring');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid mock pattern: GET
     */
    public function testMockException()
    {
        $this->app->mock('GET');
    }

    public function testReroute()
    {
        $request = $this->app->service('request');
        $realm = $request['SCHEME'] . '://' . $request['HOST'];

        $this->app->route('GET cli /cli cli', function(App $app) {
            $app->set('cli', 'foo');
        });

        $this->app->reroute('/cli', false, false);
        $this->assertEquals('/cli', $request['PATH']);

        $this->app->route('GET foo /bar', function(App $app) {
            $app->set('foo', 'bar');
        });
        $this->app->set('EVENT.REROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });
        $this->app->reroute('foo', false, false);
        $this->assertEquals(['/bar', false], $this->app->get('reroute'));
        $this->app->clear('EVENT.REROUTE');

        if (function_exists('xdebug_get_headers')) {
            $request['CLI'] = false;

            $this->app->route('GET bar /baz/{qux}', function(App $app) {
                $app->set('baz', 'qux');
            });
            $this->app->route('GET qux /quux/{corge}/{grault}', function(App $app) {
                $app->set('qux', 'quux');
            });

            $this->app->reroute(null, false, false);
            $this->assertContains("Location: $realm/", xdebug_get_headers());

            $this->app->reroute(['bar', ['qux'=>'quux']], false, false);
            $this->assertContains("Location: $realm/baz/quux", xdebug_get_headers());

            $this->app->reroute('qux(corge=grault,grault=garply)', false, false);
            $this->assertContains("Location: $realm/quux/grault/garply", xdebug_get_headers());
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
     * @expectedException LogicException
     * @expectedExceptionMessage Route does not exists: foo
     */
    public function testAliasException()
    {
        $this->app->alias('foo');
    }

    public function testBlacklisted()
    {
        $this->assertFalse($this->app->blacklisted('0.0.0.0'));
    }

    public function testExpire()
    {
        $request = $this->app->service('request');
        $response = $this->app->service('response');
        $request['CLI'] = false;

        $response->removeHeader();
        $this->assertEquals($this->app, $this->app->expire(0));


        $response->removeHeader();
        $this->app->expire(-1);
        $this->assertEmpty(preg_grep('~^Pragma~', $response->getHeaders()));

        $response->removeHeader();
        $request['METHOD'] = 'POST';
        $this->app->expire(-1);
        $this->assertContains('Pragma: no-cache', $response->getHeaders());
    }

    public function testError()
    {
        $request = $this->app->service('request');
        $request['QUERY'] = 'foo=bar';
        $req = $request['METHOD'] . ' ' . $request['PATH'] . '?foo=bar';

        $this->app->error(404);
        $error = $this->app->get('ERROR');

        $this->assertContains('Not Found', $error);
        $this->assertContains(404, $error);
        $this->assertContains("HTTP 404 ($req)", $error);

        $this->app->error(500);

        $error = $this->app->get('ERROR');
        $this->assertContains(404, $error);

        $this->app->clear('ERROR');
        $this->app->set('EVENT.ONERROR', function(App $app) {
            $app->set('foo_error.bar', 'baz');
        });

        $this->app->error(404);
        $this->assertEquals(['bar'=>'baz'], $this->app->get('foo_error'));
    }

    public function testLoadIni()
    {
        $this->app->loadIni(FIXTURE . 'config.ini');

        $this->assertEquals('bar', $this->app->get('foo'));
        $this->assertEquals(['baz1','baz2'], $this->app->get('baz'));
        $this->assertEquals(['foo'=>'bar'], $this->app->get('section'));
        $this->assertEquals(['foo'=>['bar'=>'baz']], $this->app->get('sec'));
        $this->assertEquals(['one'=>1,'two'=>true,'three'=>false,'four'=>null], $this->app->get('qux'));
    }
}
