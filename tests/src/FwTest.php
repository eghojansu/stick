<?php

/**
 * This file is part of the eghojansu/stick-test library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\EventSubscriberInterface;
use Fal\Stick\Fw;
use Fal\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class FwTest extends TestCase
{
    private $fw;
    private $log;

    public function setUp()
    {
        $this->fw = new Fw();
    }

    public function tearDown()
    {
        if ($this->log) {
            foreach (glob($this->log.'*') as $file) {
                unlink($file);
            }
        }

        header_remove();
    }

    public function testCreate()
    {
        $fw = Fw::create();

        $this->assertNull($fw['server']);
    }

    public function testCreateFromGlobals()
    {
        $fw = Fw::createFromGlobals();

        $this->assertEquals($_SERVER, $fw['SERVER']);
    }

    public function testCreateServerParsing()
    {
        $fw = new Fw(null, null, null, array(
            'CONTENT_LENGTH' => 3,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertEquals(3, $fw['REQUEST']['Content-Length']);
        $this->assertEquals('XMLHttpRequest', $fw['REQUEST']['X-Requested-With']);
        $this->assertTrue($fw['AJAX']);
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->fw['PATH']));
        $this->assertFalse(isset($this->fw['foo']));
        $this->assertFalse(isset($this->fw['foo']['bar']));
    }

    public function testOffsetGet()
    {
        $this->assertEquals('/', $this->fw['PATH']);
        $this->assertNull($this->fw['foo']);
        $this->assertEquals(array(), $this->fw['SESSION']);
    }

    public function testOffsetSet()
    {
        $this->fw['foo'] = 'bar';
        $this->fw['CACHE'] = 'auto';
        $this->fw['SESSION']['foo'] = 'bar';

        $this->assertEquals('bar', $this->fw['foo']);
        $this->assertEquals('auto', $this->fw['CACHE']);
        $this->assertEquals('bar', $this->fw['SESSION']['foo']);
    }

    public function testOffsetUnset()
    {
        $this->fw['foo'] = 'bar';
        unset($this->fw['foo']);
        unset($this->fw['CLI']);
        unset($this->fw['SESSION']);

        $this->assertNull($this->fw['foo']);
        $this->assertTrue($this->fw['CLI']);
        $this->assertEquals(array(), $this->fw['SESSION']);

        $this->fw['SESSION']['foo'] = 'bar';
        unset($this->fw['SESSION']['foo']);
        $this->assertEquals(array(), $this->fw['SESSION']);
    }

    public function testMark()
    {
        $this->fw->mark()->mark('foo');

        $this->assertCount(2, $this->fw['MARKS']);
    }

    public function testEllapsed()
    {
        $this->fw->mark('foo');
        $this->fw->mark();

        $this->assertGreaterThan(0.0, $this->fw->ellapsed(true));
        $this->assertLessThan(1.0, $this->fw->ellapsed('foo'));
        $this->assertLessThan(1.0, $this->fw->ellapsed());
    }

    public function testOverrideRequestMethod()
    {
        $this->fw['REQUEST']['X-Http-Method-Override'] = 'POST';
        $this->fw['POST']['_method'] = 'put';
        $this->fw->overrideRequestMethod();

        $this->assertEquals('PUT', $this->fw['VERB']);
    }

    /**
     * @dataProvider getCliRequestData
     */
    public function testEmulateCliRequest($argv, $path, $uri, $get)
    {
        array_unshift($argv, $_SERVER['argv'][0]);

        $this->fw['SERVER']['argv'] = $argv;
        $this->fw->emulateCliRequest();

        $this->assertEquals($path, $this->fw['PATH']);
        $this->assertEquals($uri, $this->fw['URI']);
        $this->assertEquals($get, $this->fw['GET']);
    }

    /**
     * @dataProvider getRules
     */
    public function testSetRule($id, $rule, $expected)
    {
        $this->fw->setRule($id, $rule);

        $this->assertEquals($expected, $this->fw['SERVICE_RULES'][$id]);
    }

    public function testInstance()
    {
        $this->fw['my_name'] = 'my_name';
        $this->fw->setRule('constructor', array(
            'class' => 'Fal\\Stick\\Test\\Constructor',
            'args' => array('name' => 'foo'),
            'boot' => function ($obj) {
                $obj->setName($obj->getName().' bar');
            },
        ));
        $this->fw->setRule('constructor2', array(
            'class' => 'Fal\\Stick\\Test\\Constructor',
            'args' => array('name' => '%my_name%'),
        ));
        $this->fw->setRule('independent', array(
            'class' => 'Fal\\Stick\\Test\\Independent',
            'args' => array('foo', 'bar', 'baz'),
        ));
        $this->fw->setRule('dependIndependent', array(
            'class' => 'Fal\\Stick\\Test\\DependsIndependent',
            'args' => array('independent' => '%independent%'),
        ));

        $constructorById = $this->fw->instance('constructor');
        $constructorByClass = $this->fw->instance('Fal\\Stick\\Test\\Constructor');
        $this->assertEquals('foo bar', $constructorById->getName());
        $this->assertEquals('foo bar', $constructorByClass->getName());

        $constructor2 = $this->fw->instance('constructor2');
        $this->assertEquals('my_name', $constructor2->getName());

        $noconstructor = $this->fw->instance('Fal\\Stick\\Test\\NoConstructor');
        $this->assertEquals('no constructor', $noconstructor->getName());

        $depends = $this->fw->instance('Fal\\Stick\\Test\\DependsConstructor');
        $this->assertEquals('foo bar', $depends->constructor->getName());

        $depends2 = $this->fw->instance('Fal\\Stick\\Test\\DependsConstructor', array('args' => array(new ConstructorBar())));
        $this->assertEquals('bar', $depends2->constructor->getName());

        $depends3 = $this->fw->instance('Fal\\Stick\\Test\\DependsConstructor', array('args' => array('Fal\\Stick\\Test\\ConstructorBar')));
        $this->assertEquals('bar', $depends3->constructor->getName());

        $independent = $this->fw->instance('independent');
        $this->assertEquals(array('foo', 'bar', 'baz'), $independent->getNames());

        $dependIndependent = $this->fw->instance('dependIndependent');
        $this->assertEquals(array('foo', 'bar', 'baz'), $dependIndependent->independent->getNames());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to create instance for "DateTimeInterface". Please provide instantiable version of DateTimeInterface.
     */
    public function testInstanceException()
    {
        $this->fw->instance('DateTimeInterface');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Constructor of "now" should return instance of DateTime.
     */
    public function testInstanceException2()
    {
        $this->fw->setRule('now', array(
            'class' => 'DateTime',
            'constructor' => function () {
                return new \StdClass();
            },
            'service' => false,
        ));
        $this->fw->instance('now');
    }

    public function testService()
    {
        $this->fw->setRule('noconstructor', 'Fal\\Stick\\Test\\NoConstructor');

        $this->assertSame($this->fw, $this->fw->service('fw'));
        $this->assertSame($this->fw, $this->fw->service('Fal\\Stick\\Fw'));

        $noconstructor = $this->fw->service('Fal\\Stick\\Test\\NoConstructor');
        $this->assertSame($noconstructor, $this->fw->service('noconstructor'));
    }

    public function testCall()
    {
        $result = $this->fw->call('trim', array(' foo '));
        $this->assertEquals('foo', $result);

        $result = $this->fw->call(function (...$args) {
            return implode(' ', $args);
        }, array('foo', 'bar'));
        $this->assertEquals('foo bar', $result);
    }

    public function testGrab()
    {
        $this->assertEquals(array('DateTime', 'format'), $this->fw->grab('DateTime->format', false));
        $this->assertEquals(array('DateTime', 'format'), $this->fw->grab('DateTime::format'));
        $this->assertEquals('foo', $this->fw->grab('foo'));

        $getName = $this->fw->grab('Fal\\Stick\\Test\\NoConstructor->getName');
        $this->assertEquals('no constructor', $getName());
    }

    public function testExecute()
    {
        $this->fw->execute(function (Fw $fw) {
            $fw['foo'] = 'bar';
        });

        $this->assertEquals('bar', $this->fw['foo']);
    }

    public function testSubscribe()
    {
        $this->fw->subscribe('Fal\\Stick\\Test\\NoConstructorSubscriber');

        $this->assertEquals(array('Fal\\Stick\\Test\\NoConstructor->getName', false), $this->fw['EVENTS']['no_constructor']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Subscriber "foo" should implements Fal\Stick\EventSubscriberInterface.
     */
    public function testSubscribeException()
    {
        $this->fw->subscribe('foo');
    }

    public function testOn()
    {
        $this->fw->on('foo', 'bar');

        $this->assertEquals(array('bar', false), $this->fw['EVENTS']['foo']);
    }

    public function testOne()
    {
        $this->fw->one('foo', 'bar');

        $this->assertEquals(array('bar', true), $this->fw['EVENTS']['foo']);
    }

    public function testOff()
    {
        $this->fw->one('foo', 'bar')->off('foo');

        $this->assertEquals(array(), $this->fw['EVENTS']);
    }

    public function testTrigger()
    {
        $this->fw->one('foo', 'Fal\\Stick\\Test\\NoConstructor'.'->getName');

        $this->assertEquals('no constructor', $this->fw->trigger('foo'));
        $this->assertNull($this->fw->trigger('bar'));
    }

    public function testLogFiles()
    {
        $this->assertEmpty($this->fw->logFiles());

        $file = $this->prepareLogTest();

        $this->assertEmpty($this->fw->logFiles());

        touch($file);

        $this->assertEquals(array($file), $this->fw->logFiles());
        $this->assertEmpty($this->fw->logFiles('foo'));
        $this->assertEquals(array($file), $this->fw->logFiles(date('Y-m-d')));

        unlink($file);
    }

    public function testLogClear()
    {
        $file = $this->prepareLogTest();

        touch($file);

        $this->assertFileExists($file);
        $this->fw->logClear();
        $this->assertFileNotExists($file);

        touch($file);

        $this->assertFileExists($file);
        $this->fw->logClear(date('Y-m-d'));
        $this->assertFileNotExists($file);
    }

    public function testLog()
    {
        $file = $this->prepareLogTest();

        $this->fw->log('error', 'Foo.');

        $this->assertFileExists($file);
        $this->assertContains('Foo.', file_get_contents($file));

        $this->fw->log('error', 'Bar.');
        $this->assertContains('Bar.', file_get_contents($file));
    }

    public function testLogByCode()
    {
        $file = $this->prepareLogTest();

        $this->fw->logByCode(E_USER_ERROR, 'E_USER_ERROR');

        $this->assertFileExists($file);
        $this->assertContains('E_USER_ERROR', file_get_contents($file));
    }

    /**
     * @dataProvider getExpires
     */
    public function testExpire($secs, array $headers)
    {
        $expected = array_merge(array(
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
        ), $headers);
        $this->fw->expire($secs);
        $actual = array_keys($this->fw['RESPONSE']);

        $this->assertEquals($expected, $actual);
    }

    public function testStatus()
    {
        $this->fw->status(404);

        $this->assertEquals(404, $this->fw['CODE']);
        $this->assertEquals('Not Found', $this->fw['STATUS']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported HTTP code: 900.
     */
    public function testStatusException()
    {
        $this->fw->status(900);
    }

    public function testAsset()
    {
        $this->assertEquals('/foo.css', $this->fw->asset('foo.css'));

        $this->fw['ASSET'] = 'v033';
        $this->assertEquals('/foo.css?v033', $this->fw->asset('foo.css'));
    }

    public function testSend()
    {
        $output = 'foo';
        $headers = array('Foo' => 'bar');

        $this->fw['CLI'] = false;
        $this->fw['MIME'] = 'text/plain';
        $this->fw['COOKIE'] = array('foo' => 'bar');

        $this->updateInitialValue('COOKIE', array(
            'xfoo' => 'xbar',
        ));

        $this->expectOutputString($output);
        $this->fw->send(404, $headers, $output);

        $this->assertTrue($this->fw['SENT']);
        $this->assertEquals($output, $this->fw['OUTPUT']);
        $this->assertEquals($headers, $this->fw['RESPONSE']);

        if (function_exists('xdebug_get_headers')) {
            $actualHeaders = xdebug_get_headers();
            $first = array_shift($actualHeaders);
            $pattern = '/Set-Cookie: foo=(.+); HttpOnly/';

            $expected = array(
                'Set-Cookie: xfoo=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; HttpOnly',
                'Foo: bar',
                'Content-type: text/plain;charset=UTF-8',
            );

            $this->assertEquals($expected, $actualHeaders);
            $this->assertRegExp($pattern, $first);
        }

        $this->fw->send(500);
        $this->assertEquals(404, $this->fw['CODE']);
    }

    public function testSendContentChunked()
    {
        $this->expectOutputString('foo');
        $this->fw->send(null, null, 'foo', null, 1);
    }

    /**
     * @dataProvider getErrors
     */
    public function testError($httpCode, $sets, $response, $mime = null)
    {
        $this->fw['QUIET'] = true;

        foreach ((array) $sets as $key => $value) {
            $this->fw[$key] = $value;
        }

        $this->fw->error($httpCode);

        $this->assertEquals($response, $this->fw['OUTPUT']);

        if ($mime) {
            $this->assertEquals($mime, $this->fw['MIME']);
        }
    }

    public function testErrorTrace()
    {
        $this->fw['QUIET'] = true;
        $this->fw['DEBUG'] = true;
        $this->fw->error(404);

        $expected = 'Fal\\Stick\\Test\\FwTest->testErrorTrace';

        $this->assertContains($expected, $this->fw['OUTPUT']);
    }

    public function testErrorHandler()
    {
        $this->fw['QUIET'] = true;
        $this->fw->one('fw.error', function ($message) {
            return 'foo - '.$message;
        });

        $this->fw->error(404, 'bar');
        $this->assertEquals('foo - bar', $this->fw['OUTPUT']);
    }

    public function testErrorHandlerError()
    {
        $this->fw['QUIET'] = true;
        $this->fw->one('fw.error', function ($message) {
            // this is trigger another error
            return 1 / 0;
        });

        $error = array(
            'code' => 500,
            'status' => 'Internal Server Error',
            'text' => 'Division by zero',
            'trace' => '',
        );
        $this->fw->error(404);

        $this->assertEquals($error, $this->fw['ERROR']);
        $this->assertContains($error['text'], $this->fw['OUTPUT']);
    }

    public function testErrorPrior()
    {
        $this->fw['QUIET'] = true;

        $this->assertNull($this->fw['ERROR']);

        $prior = array(
            'code' => 404,
            'status' => 'Not Found',
            'text' => 'HTTP 404 (GET /)',
            'trace' => '',
        );
        $this->fw->error(404);
        $this->assertEquals($prior, $this->fw['ERROR']);

        $this->fw->error(405);
        $this->assertContains('Not Found', $this->fw['OUTPUT']);
    }

    /**
     * @dataProvider getRedirections
     */
    public function testReroute($target, $expected, $cli = true, $header = null)
    {
        $this->fw['CLI'] = $cli;
        $this->fw['QUIET'] = true;
        $this->fw
            ->route('GET home /', function () {
                return 'You are home.';
            })
            ->route('GET about /about', function () {
                return 'Wanna know about know?';
            })
            ->route('GET product /product/@name', function ($name) {
                return 'Displaying details of product: '.$name;
            })
            ->route('GET query /query', function (Fw $fw) {
                return 'Query: ?'.http_build_query($fw['GET']);
            })
        ;

        $this->fw->reroute($target);
        $this->assertEquals($expected, $this->fw['OUTPUT']);

        if ($header && !$cli) {
            $headers = $this->fw['RESPONSE'];

            $this->assertTrue(isset($headers['Location']));
            $this->assertEquals($header, $this->fw['STATUS']);
        }
    }

    public function testRerouteHandler()
    {
        $this->fw->one('fw.reroute', function (Fw $fw, $url) {
            $fw['rerouted_to'] = $url;

            return true;
        })->reroute('/unknown-route');

        $this->assertEquals($this->fw['BASEURL'].'/unknown-route', $this->fw['rerouted_to']);
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoute($verbs, $path, $alias = null, $mode = null, $bitwiseMode = 0)
    {
        $this->fw->route($verbs.' '.$alias.' '.$path.' '.$mode, 'foo');

        $routes = $this->fw['ROUTES'][$path];

        $this->assertNotEmpty($routes);
        $this->assertTrue(array_key_exists($bitwiseMode, $routes));
        $this->assertCount(count(explode('|', $verbs)), $routes[$bitwiseMode]);

        if ($alias) {
            $this->assertEquals($path, $this->fw['ROUTE_ALIASES'][$alias]);
        }
    }

    public function testRouteAlias()
    {
        $this->fw
            ->route('GET foo /foo', 'foo')
            ->route('GET foo cli', 'bar')
        ;

        $routes = $this->fw['ROUTES']['/foo'];
        $aliases = $this->fw['ROUTE_ALIASES'];

        $this->assertNotEmpty($routes);
        $this->assertTrue(array_key_exists(0, $routes));
        $this->assertTrue(array_key_exists(2 /* Fw::REQ_CLI */, $routes));
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
        $this->fw->route('GET', 'foo');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route "foo" does not exists.
     */
    public function testRouteException2()
    {
        $this->fw->route('GET foo', 'foo');
    }

    /**
     * @dataProvider getAliases
     */
    public function testAlias($alias, $args, $expected)
    {
        $this->fw
            ->route('GET home /', 'foo')
            ->route('GET about /about', 'foo')
            ->route('GET product /product/@name', 'foo')
            ->route('GET everything /everything/*', 'foo')
            ->route('GET mix /mix/@food/*', 'foo')
            ->route('GET custom /pesan/@food/(?<count>\d+)/*', 'foo')
        ;

        $this->assertEquals($expected, $this->fw->alias($alias, $args));
    }

    public function testPath()
    {
        $base = $this->fw['BASE'];

        $this->assertEquals($base.'/foo', $this->fw->path('foo'));
        $this->assertEquals($base.'/foo?foo', $this->fw->path('foo', null, 'foo'));
        $this->assertEquals($base.'/foo?foo=', $this->fw->path('foo', null, array('foo' => '')));
    }

    public function testController()
    {
        $this->fw->controller('Foo', array(
            'GET foo /bar' => 'bar',
            'GET bar /baz' => 'baz',
        ));

        $this->assertEquals('/bar', $this->fw['ROUTE_ALIASES']['foo']);
        $this->assertEquals('/baz', $this->fw['ROUTE_ALIASES']['bar']);
        $this->assertEquals('Foo->bar', $this->fw['ROUTE_HANDLERS'][0][0]);
    }

    public function testRest()
    {
        $this->fw->rest('/foo', 'Foo', 'foo');

        $this->assertEquals('/foo', $this->fw['ROUTE_ALIASES']['foo']);
        $this->assertEquals('/foo/@item', $this->fw['ROUTE_ALIASES']['foo_item']);
        $this->assertCount(2, $this->fw['ROUTES']['/foo'][0]);
        $this->assertCount(3, $this->fw['ROUTES']['/foo/@item'][0]);
        $this->assertEquals('Foo->all', $this->fw['ROUTE_HANDLERS'][0][0]);
        $this->assertEquals('Foo->create', $this->fw['ROUTE_HANDLERS'][1][0]);
        $this->assertEquals('Foo->get', $this->fw['ROUTE_HANDLERS'][2][0]);
        $this->assertEquals('Foo->put', $this->fw['ROUTE_HANDLERS'][3][0]);
        $this->assertEquals('Foo->delete', $this->fw['ROUTE_HANDLERS'][4][0]);
    }

    /**
     * @dataProvider getValidRoutes
     */
    public function testRun($path, $expected, $contains = true, $ajax = false, $cli = false)
    {
        $this->fw['AJAX'] = $ajax;
        $this->fw['CLI'] = $cli;
        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = $path;
        $this->registerRoutes();
        $this->fw->run();

        if ($contains) {
            $this->assertContains($expected, $this->fw['OUTPUT']);
        } else {
            $this->assertEquals($expected, $this->fw['OUTPUT']);
        }
    }

    public function testRunNoRoute()
    {
        $this->fw['QUIET'] = true;
        $this->fw->run();

        $this->assertContains('No route defined.', $this->fw['OUTPUT']);
    }

    public function testRunInterception()
    {
        $this->fw['QUIET'] = true;
        $this->registerRoutes();

        $this->fw->one('fw.preroute', function () {
            return 'Intercepted';
        })->run();

        $this->assertEquals('Intercepted', $this->fw['OUTPUT']);
    }

    public function testRunModification()
    {
        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = '/str';
        $this->registerRoutes();

        $this->fw->one('fw.postroute', function () {
            return 'Modified';
        })->run();

        $this->assertEquals('Modified', $this->fw['OUTPUT']);
    }

    public function testRunModifyArguments()
    {
        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = '/foo/one/two';
        $this->fw
            ->route('GET /foo/@bar/@baz', function ($bar, $baz) {
                return 'Foo '.$bar.' '.$baz;
            })
            ->one('fw.controller_args', function ($controller, $args) {
                return array_merge(array_slice($args, 1), array_slice($args, 0, 1));
            })
            ->run();

        $this->assertEquals('Foo two one', $this->fw['OUTPUT']);
    }

    public function testRunException()
    {
        $this->fw['QUIET'] = true;
        $this->fw->route('GET /', function () {
            throw new HttpException('Data not found.', 404);
        });
        $this->fw->run();

        $this->assertEquals(404, $this->fw['CODE']);
        $this->assertContains('Data not found.', $this->fw['OUTPUT']);
    }

    public function testRunCached()
    {
        $this->setupCache();

        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = '/foo';

        $counter = 0;
        $this->fw->route('GET /foo', function () use (&$counter) {
            ++$counter;

            return 'Foo '.$counter;
        }, 1);

        $this->fw->run();
        $this->assertEquals('Foo 1', $this->fw['OUTPUT']);
        $this->assertEquals(1, $counter);

        // second call
        $this->fw->run();
        $this->assertEquals('Foo 1', $this->fw['OUTPUT']);
        $this->assertEquals(1, $counter);

        // with modified time check
        $this->fw['REQUEST']['If-Modified-Since'] = '+1 year';
        unset($this->fw['OUTPUT'], $this->fw['SENT']);
        $this->fw->run();
        $this->assertEquals('', $this->fw['OUTPUT']);
        $this->assertEquals(1, $counter);
        $this->assertEquals('Not Modified', $this->fw['STATUS']);
    }

    public function testRedirect()
    {
        $this->fw['QUIET'] = true;
        $this->fw['PATH'] = '/go-far-away';

        $this->fw
            ->route('GET home /', function () {
                return 'You are home.';
            })
            ->redirect('GET /go-far-away', 'home')
            ->run()
        ;

        $this->assertEquals('You are home.', $this->fw['OUTPUT']);
    }

    public function testMock()
    {
        $this->fw['QUIET'] = true;
        $this->registerRoutes();

        // mock named route
        $this->fw->mock('GET str');
        $this->assertEquals('String response', $this->fw['OUTPUT']);

        // mock named route with unlimited arg
        $this->fw->mock('GET unlimited(p1=foo,p2=bar,p3=baz)');
        $this->assertEquals('foo, bar, baz', $this->fw['OUTPUT']);

        // mock un-named route
        $this->fw->mock('GET /custom/foo/1');
        $this->assertEquals('foo 1', $this->fw['OUTPUT']);

        // modify body and server
        $this->fw->mock('PUT /put', null, array('Custom' => 'foo'), 'put content');
        $this->assertEquals('Put mode', $this->fw['OUTPUT']);
        $this->assertEquals('foo', $this->fw['SERVER']['Custom']);
        $this->assertEquals('put content', $this->fw['BODY']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mock should contains at least a verb and path, given "GET".
     */
    public function testMockException()
    {
        $this->fw->mock('GET');
    }

    public function testConfig()
    {
        $this->fw->config(FIXTURE.'config/independent.php');
        $this->fw->config(FIXTURE.'config/empty.php');
        $this->fw->config(FIXTURE.'config/all.php');

        $this->assertTrue($this->fw['independent']);
        // from empty
        $this->assertEquals(1, $this->fw[0]);

        $this->assertTrue($this->fw['all']);
        $this->assertTrue($this->fw['embedded']);

        $this->assertEquals('bar', $this->fw['foo']);
        $this->assertEquals(1, $this->fw['one']);
        $this->assertEquals(1.5, $this->fw['one_point_five']);
        $this->assertTrue($this->fw['bool_true']);
        $this->assertFalse($this->fw['bool_false']);
        $this->assertNull($this->fw['null_null']);
        $this->assertEquals(array('foo', 'bar', 'baz' => 'qux'), $this->fw['arr']);

        $constructor = $this->fw->service('constructor');
        $this->assertEquals('from config', $constructor->getName());

        $this->fw['QUIET'] = true;

        $this->fw->mock('GET /home');
        $this->assertEquals('Home', $this->fw['OUTPUT']);

        $this->fw->mock('GET /controller');
        $this->assertEquals('Home', $this->fw['OUTPUT']);

        $this->fw->mock('GET /redirect-to-home cli');
        $this->assertEquals('Home', $this->fw['OUTPUT']);

        $this->fw->mock('GET /books cli');
        $this->assertEquals('all', $this->fw['OUTPUT']);

        $this->fw->mock('POST /books cli');
        $this->assertEquals('create', $this->fw['OUTPUT']);

        $this->fw->mock('GET /books/1 cli');
        $this->assertEquals('get 1', $this->fw['OUTPUT']);

        $this->fw->mock('PUT /books/1 cli');
        $this->assertEquals('put 1', $this->fw['OUTPUT']);

        $this->fw->mock('DELETE /books/1 cli');
        $this->assertEquals('delete 1', $this->fw['OUTPUT']);

        $this->assertEquals('no constructor', $this->fw->trigger('no_constructor'));
    }

    public function testConfigArray()
    {
        $this->fw->config(array('foo' => 'bar'));

        $this->assertEquals('bar', $this->fw['foo']);
    }

    public function testRequireFile()
    {
        $this->assertEquals(array('independent' => true), Fw::requireFile(FIXTURE.'/config/independent.php'));
    }

    /**
     * @dataProvider getCastData
     */
    public function testCast($expected, $val)
    {
        $this->assertEquals($expected, $this->fw->cast($val));
    }

    public function testMkdir()
    {
        $path = TEMP.'test-mkdir';

        $this->assertFalse(is_dir($path));
        $this->assertTrue($this->fw->mkdir($path));
        $this->assertTrue(is_dir($path));
        $this->assertTrue($this->fw->mkdir($path));

        rmdir($path);
    }

    public function testRead()
    {
        $file = TEMP.'test-read.txt';
        file_put_contents($file, $file);

        $this->assertEquals($file, $this->fw->read($file));
        unlink($file);
    }

    public function testWrite()
    {
        $file = TEMP.'test-write.txt';
        $this->fw->write($file, $file);

        $this->assertEquals($file, file_get_contents($file));
        unlink($file);
    }

    public function testDelete()
    {
        $file = TEMP.'test-delete.txt';
        $this->assertFalse($this->fw->delete($file));

        touch($file);
        $this->assertTrue($this->fw->delete($file));
    }

    public function testHash()
    {
        $fooHash = $this->fw->hash('foo');
        $barHash = $this->fw->hash('barbaz');

        $this->assertEquals(13, strlen($fooHash));
        $this->assertEquals(13, strlen($barHash));
        $this->assertEquals($fooHash, $this->fw->hash('foo'));
    }

    public function testFixslashes()
    {
        $this->assertEquals('/foo/bar', $this->fw->fixslashes('\\foo\\bar'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('camelCase', $this->fw->camelCase('camel_case'));
        $this->assertEquals('camelCase', $this->fw->camelCase('Camel_Case'));
        $this->assertEquals('camelCase', $this->fw->camelCase('camelCase'));
        $this->assertEquals('camelCase', $this->fw->camelCase('CamelCase'));
    }

    public function testSnakeCase()
    {
        $this->assertEquals('snake_case', $this->fw->snakeCase('snakeCase'));
        $this->assertEquals('snake_case', $this->fw->snakeCase('snake_case'));
    }

    public function testClassName()
    {
        $this->assertEquals('FwTest', $this->fw->className($this));
        $this->assertEquals('FwTest', $this->fw->className(self::class));
    }

    public function testSplit()
    {
        $this->assertEquals(array(), $this->fw->split(null));
        $this->assertEquals(array(), $this->fw->split(''));
        $this->assertEquals(array('foo'), $this->fw->split(array('foo')));
        $this->assertEquals(array('foo', 'bar'), $this->fw->split('foo,bar ,,'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->split('foo,bar'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->split('foo|bar'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->split('foo;bar'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->split('foo=bar', '='));
    }

    /**
     * @dataProvider getTranslations
     */
    public function testTrans($key, $expected = null, $args = null, $fallback = null)
    {
        $this->fw['LANGUAGE'] = 'id-id';
        $this->fw['LOCALES'] = FIXTURE.'dict/';

        $this->assertEquals($expected ?: $key, $this->fw->trans($key, $args, $fallback));
    }

    /**
     * @dataProvider getChoices
     */
    public function testChoice($key, $count = 0, $expected = null, $args = null, $fallback = null)
    {
        $this->fw['LANGUAGE'] = 'id-id';
        $this->fw['LOCALES'] = FIXTURE.'dict/';

        $this->assertEquals($expected ?: $key, $this->fw->choice($key, $count, $args, $fallback));
    }

    public function testAlt()
    {
        $this->fw['DICT'] = array('foo' => array('bar' => 'baz', 'qux' => 'quux'));

        $this->assertEquals('baz', $this->fw->alt('foo.bar'));
        $this->assertEquals('quux', $this->fw->alt('foo.baz', null, null, 'foo.qux'));
        $this->assertEquals('foo.baz', $this->fw->alt('foo.baz', null, null, 'foo.quux'));
        $this->assertEquals('none', $this->fw->alt('foo.baz', null, 'none', 'foo.quux'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Message reference is not a string.
     */
    public function testLangRefException()
    {
        $this->fw['LANGUAGE'] = 'id-id';
        $this->fw['LOCALES'] = FIXTURE.'dict/';

        $this->fw->trans('invalid_ref');
    }

    public function testFindClass()
    {
        $this->fw['AUTOLOAD']['FixtureNs\\'] = FIXTURE.'classes2/FixtureNs/';
        $this->fw['AUTOLOAD_FALLBACK'] = FIXTURE.'classes2/NoNs/';

        $this->assertEquals(FIXTURE.'classes2/FixtureNs/ClassA.php', $this->fw->findClass('FixtureNs\\ClassA'));
        $this->assertEquals(FIXTURE.'classes2/NoNs/ClassOutNs.php', $this->fw->findClass('ClassOutNs'));
        $this->assertNull($this->fw->findClass('UnknownClass'));
    }

    public function testGetRule()
    {
        $this->hive['SERVICE_RULES']['foo'] = array('class' => 'foo');
        $this->hive['SERVICE_ALIASES']['bar'] = 'foo';

        $id = 'foo';
        $override = array('class' => 'bar');
        $expected = array(
            'args' => null,
            'boot' => null,
            'class' => 'bar',
            'constructor' => null,
            'service' => false,
            'use' => null,
        );

        $this->assertEquals($expected, $this->fw->getRule($id, $override, $realId));
        $this->assertEquals($id, $realId);
        $this->assertEquals($expected, $this->fw->getRule('bar', $override));
    }

    public function testCache()
    {
        $this->assertInstanceOf('Fal\\Stick\\CacheInterface', $this->fw->cache());
        $this->assertFalse($this->fw->cache('exists', 'foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cache should be instance of Fal\Stick\CacheInterface, DateTime given.
     */
    public function testCacheException()
    {
        $this->fw['CACHE'] = 'DateTime';
        $this->fw->cache();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Call to undefined cache method "foo".
     */
    public function testCacheException2()
    {
        $this->fw->cache('foo');
    }

    /**
     * @dataProvider getCollections
     */
    public function testPick($expected, $key, $collections = null, $default = null, $twoTier = false)
    {
        $this->assertEquals($expected, $this->fw->pick($key, $collections, $default, $twoTier));
    }

    public function getCliRequestData()
    {
        return array(
            array(array(), '/', '/', array()),
            array(array('/foo'), '/foo', '/foo', array()),
            array(array('/foo?bar=baz&qux'), '/foo', '/foo?bar=baz&qux', array('bar' => 'baz', 'qux' => '')),
            array(array('foo', 'bar', '-fo', '--bar=baz'), '/foo/bar', '/foo/bar?bar=baz&f=&o=', array('f' => '', 'o' => '', 'bar' => 'baz')),
        );
    }

    public function getRules()
    {
        return array(
            array('foo', 'trim', array(
                'constructor' => 'trim',
                'class' => 'foo',
                'service' => true,
            )),
            array('foo', new NoConstructor(), array(
                'class' => 'Fal\\Stick\\Test\\NoConstructor',
                'service' => true,
            )),
            array('foo', 'Fal\\Stick\\Test\\Constructor', array(
                'class' => 'Fal\\Stick\\Test\\Constructor',
                'service' => true,
            )),
            array('Fal\\Stick\\Test\\Constructor', array('service' => false), array(
                'service' => false,
                'class' => 'Fal\\Stick\\Test\\Constructor',
            )),
            array('Fal\\Stick\\Test\\Constructor', false, array(
                'class' => 'Fal\\Stick\\Test\\Constructor',
                'service' => false,
                false,
            )),
            array('Fal\\Stick\\Test\\Constructor', null, array(
                'class' => 'Fal\\Stick\\Test\\Constructor',
                'service' => true,
            )),
        );
    }

    public function getExpires()
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

    public function getErrors()
    {
        return array(
            array(404, null, 'Status : Not Found'.PHP_EOL.'Text : HTTP 404 (GET /)'.PHP_EOL.PHP_EOL),
            array(404, array('AJAX' => true), '{"code":404,"status":"Not Found","text":"HTTP 404 (GET \/)"}', 'application/json'),
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

    public function getRedirections()
    {
        return array(
            array(null, 'You are home.'),
            array(array('about'), 'Wanna know about know?'),
            array('about', 'Wanna know about know?'),
            array('product(name=foo)', 'Displaying details of product: foo'),
            array('query?foo=bar', 'Query: ?foo=bar'),
            array('/about', 'Wanna know about know?'),
            array('about', '', false, 'Found'),
        );
    }

    public function getRoutes()
    {
        return array(
            array('GET', '/foo'),
            array('GET|POST', '/foo'),
            array('GET|POST', '/foo', 'foo'),
            array('GET|POST', '/foo', 'foo', 'cli', 2 /* Fw::REQ_CLI */),
        );
    }

    public function getAliases()
    {
        return array(
            array('home', null, '/'),
            array('about', null, '/about'),
            array('product', array('name' => 'foo'), '/product/foo'),
            array('product', 'name=foo', '/product/foo'),
            array('everything', array('foo', 'bar', 'baz'), '/everything/foo/bar/baz'),
            array('mix', array('food' => 'nasi-goreng', 'usus', 'hati', 'ampela', 'teri'), '/mix/nasi-goreng/usus/hati/ampela/teri'),
            array('unknown', null, '/unknown'),
            array('custom', array('food' => 'batagor', 1, 'pedas', 'kuah'), '/pesan/batagor/1/pedas/kuah'),
        );
    }

    public function getValidRoutes()
    {
        return array(
            array('/unknown', 'HTTP 404 (GET /unknown)'),
            array('/sync-only', 'HTTP 405 (GET /sync-only)'),
            array('/uncallable', 'HTTP 405 (GET /uncallable)'),
            array('/no-class', 'HTTP 404 (GET /no-class)'),
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
            array('/simple-controller-home', 'Home', false),
        );
    }

    public function getCastData()
    {
        return array(
            array(1, '1'),
            array(1.2, '1.2'),
            array('foo', 'foo'),
            array(null, 'null'),
            array(true, 'true'),
            array(false, 'false'),
            array(array('foo'), array('foo')),
        );
    }

    public function getTranslations()
    {
        return array(
            array('foo'),
            array('a_flag', 'Bendera siji'),
            array('i.like.you', 'Saya suka kamu'),
            array('i.like.her', 'Saya suka dia'),
            array('i.like.a_girl', 'Saya suka fala', array('{a_girl}' => 'fala')),
        );
    }

    public function getChoices()
    {
        return array(
            array('foo'),
            array('apples', 0, 'There is no apple'),
            array('apples', 1, 'There is one apple'),
            array('apples', 2, 'There is 2 apples'),
            array('apples', 99, 'There is 99 apples'),
            array('fruits', 0, 'There is no fruits', array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('fruits', 1, 'There is apple, orange and mango', array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('fruits', 2, 'There is lots of apple, orange and mango', array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
        );
    }

    public function getCollections()
    {
        return array(
            array('foo', 'bar', array('bar' => 'foo')),
            array('foo', 'bar', array(array('bar' => 'foo')), null, true),
            array(null, 'foo', array('bar' => 'foo')),
        );
    }

    private function registerRoutes()
    {
        $this->fw
            ->route('GET str /str', function () {
                return 'String response';
            })
            ->route('GET arr /arr', function () {
                return array('Array response');
            })
            ->route('GET call /call', function (Fw $fw) {
                return function () use ($fw) {
                    $fw['OUTPUT'] = 'From callable';
                };
            })
            ->route('GET /null', function () {
                return null;
            })
            ->route('GET /obj', function (Fw $fw) {
                return $fw;
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
            ->route('GET /sync-only sync', 'xxxfooxxx')
            ->route('GET /uncallable', 'xxxfooxxx')
            ->route('GET /simple-controller-home', 'Fal\\Stick\\Test\\SimpleController->home')
            ->route('GET /no-class', 'UnknownClass->home')
        ;
    }

    private function prepareLogTest()
    {
        $this->fw['LOG'] = $this->log = $dir = TEMP.'logs-test/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'log_'.date('Y-m-d').'.log';
    }

    private function updateInitialValue($name, $value)
    {
        $ref = new \ReflectionProperty($this->fw, 'init');
        $ref->setAccessible(true);
        $val = $ref->getValue($this->fw);
        $val[$name] = $value;
        $ref->setValue($this->fw, $val);
    }

    private function setupCache()
    {
        $this->fw['CACHE'] = 'fallback';
        $this->fw->cache('reset');
    }
}

class Constructor
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}

class NoConstructor
{
    public function getName()
    {
        return 'no constructor';
    }
}

class DependsConstructor
{
    public $constructor;

    public function __construct(Constructor $constructor)
    {
        $this->constructor = $constructor;
    }
}

class ConstructorBar extends Constructor
{
    public function __construct()
    {
        $this->name = 'bar';
    }
}

class Independent
{
    private $names;

    public function __construct(...$names)
    {
        $this->names = $names;
    }

    public function getNames()
    {
        return $this->names;
    }
}

class DependsIndependent
{
    public $independent;

    public function __construct(Independent $independent)
    {
        $this->independent = $independent;
    }
}

class SimpleController
{
    public function home()
    {
        return 'Home';
    }
}

class BookController
{
    public function all()
    {
        return 'all';
    }

    public function create()
    {
        return 'create';
    }

    public function get($item)
    {
        return 'get '.$item;
    }

    public function put($item)
    {
        return 'put '.$item;
    }

    public function delete($item)
    {
        return 'delete '.$item;
    }
}

class NoConstructorSubscriber implements EventSubscriberInterface
{
    public static function getEvents(): array
    {
        return array('no_constructor' => 'Fal\\Stick\\Test\\NoConstructor->getName');
    }
}
