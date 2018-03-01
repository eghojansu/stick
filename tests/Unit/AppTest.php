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

    public function setUp()
    {
        $_SERVER['argc']           = 1;
        $_SERVER['argv']           = [$_SERVER['argv'][0]];
        $_SERVER['CONTENT_LENGTH'] = 0;
        $_SERVER['CONTENT_TYPE']   = 'text/html';

        $this->app = new App();
        $this->app->sets([
            'TEMP'      => TEMP,
            'LOG_ERROR' => FALSE,
            'HALT'      => FALSE,
        ]);
    }

    public function tearDown()
    {
        header_remove();
        $this->app->cacheReset();

        if (file_exists($dir = TEMP . 'cache')) {
            rmdir($dir);
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
            ->route('GET custom /custom/{custom}', ControllerClass::class . '->{custom}')
            ->route('GET invalidclass /invalidclass', 'InvalidClass->invalid')
            ->route('GET invalidmethod /invalidmethod', ControllerClass::class .'->invalid')
            ->route('GET invalidfunction /invalidfunction', 'invalidfunction')
            ->route('GET emptycallback /emptycallback', NULL)
            ->route('GET /cookie', function(App $app) {
                $app->set('COOKIE.foo', 'bar');

                echo 'cookie';
            })
        ;
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

    public function testAgent()
    {
        $this->app['HEADERS.User-Agent'] = 'User-Agent';
        $this->assertEquals('User-Agent', $this->app->agent());
        unset($this->app['HEADERS.User-Agent']);

        $this->app['HEADERS.X-Operamini-Phone-Ua'] = 'X-Operamini-Phone-Ua';
        $this->assertEquals('X-Operamini-Phone-Ua', $this->app->agent());
        unset($this->app['HEADERS.X-Operamini-Phone-Ua']);

        $this->app['HEADERS.X-Skyfire-Phone'] = 'X-Skyfire-Phone';
        $this->assertEquals('X-Skyfire-Phone', $this->app->agent());
    }

    public function testAjax()
    {
        $this->assertFalse($this->app->ajax());
        $this->app['HEADERS.X-Requested-With'] = 'xmlhttprequest';
        $this->assertTrue($this->app->ajax());
    }

    public function testIp()
    {
        $this->app['HEADERS.Client-Ip'] = 'Client-Ip';
        $this->assertEquals('Client-Ip', $this->app->ip());
        unset($this->app['HEADERS.Client-Ip']);

        $this->app['HEADERS.X-Forwarded-For'] = 'X-Forwarded-For';
        $this->assertEquals('X-Forwarded-For', $this->app->ip());
        unset($this->app['HEADERS.X-Forwarded-For']);

        $this->app['SERVER.REMOTE_ADDR'] = 'REMOTE_ADDR';
        $this->assertEquals('REMOTE_ADDR', $this->app->ip());
    }

    public function testStatus()
    {
        $this->assertEquals('Not Found', $this->app->status(404)->get('TEXT'));
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
        $this->app->header('Location', '/foo');
        $this->assertEquals(['/foo'], $this->app->getHeader('Location'));
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

    public function testSendHeader()
    {
        $this->app['CLI'] = FALSE;
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

    public function testOut()
    {
        $this->expectOutputString('throttle');

        $start = microtime(TRUE);
        $this->app->out('throttle', 1);
        $end = microtime(TRUE) - $start;

        $this->assertGreaterThan(1, $end);
    }

    public function testOutSilent()
    {
        $this->expectOutputString('');

        $this->app->set('QUIET', TRUE)->out('throttle', 0);
    }

    public function testCache()
    {
        $this->app->set('CACHE', 'folder=' . TEMP . 'cache/');

        $this->expectOutputString('cache');

        $this->app->cache(function() {
            echo 'cache';
        }, 5);

        $cache = TEMP . 'cache/' . $this->app['SEED'] . '.' . f\hash($this->app['METHOD'] . ' ' . $this->app['URI']) . '.url';
        $this->assertFileExists($cache);
    }

    public function testCachePageNotInVerb()
    {
        $this->app->set('CACHE', 'folder=' . TEMP . 'cache/');
        $this->app['METHOD'] = 'POST';

        $this->expectOutputString('executed');

        $this->app->cache(function() {
            echo 'executed';
        }, 5);
    }

    public function testCachePageExists()
    {
        $this->app->set('CACHE', 'folder=' . TEMP . 'cache/');

        $hash = f\hash($this->app['METHOD'] . ' ' . $this->app['URI']) . '.url';

        $this->app->cacheSet($hash, [
            [],
            'cached'
        ], 5);

        $this->expectOutputString('cached');

        $this->app->cache(function() {
            echo 'not executed';
        }, 5);
    }

    public function testCachePageExistsWithHeaderCheck()
    {
        $this->app->set('CACHE', 'folder=' . TEMP . 'cache/');
        $this->app->set('HEADERS.If-Modified-Since', gmdate('r', strtotime('+1 minute')));

        $hash = f\hash($this->app['METHOD'] . ' ' . $this->app['URI']) . '.url';

        $this->app->cacheSet($hash, [
            [],
            'cached'
        ], 5);

        $this->expectOutputString('');

        $this->app->cache(function() {
            echo 'not executed';
        }, 5);

        $this->assertEquals('Not Modified', $this->app['TEXT']);
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
            ['GET invalidfunction', 'regex/Not Found/', ['TEXT'=>'Not Found','PATH'=>'/invalidfunction']],

            // invalid callback
            ['GET emptycallback', 'regex/Internal Server Error/', ['TEXT'=>'Internal Server Error','PATH'=>'/emptycallback']],

            // pure not found
            ['GET /none', 'regex/Not Found/', ['TEXT'=>'Not Found','ERROR.status'=>'Not Found','PATH'=>'/none']],

            // with cookie send
            ['GET /cookie', 'cookie', ['RHEADERS.Set-Cookie'=>['foo=bar; Path=/; HttpOnly']]],
        ];
    }

    /** @dataProvider runProvider */
    public function testRun($args, $output, array $hive = NULL)
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

    public function testRunCors()
    {
        $this->registerRoutes();

        $this->app->merge('CORS', [
            'origin' => 'example.com',
            'expose' => 'example.com',
            'headers' => 'example.com',
            'ttl' => 10,
        ], TRUE);

        $this->expectOutputString('foo');

        $this->app->mock('GET /foo', NULL, ['Origin'=>'example.com']);
    }

    public function testRunCors403()
    {
        $this->registerRoutes();

        $this->app->merge('CORS', [
            'origin' => 'example.com',
            'expose' => 'example.com',
            'headers' => 'example.com',
            'ttl' => 10,
        ], TRUE);

        $this->expectOutputRegex('/Method Not Allowed/');

        // 403
        $this->app->mock('POST /foo', NULL, ['Origin'=>'example.com']);

        $this->assertEquals('Method Not Allowed', $this->app['TEXT']);
    }

    public function testMock()
    {
        // Covered in testRun
        $this->assertTrue(TRUE);
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
        // in cli mode
        $this->registerRoutes();

        $this->expectOutputString('cli foo');

        $this->app->reroute('/cli', FALSE, FALSE);

        $this->assertEquals('/cli', $this->app['PATH']);
    }

    public function testRerouteWithHandler()
    {
        $this->registerRoutes();

        $this->app->set('ONREROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });

        $this->expectOutputString('');

        $this->app->reroute('foo', FALSE, FALSE);

        $this->assertEquals(['/foo', FALSE], $this->app->get('reroute'));
    }

    public function testRerouteWithHeader()
    {
        $this->registerRoutes();

        // only if xdebug extension enabled
        $this->assertTrue(TRUE);

        if (function_exists('xdebug_get_headers')) {
            $this->app['CLI'] = FALSE;

            $realm = $this->app['SCHEME'] . '://' . $this->app['HOST'];

            ob_start();

            $this->app->reroute(NULL, FALSE, FALSE);
            $this->assertContains("Location: $realm/", xdebug_get_headers());

            $this->app->reroute(['qux', ['quux'=>'corge']], FALSE, FALSE);
            $this->assertContains("Location: $realm/qux/corge", xdebug_get_headers());

            $this->app->reroute('quux(corge=grault,grault=garply)', FALSE, FALSE);
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
     * @expectedException LogicException
     * @expectedExceptionMessage Route was not exists: foo
     */
    public function testAliasException()
    {
        $this->app->alias('foo');
    }

    public function testUnload()
    {
        // skipped
        $this->assertTrue(TRUE);
    }

    public function testError()
    {
        $this->app['QUIET'] = TRUE;
        $this->app['QUERY'] = 'foo=bar';

        $req = $this->app['METHOD'] . ' ' . $this->app['PATH'] . '?foo=bar';

        $this->app->error(404);
        $error = $this->app->get('ERROR');

        $this->assertContains('Not Found', $error);
        $this->assertContains(404, $error);
        $this->assertContains("HTTP 404 ($req)", $error);

        $this->app->error(500);

        $error = $this->app->get('ERROR');
        $this->assertContains(404, $error);

        $this->app->clear('ERROR');
        $this->app->set('ONERROR', function(App $app) {
            $app->set('foo_error.bar', 'baz');
        });

        $this->app->error(404);
        $this->assertEquals(['bar'=>'baz'], $this->app->get('foo_error'));
    }

    public function testErrorOutputAjax()
    {
        $this->app['AJAX'] = TRUE;

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

    public function testHalt()
    {
        // skipped
        $this->assertTrue(TRUE);
    }

    public function testExpire()
    {
        $this->app['CLI'] = FALSE;

        $this->assertEquals($this->app, $this->app->expire(0));

        $this->app->expire(-1);
        $this->assertEquals([], $this->app->getHeader('Pragma'));

        $this->app['METHOD'] = 'POST';
        $this->app->expire(-1);
        $this->assertEquals('no-cache', $this->app->getHeader('Pragma')[0]);
    }

    public function testBlacklisted()
    {
        $this->assertFalse($this->app->blacklisted('0.0.0.0'));

        // this domain is blacklisted on below host (require internet connection)
        $blacklist = '202.67.46.23';
        $this->app->set('DNSBL', ['dnsbl.justspam.org']);
        $this->assertTrue($this->app->blacklisted($blacklist));
    }

    public function testResources()
    {
        $class = ResourceClass::class;
        $this->app->resources(['foo', 'bar'], $class, ['index','store']);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 'foo_index'],
                    'POST' => ["{$class}->store", 'foo_store'],
                ],
            ],
            '/bar' => [
                0 => [
                    'GET' => ["{$class}->index", 'bar_index'],
                    'POST' => ["{$class}->store", 'bar_store'],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app->get('ROUTES'));
    }

    public function testResource()
    {
        $class = ResourceClass::class;
        $this->app->resource('foo', $class);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 'foo_index'],
                    'POST' => ["{$class}->store", 'foo_store'],
                ],
            ],
            '/foo/create' => [
                0 => [
                    'GET' => ["{$class}->create", 'foo_create'],
                ],
            ],
            '/foo/{foo}' => [
                0 => [
                    'GET' => ["{$class}->show", 'foo_show'],
                    'PUT' => ["{$class}->update", 'foo_update'],
                    'DELETE' => ["{$class}->destroy", 'foo_destroy'],
                ],
            ],
            '/foo/{foo}/edit' => [
                0 => [
                    'GET' => ["{$class}->edit", 'foo_edit'],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->app->get('ROUTES'));

        $this->app->clear('ROUTES');

        $this->app->resource('foo', $class, ['index','store']);

        $expected = [
            '/foo' => [
                0 => [
                    'GET' => ["{$class}->index", 'foo_index'],
                    'POST' => ["{$class}->store", 'foo_store'],
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

    public function testMaps()
    {
        $this->app->maps(['/', '/home'], ControllerClass::class);

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(2, count($routes));
    }

    public function testMap()
    {
        $this->app->map('/', ControllerClass::class);

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(1, count($routes));
    }

    public function testRedirects()
    {
        $this->app->redirects(['GET /foo', 'GET /bar'], '/baz');

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(2, count($routes));
    }

    public function testRedirect()
    {
        $this->app->redirect('GET /foo', '/bar');
        $this->app->set('ONREROUTE', function(App $app, $url, $permanent) {
            $app->set('reroute', [$url, $permanent]);
        });

        $routes = $this->app->get('ROUTES');

        $this->assertEquals(1, count($routes));

        $this->app->mock('GET /foo');
        $this->assertEquals(['/bar', TRUE], $this->app->get('reroute'));
    }

    public function testRoutes()
    {
        $this->app->routes(['GET /', 'GET dashboard /dashboard'], function() {});

        $routes = $this->app->get('ROUTES');

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
                    'GET' => [$handler, NULL],
                ],
            ],
            '/home' => [
                0 => [
                    'GET' => [$handler, 'home'],
                ],
            ],
            '/user' => [
                2 => [
                    'GET' => [$handler, 'user'],
                    'POST' => [$handler, 'user'],
                ],
            ],
            '/profile' => [
                1 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/command' => [
                4 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/product/{keyword}' => [
                0 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/product/{id:digit}' => [
                0 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/category/{category:word}' => [
                0 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/post/{post:custom}' => [
                0 => [
                    'GET' => [$handler, NULL],
                ],
            ],
            '/regex/(?<regex>[[:alpha:]])' => [
                0 => [
                    'GET' => [$handler, NULL],
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

    public function testSerialize()
    {
        $this->app['SERIALIZER'] = 'php';
        $arg = ['foo'=>'bar'];
        $expected = serialize($arg);
        $result = $this->app->serialize($arg);
        $this->assertEquals($expected, $result);

        if (extension_loaded('igbinary')) {
            $expected = igbinary_serialize($arg);
            $this->app['SERIALIZER'] = 'igbinary';
            $result = $this->app->serialize($arg);

            $this->assertEquals($expected, $result);
        }
    }

    public function testUnserialize()
    {
        $this->app['SERIALIZER'] = 'php';
        $expected = ['foo'=>'bar'];
        $arg = serialize($expected);
        $result = $this->app->unserialize($arg);
        $this->assertEquals($expected, $result);

        if (extension_loaded('igbinary')) {
            $arg = igbinary_serialize($expected);
            $this->app['SERIALIZER'] = 'igbinary';
            $result = $this->app->unserialize($arg);

            $this->assertEquals($expected, $result);
        }
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
    }

    public function testHive()
    {
        $this->app->set('foo', 'foo');
        $this->assertContains('foo', $this->app->hive());
    }

    public function testRef()
    {
        $foo = $this->app->ref('foo');
        $this->assertNull($foo);

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
        $this->assertEquals('bar', $this->app->set('POST.foo', 'bar')->get('POST.foo'));
        $this->assertEquals('bar', $this->app->get('REQUEST.foo'));

        // update timezone
        $this->app->set('TZ', 'Asia/Jakarta');
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());

        // URI
        $this->app->set('URI', '/foo');
        $this->assertEquals('/foo', $_SERVER['REQUEST_URI']);

        // cache
        $this->assertEquals('auto', $this->app->set('CACHE', 'auto')->get('CACHE'));

        // JAR
        $this->assertEquals('foo.com', $this->app->set('JAR.domain', 'foo.com')->get('JAR.domain'));
        $this->assertEquals(TRUE, $this->app->set('JAR.secure', TRUE)->get('JAR.secure'));
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
        $this->assertEquals('baz', $this->app->get('foo.bar'));
        $this->app->clear('foo.bar');
        $this->assertFalse($this->app->exists('foo.bar'));

        // reset
        $init = $this->app['URI'];

        // change
        $this->app['URI'] = '/foo';
        $this->assertNotEquals($init, $this->app['URI']);

        unset($this->app['URI']);
        $this->assertEquals($init, $this->app['URI']);

        // CACHE
        $this->assertEquals($this->app, $this->app->clear('CACHE'));

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
        $this->app->set('SERVICE.foo', CommonClass::class);
        $this->assertEquals(['class'=>CommonClass::class, 'keep'=>TRUE], $this->app->get('SERVICE.foo'));
        $this->app->clear('SERVICE.foo');
        $this->assertNull($this->app->get('SERVICE.foo'));

        // COOKIE
        $this->app->set('COOKIE.foo', 'bar');
        $this->assertContains('foo=bar', $this->app->getHeader('Set-Cookie')[0]);
        $this->assertTrue(isset($_COOKIE['foo']));
        $this->app->clear('COOKIE.foo');
        $this->assertContains('foo=bar', $this->app->getHeader('Set-Cookie')[0]);
        $this->assertFalse(isset($_COOKIE['foo']));
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

    public function testCopy()
    {
        $this->assertEquals('bar', $this->app->set('foo', 'bar')->copy('foo', 'bar')->get('bar'));
    }

    public function testConcat()
    {
        $this->assertEquals('barbaz', $this->app->set('foo', 'bar')->concat('foo', 'baz'));
        $this->assertEquals('barbaz', $this->app->concat('foo', 'baz', '', TRUE)->get('foo'));
    }

    public function testFlip()
    {
        $this->assertEquals(['baz'=>'bar'], $this->app->set('foo', ['bar'=>'baz'])->flip('foo'));
        $this->assertEquals(['baz'=>'bar'], $this->app->flip('foo', TRUE)->get('foo'));
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
        $this->assertEquals(['foo','bar'], $this->app->merge('foo', ['bar'], TRUE)->get('foo'));
        $this->assertEquals(['bar'], $this->app->merge('bar', ['bar'], TRUE)->get('bar'));
    }

    public function testExtend()
    {
        $this->assertEquals(['foo'=>'bar'], $this->app->set('foo', [])->extend('foo', ['foo'=>'bar']));
        $this->assertEquals(['foo'=>'bar'], $this->app->extend('foo', ['foo'=>'bar'], TRUE)->get('foo'));
    }

    public function cacheProvider()
    {
        $provider = [
            [''],
            ['folder='.TEMP.'file_cache/'],
            ['fallback'],
        ];

        if (extension_loaded('apc')) {
            $provider[] = ['apc'];
            $provider[] = ['apcu'];
            $provider[] = ['auto'];
        }

        if (extension_loaded('memcached')) {
            $provider[] = ['memcached=127.0.0.1'];
        }

        if (extension_loaded('redis')) {
            $provider[] = ['redis=127.0.0.1'];
        }

        if (extension_loaded('wincache')) {
            $provider[] = ['wincache'];
        }

        if (extension_loaded('xcache')) {
            $provider[] = ['xcache'];
        }

        return $provider;
    }

    /** @dataProvider cacheProvider */
    public function testCacheExists($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $key = 'foo';
        $this->assertFalse($this->app->cacheExists($key));

        if ($dsn) {
            $this->assertTrue($this->app->cacheSet($key, $key)->cacheExists($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testCacheGet($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $key = 'foo';
        $this->assertEquals([], $this->app->cacheGet($key));

        if ($dsn) {
            $this->assertContains($key, $this->app->cacheSet($key, $key)->cacheGet($key));

            $this->app->clear($key);
            $this->app->cacheSet($key, $key, 1);
            // onesecond
            usleep(1000000);
            $this->assertEquals([], $this->app->cacheGet($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testCacheSet($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $key = 'foo';
        $value = 'bar';
        $this->assertEquals($this->app, $this->app->cacheSet($key, $value));

        if ($dsn) {
            $this->assertContains($value, $this->app->cacheGet($key));

            $this->assertContains($key, $this->app->cacheSet($key, $key)->cacheGet($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testCacheClear($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $key = 'foo';
        $this->assertFalse($this->app->cacheClear($key));

        if ($dsn) {
            $this->assertTrue($this->app->cacheSet($key, $key)->cacheClear($key));
        }
    }

    /** @dataProvider cacheProvider */
    public function testCacheReset($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $key = 'foo';
        $this->assertTrue($this->app->cacheReset());
        $this->assertTrue($this->app->cacheSet($key, $key)->cacheReset());
    }

    public function testCacheDef()
    {
        define('me','bar');
        $this->assertEquals([NULL, NULL], $this->app->cacheDef());
    }

    public function testCacheRedis()
    {
        $this->app->set('CACHE', 'redis=invalid-host');
        // fallback to folder
        $this->assertEquals(['folder', TEMP . 'cache/'], $this->app->cacheDef());
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

    public function testConfig()
    {
        $this->app->config(FIXTURE . 'config.ini');

        $this->assertEquals('bar', $this->app['foo']);
        $this->assertEquals(['baz1','baz2'], $this->app['baz']);
        $this->assertEquals(['one'=>1,'two'=>TRUE,'three'=>FALSE,'four'=>NULL], $this->app['qux']);

        $this->assertEquals('bar', $this->app['section.foo']);
        $this->assertEquals('baz', $this->app['sec.foo.bar']);

        $this->assertEquals('foo', $this->app['glob']);

        $routes = $this->app['ROUTES'];

        $this->assertTrue(isset($routes['/route']));
        $this->assertTrue(isset($routes['/map']));
        $this->assertTrue(isset($routes['/redirect']));
        $this->assertTrue(isset($routes['/resource']));

        // mock registered route
        $this->app->mock('GET /route');
        $this->assertEquals('foo', $this->app['custom']);
    }
}
