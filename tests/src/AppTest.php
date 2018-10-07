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

namespace Fal\Stick\Test;

use Fal\Stick\App;
use Fal\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    private $app;
    private $logDir;

    public function setUp()
    {
        $this->app = new App();
    }

    public function tearDown()
    {
        if ($this->logDir) {
            foreach (glob($this->logDir.'*') as $file) {
                unlink($file);
            }
        }

        header_remove();
    }

    private function updateInitialValue($name, $value)
    {
        $ref = new \ReflectionProperty($this->app, '_init');
        $ref->setAccessible(true);
        $val = $ref->getValue($this->app);
        $val[$name] = $value;
        $ref->setValue($this->app, $val);
    }

    private function registerRoutes()
    {
        $this->app
            ->route('GET str /str', function () {
                return 'String response';
            })
            ->route('GET arr /arr', function () {
                return array('Array response');
            })
            ->route('GET call /call', function (App $app) {
                return function () use ($app) {
                    $app->set('OUTPUT', 'From callable');
                };
            })
            ->route('GET /null', function () {
                return null;
            })
            ->route('GET /obj', function (App $app) {
                return $app;
            })
            ->route('GET unlimited /unlimited/*', function (...$args) {
                return implode(', ', $args);
            })
            ->route('GET /custom/@name/(\d)', function ($name, $id) {
                return $name.' '.$id;
            })
            ->route('GET /ajax-access ajax', function () {
                return 'Access granted';
            })
            ->route('GET /cli-access cli', function () {
                return 'Access granted';
            })
            ->route('GET /sync-access sync', function () {
                return 'Access granted';
            })
            ->route('PUT /put', function () {
                return 'Put mode';
            })
            ->route('GET /uncallable/dinamic/@method', 'FakeClass->@method')
            ->route('GET /sync-only sync', 'xxxfooxxx')
            ->route('GET /uncallable', 'xxxfooxxx')
        ;
    }

    private function prepareLogTest()
    {
        $this->logDir = $dir = TEMP.'logs-test/';

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        $this->app->set('LOG', $dir);

        return $dir.'log_'.date('Y-m-d').'.log';
    }

    public function testCreate()
    {
        $app = App::create();

        $this->assertInstanceOf(App::class, $app);
        $this->assertNotSame($this->app, $app);
        $this->assertNull($app->get('POST'));
        $this->assertNull($app->get('GET'));
    }

    public function testCreateFromGlobals()
    {
        $app = App::createFromGlobals();

        $this->assertInstanceOf(App::class, $app);
        $this->assertNotSame($this->app, $app);
        $this->assertEquals($app->get('POST'), $_POST);
        $this->assertEquals($app->get('GET'), $_GET);
    }

    public function testHive()
    {
        $hive = $this->app->hive();

        $this->assertTrue($hive['CLI']);
    }

    public function testRef()
    {
        $isCli = &$this->app->ref('CLI');
        $this->assertTrue($isCli);
        $isCli = false;
        $this->assertFalse($this->app->ref('CLI'));

        $foo = $this->app->ref('foo', false);
        $this->assertNull($foo);
        $foo = 'bar';
        $this->assertNull($this->app->ref('foo', false));

        $bar = &$this->app->ref('bar.baz.qux');
        $this->assertNull($bar);
        $bar = 'quux';
        $this->assertEquals('quux', $this->app->ref('bar.baz.qux'));

        $var = array('foo' => array('bar' => 'baz'));
        $this->assertEquals('baz', $this->app->ref('foo.bar', false, $var));
        $foo2 = &$this->app->ref('foo.bar', true, $var);
        $foo2 = 'qux';
        $this->assertEquals('qux', $var['foo']['bar']);

        $session = &$this->app->ref('SESSION.foo');
        $this->assertNull($session);
        $session = 'bar';
        $this->assertEquals('bar', $this->app->ref('SESSION.foo'));
    }

    public function testUnref()
    {
        $this->assertNull($this->app->unref('CLI')->ref('CLI'));

        $var = array('foo' => array('bar' => array('baz' => 'qux')));
        $this->app->unref('foo.bar.baz', $var);
        $this->assertEquals(array(), $var['foo']['bar']);
        $init = $var;
        $this->app->unref('bar.baz', $var);
        $this->assertEquals($init, $var);
    }

    public function testExists()
    {
        $this->assertTrue($this->app->exists('CLI'));
        $this->assertFalse($this->app->exists('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->app->set('foo', 'bar')->get('foo'));
        $this->app->set('CACHE_ENGINE', 'foo');
        $this->app->set('CACHE', 'foo');
        $this->assertEmpty($this->app->get('CACHE_ENGINE'));

        $this->assertNull($this->app->get('DICT'));
        $this->app->set('LOCALES', 'foo');
        $this->assertEquals('foo', $this->app->get('LOCALES'));
        $this->assertEquals(array(), $this->app->get('DICT'));

        $this->assertEquals('UTF-8', $this->app->set('ENCODING', 'UTF-8')->get('ENCODING'));

        $this->app->set('TZ', 'Asia/Jakarta');
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());
    }

    public function testGet()
    {
        $isCli = &$this->app->get('CLI');
        $this->assertTrue($isCli);
        $isCli = false;
        $this->assertFalse($this->app->get('CLI'));

        $this->assertNull($this->app->get('foo'));
    }

    public function testClear()
    {
        $init = $this->app->get('BASE');
        $this->assertEquals($init, $this->app->clear('BASE')->get('BASE'));

        $this->app->set('foo', 'bar');
        $this->assertNull($this->app->clear('foo')->get('foo'));

        $this->app->set('COOKIE', array('foo' => 'bar'));
        $this->assertNull($this->app->clear('COOKIE.foo')->get('COOKIE.foo'));
        $this->assertEquals(array(), $this->app->clear('COOKIE')->get('COOKIE'));

        $this->app->set('SESSION', array('foo' => 'bar'));
        $this->assertNull($this->app->clear('SESSION.foo')->get('SESSION.foo'));
        $this->assertEquals(array(), $this->app->clear('SESSION')->get('SESSION'));
    }

    public function testMset()
    {
        $this->app->mset(array(
            'foo' => 'bar',
            'bar' => 'baz',
        ), 'foo_');

        $this->assertEquals('bar', $this->app->get('foo_foo'));
        $this->assertEquals('baz', $this->app->get('foo_bar'));
    }

    public function testMclear()
    {
        $this->app->set('foo', 'bar');

        $this->assertNull($this->app->mclear('foo')->get('foo'));
    }

    public function testOverrideRequestMethod()
    {
        $this->app->mset(array(
            'REQUEST.X-Http-Method-Override' => 'POST',
            'POST._method' => 'put',
        ));

        $this->assertEquals('PUT', $this->app->overrideRequestMethod()->get('VERB'));
    }

    public function testEmulateCliRequest()
    {
        $entry = $_SERVER['argv'][0];

        $this->app->mset(array(
            'SERVER.argv' => array($entry),
        ))->emulateCliRequest();
        $this->assertEquals('/', $this->app->get('PATH'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo?bar=baz&qux'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));
        $this->assertEquals('/foo?bar=baz&qux', $this->app->get('URI'));
        $this->assertEquals(array('bar' => 'baz', 'qux' => ''), $this->app->get('GET'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));
        $this->assertEquals('/foo', $this->app->get('URI'));
        $this->assertEquals(array(), $this->app->get('GET'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo?bar=baz&qux'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));
        $this->assertEquals('/foo?bar=baz&qux', $this->app->get('URI'));
        $this->assertEquals(array('bar' => 'baz', 'qux' => ''), $this->app->get('GET'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, 'foo', 'bar', '-fo', '--bar=baz'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo/bar', $this->app->get('PATH'));
        $this->assertEquals('/foo/bar?bar=baz&f=&o=', $this->app->get('URI'));
        $this->assertEquals(array('f' => '', 'o' => '', 'bar' => 'baz'), $this->app->get('GET'));
    }

    public function testOffsetExists()
    {
        $this->assertTrue($this->app->offsetExists('CLI'));
    }

    public function testOffsetSet()
    {
        $this->app->offsetSet('foo', 'bar');

        $this->assertEquals('bar', $this->app->get('foo'));
    }

    public function testOffsetGet()
    {
        $this->assertTrue($this->app->offsetGet('CLI'));

        $foo = &$this->app->offsetGet('foo');
        $foo = 'bar';
        $this->assertEquals('bar', $this->app->offsetGet('foo'));

        $this->app['bar']['baz'] = 'qux';
        $this->assertEquals('qux', $this->app->get('bar.baz'));
    }

    public function testOffsetUnset()
    {
        $this->app->mset(array(
            'foo' => 'bar',
        ));
        $this->app->offsetUnset('foo');

        $this->assertFalse($this->app->exists('foo'));
    }

    public function testMagicIsset()
    {
        $this->assertTrue(isset($this->app->CLI));
    }

    public function testMagicSet()
    {
        $this->app->foo = 'bar';

        $this->assertEquals('bar', $this->app->get('foo'));
    }

    public function testMagicGet()
    {
        $this->assertTrue($this->app->CLI);

        $foo = &$this->app->foo;
        $foo = 'bar';
        $this->assertEquals('bar', $this->app->foo);

        $this->app->bar['baz'] = 'qux';
        $this->assertEquals('qux', $this->app->get('bar.baz'));
    }

    public function testMagicUnset()
    {
        $this->app->mset(array(
            'foo' => 'bar',
        ));
        unset($this->app->foo);

        $this->assertFalse($this->app->exists('foo'));
    }

    public function testStatus()
    {
        $this->app->status(404);

        $this->assertEquals(404, $this->app->get('CODE'));
        $this->assertEquals('Not Found', $this->app->get('STATUS'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported HTTP code: 900.
     */
    public function testStatusException()
    {
        $this->app->status(900);
    }

    public function expireProvider()
    {
        return array(
            array(0, array(
                'Pragma',
                'Cache-Control',
                'Expires',
            )),
            array(1, array(
                'Cache-Control',
                'Expires',
                'Last-Modified',
            )),
        );
    }

    /**
     * @dataProvider expireProvider
     */
    public function testExpire($secs, array $headers)
    {
        $expected = array_merge(array(
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
        ), $headers);
        $actual = array_keys($this->app->expire($secs)->get('RESPONSE'));

        $this->assertEquals($expected, $actual);
    }

    public function ruleProvider()
    {
        $dateObj = new \DateTime();

        return array(
            array('foo', 'trim', array(
                'constructor' => 'trim',
                'class' => 'foo',
                'service' => true,
            )),
            array('foo', $dateObj, array(
                'class' => 'DateTime',
                'service' => true,
            )),
            array('foo', 'DateTime', array(
                'class' => 'DateTime',
                'service' => true,
            )),
            array('DateTime', array('service' => false), array(
                'service' => false,
                'class' => 'DateTime',
            )),
            array('DateTime', null, array(
                'class' => 'DateTime',
                'service' => true,
            )),
        );
    }

    /**
     * @dataProvider ruleProvider
     */
    public function testRule($id, $rule, $expected)
    {
        $this->assertEquals($expected, $this->app->rule($id, $rule)->get('SERVICE_RULES.'.$id));
    }

    public function testBaseUrl()
    {
        $this->assertEquals($this->app->get('BASEURL').'/', $this->app->baseUrl('/'));
        $this->assertEquals($this->app->get('BASEURL').'/', $this->app->baseUrl('//'));
        $this->assertEquals($this->app->get('BASEURL').'/foo', $this->app->baseUrl('foo'));
    }

    public function testSend()
    {
        $output = 'foo';
        $headers = array('Foo' => 'bar');
        $this->app->mset(array(
            'CLI' => false,
            'MIME' => 'text/plain',
            'COOKIE' => array(
                'foo' => 'bar',
            ),
        ));
        $this->updateInitialValue('COOKIE', array(
            'xfoo' => 'xbar',
        ));

        $this->expectOutputString($output);
        $this->app->send(404, $headers, $output);

        $this->assertTrue($this->app->get('SENT'));
        $this->assertEquals($output, $this->app->get('OUTPUT'));
        $this->assertEquals($headers, $this->app->get('RESPONSE'));

        if (function_exists('xdebug_get_headers')) {
            $expected = array(
                'Set-Cookie: foo=bar; HttpOnly',
                'Set-Cookie: xfoo=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; HttpOnly',
                'Foo: bar',
                'Content-type: text/plain;charset=UTF-8',
            );
            $this->assertEquals($expected, xdebug_get_headers());
        }

        $this->app->send(500);
        $this->assertEquals(404, $this->app->get('CODE'));
    }

    public function testSendContentChunked()
    {
        $this->expectOutputString('foo');
        $this->app->send(null, null, 'foo', null, 1);
    }

    public function testOn()
    {
        $this->app->on('foo', 'bar');

        $this->assertEquals(array('bar', false), $this->app->get('EVENTS.foo'));
    }

    public function testOne()
    {
        $this->app->one('foo', 'bar');

        $this->assertEquals(array('bar', true), $this->app->get('EVENTS.foo'));
    }

    public function testOff()
    {
        $this->app->one('foo', 'bar')->off('foo');

        $this->assertNull($this->app->get('EVENTS.foo'));
    }

    public function testInstance()
    {
        $this->app->rule('post', array(
            'class' => 'Fixture\\Services\\BlogPost',
            'service' => false,
        ));
        $this->app->rule('now', 'DateTime');
        $this->app->rule('Fixture\\Services\\Author', array(
            'boot' => function ($author) {
                $author->setName('Foo');
            },
        ));
        $this->app->rule('now2', array(
            'class' => 'DateTime',
            'constructor' => function () {
                return new \DateTime();
            },
            'service' => false,
        ));

        $simplePost = $this->app->instance('Fixture\\Services\\SimplePost', array(
            'title' => 'Foo',
            'postNow' => '%CLI%',
        ));
        $now = $this->app->instance('now');
        $now2 = $this->app->instance('now2');
        $post = $this->app->instance('post', array(
            'title' => 'Foo',
            'postNow' => '%CLI%',
            'postedDate' => '%now%',
            'author' => 'Fixture\\Services\\Author',
        ));
        $post2 = $this->app->instance('Fixture\\Services\\BlogPost', array(
            'Foo',
            false,
            $now,
        ));

        $this->assertInstanceOf('Fixture\\Services\\SimplePost', $simplePost);
        $this->assertSame($now, $post->getPostedDate());
        $this->assertEquals('Foo', $post->getTitle());
        $this->assertEquals('Foo', $post->getAuthor()->getName());
        $this->assertNotSame($post2, $post);
        $this->assertNotSame($now2, $now);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to create instance for "DateTimeInterface". Please provide instantiable version of DateTimeInterface.
     */
    public function testInstanceException()
    {
        $this->app->instance('DateTimeInterface');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Constructor of "now" should return instance of DateTime.
     */
    public function testInstanceException2()
    {
        $this->app->rule('now', array(
            'class' => 'DateTime',
            'constructor' => function () {
                return new \StdClass();
            },
            'service' => false,
        ));
        $this->app->instance('now');
    }

    public function testService()
    {
        $this->app->rule('author', 'Fixture\\Services\\Author');

        $this->assertSame($this->app, $this->app->service('app'));
        $this->assertSame($this->app, $this->app->service(App::class));

        $author = $this->app->service('Fixture\\Services\\Author');
        $this->assertSame($author, $this->app->service('author'));
        $this->assertSame($author, $this->app->service('Fixture\\Services\\Author'));
    }

    public function testGrab()
    {
        $this->assertEquals('foo', $this->app->grab('foo'));
        $this->assertEquals(array('Fixture\\Services\\Author', 'getName'), $this->app->grab('Fixture\\Services\\Author->getName', false));
        $this->assertEquals(array('Fixture\\Services\\Author', 'getName'), $this->app->grab('Fixture\\Services\\Author::getName'));

        $mark = time();
        $grabbed = $this->app->grab('DateTime->getTimestamp');
        $this->assertLessthan(1, $grabbed[0]->getTimestamp() - $mark);
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->app->call('trim', ' foo '));
        $this->assertEquals(' foo ', $this->app->call('trim', array(' foo ;', ';')));
        $this->assertEquals('123', $this->app->call(function ($one, ...$rest) {
            return $one.implode('', $rest);
        }, array(1, 2, 3)));
        $this->assertEquals('123', $this->app->call(function () {
            return implode('', func_get_args());
        }, array(1, 2, 3)));

        $mark = time();
        $timestamp = $this->app->call('DateTime->getTimestamp');
        $this->assertLessthan(1, $timestamp - $mark);
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Function xxxfooxxx() does not exist
     */
    public function testCallException()
    {
        $this->app->call('xxxfooxxx');
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Method DateTime::xxxfooxxx() does not exist
     */
    public function testCallException2()
    {
        $this->app->call('DateTime->xxxfooxxx');
    }

    public function testWrap()
    {
        $isCli = false;
        $this->app->wrap(function (App $app) use (&$isCli) {
            $isCli = $app->get('CLI');
        });

        $this->assertTrue($isCli);
    }

    public function testTrigger()
    {
        $this->assertNull($this->app->trigger('foo'));

        $this->app->on('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $this->app->trigger('foo', null, true));
        $this->assertNull($this->app->trigger('foo'));
    }

    public function errorProvider()
    {
        return array(
            array(404, null, 'Status : Not Found'.PHP_EOL.'Text   : HTTP 404 (GET /)'.PHP_EOL.PHP_EOL),
            array(404, array('AJAX' => true), '{"status":"Not Found","text":"HTTP 404 (GET \/)"}', 'application/json'),
            array(404, array('CLI' => false), '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="UTF-8">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>404 Not Found</title>'.
                '</head>'.
                '<body>'.
                  '<h1>Not Found</h1>'.
                  '<p>HTTP 404 (GET /)</p>'.
                '</body>'.
                '</html>', 'text/html', ),
        );
    }

    /**
     * @dataProvider errorProvider
     */
    public function testError($httpCode, $sets, $response, $header = null)
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
        ));

        if ($sets) {
            $this->app->mset($sets);
        }

        $this->app->error($httpCode);

        $this->assertEquals($response, $this->app->get('OUTPUT'));

        if ($header) {
            $this->assertEquals($header, $this->app->get('MIME'));
        }
    }

    public function testErrorHandler()
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
        ))->one('app_error', function (App $app, $message) {
            return 'foo - '.$message;
        });

        $this->app->error(404, 'bar');

        $this->assertEquals('foo - bar', $this->app->get('OUTPUT'));
    }

    public function testErrorPrior()
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
        ));

        $this->assertFalse($this->app->get('ERROR'));
        $this->app->error(404);

        $this->assertTrue($this->app->get('ERROR'));
        $this->app->error(405);

        $this->assertContains('Not Found', $this->app->get('OUTPUT'));
    }

    public function routeProvider()
    {
        return array(
            array('GET', '/foo'),
            array('GET|POST', '/foo'),
            array('GET|POST', '/foo', 'foo'),
            array('GET|POST', '/foo', 'foo', 'cli', 2 /* App::REQ_CLI */),
        );
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRoute($verbs, $path, $alias = null, $mode = null, $bitwiseMode = 0)
    {
        $expr = trim($verbs.' '.$alias.' '.$path.' '.$mode);

        $this->app->route($expr, 'foo');

        $routes = $this->app->get('ROUTES.'.$path);

        $this->assertNotEmpty($routes);
        $this->assertTrue(array_key_exists($bitwiseMode, $routes));
        $this->assertCount(count(explode('|', $verbs)), $routes[$bitwiseMode]);

        if ($alias) {
            $this->assertEquals($path, $this->app->get('ROUTE_ALIASES.'.$alias));
        }
    }

    public function testRouteAlias()
    {
        $this->app
            ->route('GET foo /foo', 'foo')
            ->route('GET foo cli', 'bar')
        ;

        $routes = $this->app->get('ROUTES./foo');
        $aliases = $this->app->get('ROUTE_ALIASES');

        $this->assertNotEmpty($routes);
        $this->assertTrue(array_key_exists(0, $routes));
        $this->assertTrue(array_key_exists(2 /* App::REQ_CLI */, $routes));
        $this->assertCount(1, $routes[0]);
        $this->assertCount(1, $routes[2]);
        $this->assertCount(1, $aliases);
        $this->assertEquals('/foo', $aliases['foo']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route should contains at least a verb and path, given "GET".
     */
    public function testRouteException()
    {
        $this->app->route('GET', 'foo');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route "foo" not exists.
     */
    public function testRouteException2()
    {
        $this->app->route('GET foo', 'foo');
    }

    public function aliasProvider()
    {
        return array(
            array('home', null, '/'),
            array('about', null, '/about'),
            array('product', array('name' => 'foo'), '/product/foo'),
            array('product', 'name=foo', '/product/foo'),
            array('everything', array('foo', 'bar', 'baz'), '/everything/foo/bar/baz'),
            array('mix', array('food' => 'nasi-goreng', 'usus', 'hati', 'ampela', 'teri'), '/mix/nasi-goreng/usus/hati/ampela/teri'),
            array('unknown', null, '/unknown'),
        );
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testAlias($alias, $args, $expected)
    {
        $this->app
            ->route('GET home /', 'foo')
            ->route('GET about /about', 'foo')
            ->route('GET product /product/@name', 'foo')
            ->route('GET everything /everything/*', 'foo')
            ->route('GET mix /mix/@food/*', 'foo')
        ;

        $this->assertEquals($expected, $this->app->alias($alias, $args));
    }

    public function testPath()
    {
        $this->assertEquals($this->app->get('BASE').'/foo', $this->app->path('foo'));
        $this->assertEquals($this->app->get('BASE').'/foo?foo', $this->app->path('foo', null, 'foo'));
        $this->assertEquals($this->app->get('BASE').'/foo?foo=', $this->app->path('foo', null, array('foo' => '')));
    }

    public function rerouteProvider()
    {
        $home = 'You are home.';
        $about = 'Wanna know about know?';
        $product = 'Displaying details of product: ';

        return array(
            array(null, $home),
            array(array('about'), $about),
            array('about', $about),
            array('product(name=foo)', $product.'foo'),
            array('query?foo=bar', 'Query: ?foo=bar'),
            array('/about', $about),
            array('about', '', false, 'Found'),
        );
    }

    /**
     * @dataProvider rerouteProvider
     */
    public function testReroute($target, $expected, $cli = true, $header = null)
    {
        $this->app->mset(array(
            'CLI' => $cli,
            'QUIET' => true,
        ));
        $this->app
            ->route('GET home /', function () {
                return 'You are home.';
            })
            ->route('GET about /about', function () {
                return 'Wanna know about know?';
            })
            ->route('GET product /product/@name', function ($name) {
                return 'Displaying details of product: '.$name;
            })
            ->route('GET query /query', function (App $app) {
                return 'Query: ?'.http_build_query($app->get('GET'));
            })
        ;

        $this->app->reroute($target);
        $this->assertEquals($expected, $this->app->get('OUTPUT'));

        if ($header && !$cli) {
            $headers = $this->app->get('RESPONSE');

            $this->assertTrue(isset($headers['Location']));
            $this->assertEquals($header, $this->app->get('STATUS'));
        }
    }

    public function testRerouteHandler()
    {
        $this->app->one('app_reroute', function (App $app, $url) {
            $app->set('rerouted_to', $url);

            return true;
        });

        $this->app->reroute('/unknown-route');

        $this->assertEquals($this->app->get('BASEURL').'/unknown-route', $this->app->get('rerouted_to'));
    }

    public function runProvider()
    {
        return array(
            array('/unknown', 'HTTP 404 (GET /unknown)'),
            array('/sync-only', 'HTTP 405 (GET /sync-only)'),
            array('/uncallable', 'HTTP 405 (GET /uncallable)'),
            array('/str', 'String response', false),
            array('/arr', '["Array response"]', false),
            array('/call', 'From callable', false),
            array('/null', '', false),
            array('/obj', '', false),
            array('/unlimited/foo/bar/baz', 'foo, bar, baz', false),
            array('/custom/foo/1', 'foo 1', false),
            array('/ajax-access', 'Access granted', false, true),
            array('/cli-access', 'Access granted', false, false, true),
            array('/sync-access', 'Access granted', false),
            array('/uncallable/dinamic/method', 'HTTP 405 (GET /uncallable/dinamic/method)'),
        );
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($path, $expected, $contains = true, $ajax = false, $cli = false)
    {
        $this->app->mset(array(
            'AJAX' => $ajax,
            'CLI' => $cli,
            'QUIET' => true,
            'PATH' => $path,
        ));
        $this->registerRoutes();
        $this->app->run();

        if ($contains) {
            $this->assertContains($expected, $this->app->get('OUTPUT'));
        } else {
            $this->assertEquals($expected, $this->app->get('OUTPUT'));
        }
    }

    public function testRunInterception()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->registerRoutes();

        // Intercept before route
        $this->app->one('app_preroute', function (App $app) {
            return 'Intercepted';
        })->run();
        $this->assertEquals('Intercepted', $this->app->get('OUTPUT'));
    }

    public function testRunModification()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->registerRoutes();

        $this->app->mset(array(
            'ERROR' => false,
            'PATH' => '/str',
        ));
        $this->app->one('app_postroute', function (App $app) {
            return 'Modified';
        })->run();
        $this->assertEquals('Modified', $this->app->get('OUTPUT'));
    }

    public function testRunModifyArguments()
    {
        $this->app
            ->set('QUIET', true)
            ->set('PATH', '/foo/one/two')
            ->route('GET /foo/@bar/@baz', function ($bar, $baz) {
                return 'Foo '.$bar.' '.$baz;
            })
            ->one('app_controller_args', function ($controller, $args) {
                return array_merge(array_slice($args, 1), array_slice($args, 0, 1));
            })
            ->run();

        $this->assertEquals('Foo two one', $this->app->get('OUTPUT'));
    }

    public function testRunException()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->app->route('GET /', function () {
            throw new HttpException('Data not found.', 404);
        });
        $this->app->run();

        $this->assertEquals(404, $this->app->get('CODE'));
        $this->assertContains('Data not found.', $this->app->get('OUTPUT'));
    }

    public function testMock()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->registerRoutes();

        // mock named route
        $this->app->mock('GET str');
        $this->assertEquals('String response', $this->app->get('OUTPUT'));

        // mock named route with unlimited arg
        $this->app->mock('GET unlimited(p1=foo,p2=bar,p3=baz)');
        $this->assertEquals('foo, bar, baz', $this->app->get('OUTPUT'));

        // mock un-named route
        $this->app->mock('GET /custom/foo/1');
        $this->assertEquals('foo 1', $this->app->get('OUTPUT'));

        // modify body and server
        $this->app->mock('PUT /put', null, array('Custom' => 'foo'), 'put content');
        $this->assertEquals('Put mode', $this->app->get('OUTPUT'));
        $this->assertEquals('foo', $this->app->get('SERVER.Custom'));
        $this->assertEquals('put content', $this->app->get('BODY'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mock should contains at least a verb and path, given "GET".
     */
    public function testMockException()
    {
        $this->app->mock('GET');
    }

    public function testRedirect()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ))->route('GET home /', function () {
            return 'You are home.';
        })->redirect('GET /go-far-away', 'home');

        $this->app->mock('GET /go-far-away cli');

        $this->assertEquals('You are home.', $this->app->get('OUTPUT'));
    }

    public function cacheDsnProvider()
    {
        return array(
            array(''),
            array('auto'),
            array('fallback'),
            array('apc'),
            array('apcu'),
            array('memcached=127.0.0.1:11211'),
            array('redis=127.0.0.1:6379'),
            array('folder='.TEMP.'implicit-cache/'),
        );
    }

    /**
     * @dataProvider cacheDsnProvider
     */
    public function testCacheClear($dsn)
    {
        $this->app->set('CACHE', $dsn);

        $this->assertEquals(!$dsn, $this->app->cacheClear('foo'));
    }

    /**
     * @dataProvider cacheDsnProvider
     */
    public function testCacheExists($dsn)
    {
        $this->app->set('CACHE', $dsn);

        $this->assertFalse($this->app->cacheExists('foo'));
    }

    /**
     * @dataProvider cacheDsnProvider
     */
    public function testCacheGet($dsn)
    {
        $this->app->set('CACHE', $dsn);

        $this->assertEquals(array(), $this->app->cacheGet('foo'));
    }

    /**
     * @dataProvider cacheDsnProvider
     */
    public function testCacheSet($dsn)
    {
        $this->app->set('CACHE', $dsn);

        $this->assertTrue($this->app->cacheSet('foo', $dsn));

        if ($dsn) {
            $cache = $this->app->cacheGet('foo');
            $this->assertEquals($dsn, reset($cache));

            if ($dsn) {
                $this->assertTrue($this->app->cacheExists('foo'));

                $this->app->cacheClear('foo');
                $this->assertFalse($this->app->cacheExists('foo'));
            }
        }
    }

    /**
     * @dataProvider cacheDsnProvider
     */
    public function testCacheReset($dsn)
    {
        $this->app->set('CACHE', $dsn);
        $this->app->cacheSet('foo', 'bar');

        $this->assertSame($this->app, $this->app->cacheReset());

        // because of bug on memcached VERSION 1.4.25 Ubuntu (in my laptop)
        // we skip check test for memcached
        if (false === strpos($dsn, 'memcached')) {
            $this->assertFalse($this->app->cacheExists('foo'));
        }

        $this->app->cacheClear('foo');
    }

    public function testCacheRedisSelectDb()
    {
        $this->app->set('CACHE', 'redis=127.0.0.1:6379:1');

        $this->assertTrue($this->app->cacheSet('foo', 'bar'));
        $this->app->cacheClear('foo');
    }

    public function testCacheRedisFallback()
    {
        $this->app->set('CACHE', 'redis=foo');

        $this->assertFalse($this->app->cacheExists('foo'));
        $this->assertEquals('folder', $this->app->get('CACHE_ENGINE'));
    }

    public function testCacheTtl()
    {
        $this->app->set('CACHE', 'fallback');

        $this->assertTrue($this->app->cacheSet('tfoo', 'bar', 1));
        $cached = $this->app->cacheGet('tfoo');
        $this->assertEquals('bar', reset($cached));
        $this->assertTrue($this->app->cacheExists('tfoo'));
        usleep(intval(1e6));
        $this->assertFalse($this->app->cacheExists('tfoo'));
    }

    public function testIsCached()
    {
        $this->app->set('CACHE', 'fallback');

        $this->assertFalse($this->app->isCached('foo', $cached));
        $this->assertNull($cached);

        $this->app->cacheSet('foo', 'bar');
        $this->assertTrue($this->app->isCached('foo', $cached));
        $this->assertEquals('bar', $cached[0]);

        $this->app->cacheClear('foo');
    }

    public function testRouteCached()
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
            'CACHE' => 'fallback',
        ));
        $counter = 0;
        $this->app
            ->route('GET /foo', function () use (&$counter) {
                ++$counter;

                return 'Foo '.$counter;
            }, 1)
        ;
        $this->app->cacheReset();

        $this->app->mock('GET /foo');
        $this->assertEquals('Foo 1', $this->app->get('OUTPUT'));
        $this->assertEquals(1, $counter);

        // second call
        $this->app->mock('GET /foo');
        $this->assertEquals('Foo 1', $this->app->get('OUTPUT'));
        $this->assertEquals(1, $counter);

        // with modified time check
        $this->app->set('REQUEST.If-Modified-Since', '+1 year');
        $this->app->mock('GET /foo');
        $this->assertEquals('', $this->app->get('OUTPUT'));
        $this->assertEquals(1, $counter);
        $this->assertEquals('Not Modified', $this->app->get('STATUS'));

        $this->app->cacheReset();
    }

    public function testEllapsed()
    {
        $this->assertContains('seconds', $this->app->ellapsed());
    }

    public function testLogFiles()
    {
        $this->assertEmpty($this->app->logFiles());

        $file = $this->prepareLogTest();

        $this->assertEmpty($this->app->logFiles());

        touch($file);

        $this->assertEquals(array($file), $this->app->logFiles());
        $this->assertEmpty($this->app->logFiles('foo'));
        $this->assertEquals(array($file), $this->app->logFiles(date('Y-m-d')));

        unlink($file);
    }

    public function testLogClear()
    {
        $file = $this->prepareLogTest();

        touch($file);

        $this->assertFileExists($file);
        $this->app->logClear();
        $this->assertFileNotExists($file);

        touch($file);

        $this->assertFileExists($file);
        $this->app->logClear(date('Y-m-d'));
        $this->assertFileNotExists($file);
    }

    public function testLog()
    {
        $file = $this->prepareLogTest();

        $this->app->log('error', 'Foo.');

        $this->assertFileExists($file);
        $this->assertContains('Foo.', file_get_contents($file));

        $this->app->log('error', 'Bar.');
        $this->assertContains('Bar.', file_get_contents($file));
    }

    public function testLogByCode()
    {
        $file = $this->prepareLogTest();

        $this->app->logByCode(E_USER_ERROR, 'E_USER_ERROR');

        $this->assertFileExists($file);
        $this->assertContains('E_USER_ERROR', file_get_contents($file));
    }

    public function transProvider()
    {
        return array(
            array('foo'),
            array('a_flag', 'Bendera siji'),
            array('i.like.you', 'Saya suka kamu'),
            array('i.like.her', 'Saya suka dia'),
            array('i.like.a_girl', 'Saya suka fala', array('{a_girl}' => 'fala')),
        );
    }

    /**
     * @dataProvider transProvider
     */
    public function testTrans($key, $expected = null, $args = null, $fallback = null)
    {
        $this->app->mset(array(
            'LANGUAGE' => 'id-id',
            'LOCALES' => FIXTURE.'dict/',
        ));

        $this->assertEquals($expected ?: $key, $this->app->trans($key, $args, $fallback));
    }

    public function choiceProvider()
    {
        $fruits = array(
            '{fruit1}' => 'apple',
            '{fruit2}' => 'orange',
            '{fruit3}' => 'mango',
        );

        return array(
            array('foo'),
            array('apples', 0, 'There is no apple'),
            array('apples', 1, 'There is one apple'),
            array('apples', 2, 'There is 2 apples'),
            array('apples', 99, 'There is 99 apples'),
            array('fruits', 0, 'There is no fruits', $fruits),
            array('fruits', 1, 'There is apple, orange and mango', $fruits),
            array('fruits', 2, 'There is lots of apple, orange and mango', $fruits),
        );
    }

    /**
     * @dataProvider choiceProvider
     */
    public function testChoice($key, $count = 0, $expected = null, $args = null, $fallback = null)
    {
        $this->app->mset(array(
            'LOCALES' => FIXTURE.'dict/',
            'LANGUAGE' => 'id-id',
        ));

        $this->assertEquals($expected ?: $key, $this->app->choice($key, $count, $args, $fallback));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Message reference is not a string.
     */
    public function testRefException()
    {
        $this->app->mset(array(
            'LOCALES' => FIXTURE.'dict/',
            'LANGUAGE' => 'id-id',
        ));

        $this->app->trans('invalid_ref');
    }

    public function testTransAlt()
    {
        $this->app->set('DICT.foo', array('bar' => 'baz', 'qux' => 'quux'));

        $this->assertEquals('baz', $this->app->transAlt('foo.bar'));
        $this->assertEquals('quux', $this->app->transAlt('foo.baz', null, null, 'foo.qux'));
        $this->assertEquals('foo.baz', $this->app->transAlt('foo.baz', null, null, 'foo.quux'));
        $this->assertEquals('none', $this->app->transAlt('foo.baz', null, 'none', 'foo.quux'));
    }

    public function testPrepend()
    {
        $this->assertEquals('bar', $this->app->prepend('foo', 'bar')->get('foo'));
        $this->assertEquals('foobar', $this->app->prepend('foo', 'foo')->get('foo'));
    }

    public function testAppend()
    {
        $this->assertEquals('foo', $this->app->append('foo', 'foo')->get('foo'));
        $this->assertEquals('foobar', $this->app->append('foo', 'bar')->get('foo'));
    }

    public function testConfig()
    {
        $this->app->config(FIXTURE.'files/config.php');
        $this->app->set('QUIET', true);

        $this->assertEquals('bar', $this->app->get('foo'));
        $this->assertEquals('baz', $this->app->get('bar'));
        $this->assertEquals('quux', $this->app->get('qux'));
        $this->assertEquals(range(1, 3), $this->app->get('arr'));
        $this->assertEquals('config', $this->app->get('sub'));

        $this->assertEquals('/map/path', $this->app->get('ROUTE_ALIASES.map'));

        $this->app->mock('GET /');
        $this->assertEquals('registered from config', $this->app->get('OUTPUT'));

        $this->app->mock('GET /foo');
        $this->assertEquals($this->app->get('BASEURL').'/', $this->app->get('RESPONSE.Location'));

        $this->assertTrue($this->app->trigger('foo'));
        $this->assertTrue($this->app->trigger('foo_once'));

        $this->assertInstanceOf('DateTime', $this->app->service('foo'));
    }

    public function testCopy()
    {
        $this->app->set('foo', 'bar');
        $this->app->copy('foo', 'bar');

        $this->assertEquals('bar', $this->app->get('bar'));
        $this->assertEquals('bar', $this->app->get('foo'));
    }

    public function testCut()
    {
        $this->app->set('foo', 'bar');
        $this->app->cut('foo', 'bar');

        $this->assertEquals('bar', $this->app->get('bar'));
        $this->assertNull($this->app->get('foo'));
    }

    public function testFlash()
    {
        $this->app->set('foo', 'bar');

        $this->assertEquals('bar', $this->app->flash('foo'));
        $this->assertFalse($this->app->exists('foo'));
    }

    public function testMap()
    {
        $this->app->map('Foo', array(
            'GET foo /bar' => 'bar',
            'GET bar /baz' => 'baz',
        ));

        $this->assertEquals('/bar', $this->app->get('ROUTE_ALIASES.foo'));
        $this->assertEquals('/baz', $this->app->get('ROUTE_ALIASES.bar'));
    }

    public function testTrace()
    {
        $trace = array(
            array(
                'file' => __FILE__,
                'class' => __CLASS__,
                'function' => __FUNCTION__,
                'type' => '->',
                'line' => 20,
            ),
        );
        $expected = '['.__FILE__.':20] '.__CLASS__.'->'.__FUNCTION__."\n";

        $this->assertEquals($expected, $this->app->trace($trace));
    }

    public function testIs()
    {
        $this->assertFalse($this->app->is('foo'));
        $this->assertTrue($this->app->is('CLI'));
    }
}
