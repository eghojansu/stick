<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 20:40
 */

namespace Fal\Stick\Test;

use Fal\Stick\Core;
use Fal\Stick\Event;
use Fal\Stick\CacheItem;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    private $core;

    public function setup()
    {
        $this->core = new Core();
    }

    public function teardown()
    {
        header_remove();
    }

    public function testCreateFromGlobals()
    {
        $core = Core::createFromGlobals();

        $this->assertEquals($_SERVER, $core->get('SERVER'));
    }

    public function testHash()
    {
        $this->assertEquals('1xnmsgr3l2f5f', Core::hash('foo'));
    }

    public function testRequestHeaders()
    {
        $expected = array(
            'Content-Length' => 0,
            'X-Requested-With' => 'Foo',
        );
        $server = array(
            'CONTENT_LENGTH' => 0,
            'HTTP_X_REQUESTED_WITH' => 'Foo',
        );

        $this->assertEquals($expected, Core::requestHeaders($server));
    }

    /**
     * @dataProvider normalizeUploadFilesProvider
     */
    public function testNormalizeUploadFiles($expected, $files)
    {
        $this->assertEquals($expected, Core::normalizeUploadFiles($files));
    }

    public function testSplit()
    {
        $this->assertEquals(array('foo', 'bar'), Core::split(array('foo', 'bar')));
        $this->assertEquals(array(), Core::split(null));
        $this->assertEquals(array('foo', 'bar'), Core::split('foo,bar'));
        $this->assertEquals(array('foo', 'bar'), Core::split('foo:bar', ':'));
        $this->assertEquals(array('foo', 'bar', 'baz'), Core::split('foo, bar|baz '));
    }

    public function testJoin()
    {
        $this->assertEquals('foo,bar', Core::join(array('foo', 'bar')));
        $this->assertEquals('foo:bar', Core::join(array('foo', 'bar'), ':'));
    }

    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize($expected, $key, $server)
    {
        $this->core->initialize(null, null, null, null, $server);

        $this->assertEquals($expected, $this->core->get($key));
    }

    /**
     * @dataProvider overrideRequestMethodProvider
     */
    public function testOverrideRequestMethod($expected, $server, $request = null)
    {
        $this->core->initialize(null, $request, null, null, $server);

        $this->assertEquals($expected, $this->core->overrideRequestMethod()->get('VERB'));
    }

    /**
     * @dataProvider emulateCliRequestProvider
     */
    public function testEmulateCliRequest($expected, $query, $server)
    {
        $this->core->initialize(null, null, null, null, $server);
        $this->core->emulateCliRequest();

        $this->assertEquals($expected, $this->core->get('PATH'));
        $this->assertEquals($query, $this->core->get('QUERY'));
    }

    public function testRegisterErrorHandler()
    {
        $this->assertSame($this->core, $this->core->registerErrorHandler());

        restore_error_handler();
        restore_exception_handler();
    }

    public function testUnregisterErrorHandler()
    {
        $this->assertSame($this->core, $this->core->registerErrorHandler()->unregisterErrorHandler());
    }

    public function testRegisterShutdownHandler()
    {
        // skipped
        $this->assertTrue(true);
    }

    public function testReference()
    {
        $ref = $this->core->reference('foo', false);
        $this->assertNull($ref);

        $ref = &$this->core->reference('SESSION.foo.bar', true, $hive, $found);
        $ref = 'baz';
        $set = $this->core->reference('SESSION.foo.bar', false, $hive2, $found2);

        $this->assertEquals('baz', $set);
        $this->assertFalse($found);
        $this->assertTrue($found2);

        $ref = &$this->core->reference('obj');
        $ref = new \stdClass();

        $ref = &$this->core->reference('obj.foo');
        $ref = 'bar';
        $set = $this->core->reference('obj.foo', false, $hive3, $found);

        $this->assertEquals('bar', $set);
        $this->assertTrue($found);

        $hive = array('foo' => array('bar' => 'baz'));
        $ref = &$this->core->reference('foo.bar', true, $hive, $found);
        $ref = 'qux';

        $this->assertEquals('qux', $hive['foo']['bar']);
    }

    public function testDeReference()
    {
        $ref = &$this->core->reference('foo.bar.baz');
        $ref = 'qux';

        $ref = &$this->core->reference('obj');
        $ref = new \stdClass();
        $ref->foo = 'bar';

        $ref = &$this->core->reference('SESSION.foo');
        $ref = 'bar';

        $this->assertEquals('qux', $this->core->reference('foo.bar.baz'));
        $this->assertEquals('bar', $this->core->reference('obj.foo'));
        $this->assertEquals('bar', $this->core->reference('SESSION.foo'));

        $this->assertNull($this->core->deReference('foo.bar.baz')->reference('foo.bar.baz'));
        $this->assertNull($this->core->deReference('foo')->reference('foo'));
        $this->assertNull($this->core->deReference('obj.foo')->reference('obj.foo'));
        $this->assertNull($this->core->deReference('obj')->reference('obj'));
        $this->assertNull($this->core->deReference('SESSION.foo')->reference('SESSION.foo'));
        $this->assertEmpty($this->core->deReference('SESSION')->reference('SESSION'));

        $hive = array('foo' => array('bar' => 'baz'));
        $this->core->deReference('foo.bar', $hive);

        $this->assertEquals(array(), $hive['foo']);
    }

    public function testExists()
    {
        $this->assertFalse($this->core->exists('foo'));
        $this->assertTrue($this->core->exists('CLI'));
    }

    public function testGet()
    {
        $this->assertTrue($this->core->get('CLI'));
        $this->assertNull($this->core->get('foo'));
    }

    public function testSet()
    {
        $this->assertNull($this->core->set('CACHE', null)->get('CACHE'));
        $this->assertNull($this->core->set('LANGUAGE', null)->get('LANGUAGE'));
        $this->assertEquals('bar', $this->core->set('foo', 'bar')->get('foo'));
    }

    public function testClear()
    {
        $this->assertTrue($this->core->clear('CLI')->get('CLI'));
        $this->assertNull($this->core->set('foo', 'bar')->clear('foo')->get('foo'));
    }

    public function testAllExists()
    {
        $this->assertTrue($this->core->allExists('CLI,BASE'));
        $this->assertFalse($this->core->allExists('CLI,BASE,foo'));
    }

    public function testAllGet()
    {
        $expected = array('foo' => null, 'cli' => true, 'is_ajax' => '');

        $this->assertEquals($expected, $this->core->allGet('foo,CLI,AJAX', true, array('AJAX' => 'is_ajax')));
        $this->assertEquals(array('CLI' => true), $this->core->allGet('CLI'));
    }

    public function testAllSet()
    {
        $expected = array('foo' => 'bar', 'bar' => 'baz');

        $this->assertEquals($expected, $this->core->allSet($expected, 'foo.')->get('foo'));
        $this->assertEquals('bar', $this->core->get('foo.foo'));
    }

    public function testAllClear()
    {
        $set = array('foo' => 'bar', 'bar' => 'baz');

        $this->assertEmpty($this->core->set('foo', $set)->allClear('foo,bar', 'foo.')->get('foo'));
    }

    public function testCopy()
    {
        $this->assertTrue($this->core->copy('CLI', 'foo')->get('foo'));
    }

    public function testCut()
    {
        $this->assertEquals('bar', $this->core->set('foo', 'bar')->cut('foo'));
    }

    public function testPrepend()
    {
        $this->core->set('foo', 'bar');
        $this->core->set('bar', array('baz'));

        $this->assertEquals('foobar', $this->core->prepend('foo', 'foo')->get('foo'));
        $this->assertEquals(array('foo', 'baz'), $this->core->prepend('bar', 'foo')->get('bar'));
        $this->assertEquals(array('foo'), $this->core->prepend('baz', 'foo', true)->get('baz'));
    }

    public function testAppend()
    {
        $this->core->set('foo', 'bar');
        $this->core->set('bar', array('baz'));

        $this->assertEquals('barfoo', $this->core->append('foo', 'foo')->get('foo'));
        $this->assertEquals(array('baz', 'foo'), $this->core->append('bar', 'foo')->get('bar'));
        $this->assertEquals(array('foo'), $this->core->append('baz', 'foo', true)->get('baz'));
    }

    /**
     * @dataProvider createInstanceProvider
     */
    public function testCreateInstance($expected, $id, $rule = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->core->createInstance($id, $rule);

            return;
        }

        $this->assertInstanceOf($expected, $this->core->createInstance($id, $rule));
    }

    /**
     * @dataProvider ruleProvider
     */
    public function testRule($expected, $id, $rule)
    {
        $this->assertEquals($expected, $this->core->rule($id, $rule)->get('SERVICE_RULES.'.$id));
    }

    /**
     * @dataProvider serviceProvider
     */
    public function testService($expected, $id, $rule = null, $secondCall = null, $service = true)
    {
        if (!in_array($id, array('core', 'Fal\\Stick\\Core'))) {
            $this->core->rule($id, $rule);
        }

        $shared = isset($rule['shared']) ? $rule['shared'] : $service;
        $instance = $this->core->service($id);

        $this->assertInstanceOf($expected, $instance);

        if ($shared) {
            $this->assertSame($instance, $this->core->service($secondCall ?: $id));
        } else {
            $secondInstance = $this->core->service($secondCall ?: $id, $service);

            $this->assertInstanceOf($expected, $secondInstance);
            $this->assertNotSame($instance, $secondInstance);
        }
    }

    /**
     * @dataProvider callProvider
     */
    public function testCall($expected, $callback, $arguments = null)
    {
        $std = new \StdClass();
        $std->foo = 'bar';
        $this->core->rule('std_service', $std);

        $this->assertEquals($expected, $this->core->call($callback, $arguments));
    }

    public function testChain()
    {
        $this->assertEquals('foo', $this->core->chain(function (Core $core) {
            $core->set('foo', 'foo');
        })->get('foo'));
    }

    /**
     * @dataProvider grabProvider
     */
    public function testGrab($expected, $expression)
    {
        $callback = $this->core->grab($expression);

        $this->assertEquals($expected, $callback());
    }

    public function testRoute()
    {
        $this->core->route('GET /', 'foo');
        $this->core->route('GET foo /bar', 'foo');
        $this->core->route('POST foo', 'foo');

        $this->assertCount(2, $this->core->get('ROUTES'));
        $this->assertCount(2, $this->core->get('ROUTES./bar.all'));
        $this->assertCount(1, $this->core->get('ALIASES'));
    }

    /**
     * @dataProvider routeExceptionProvider
     */
    public function testRouteException($expected, $route)
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage($expected);

        $this->core->route($route, 'foo');
    }

    public function testController()
    {
        $this->core->controller('foo', array(
            'GET home /home' => 'home',
        ));

        $this->assertEquals('/home', $this->core->get('ALIASES.home'));
        $this->assertCount(1, $this->core->get('ROUTES'));
    }

    public function testRest()
    {
        $this->core->rest('home /home', 'foo');

        $this->assertEquals('/home', $this->core->get('ALIASES.home'));
        $this->assertEquals('/home/@item', $this->core->get('ALIASES.home_item'));
        $this->assertCount(2, $this->core->get('ROUTES'));
    }

    public function testRedirect()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /go-away', function () {
            return 'leaving';
        });
        $this->core->redirect('GET /home', '/go-away');
        $this->core->set('PATH', '/home');
        $this->core->run();

        $this->assertEquals('leaving', $this->core->get('OUTPUT'));
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testAlias($expected, $alias, $arguments = null, $exception = null)
    {
        $this->core->route('GET home /', 'foo');
        $this->core->route('GET foo /foo', 'foo');
        $this->core->route('GET bar /foo/@bar', 'foo');
        $this->core->route('GET baz /foo/@bar(\d+)', 'foo');
        $this->core->route('GET qux /foo/@bar(\d+)/@baz', 'foo');
        $this->core->route('GET quux /foo/@bar(\d+)/@baz/@qux*', 'foo');

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->core->alias($alias, $arguments);

            return;
        }

        $this->assertEquals($expected, $this->core->alias($alias, $arguments));
    }

    public function testAsset()
    {
        $this->core->set('ASSETS', array(
            'foo' => 'bar',
        ));
        $this->core->set('ASSETS_VERSION', 3);

        $this->assertEquals('/bar?3', $this->core->asset('foo'));
    }

    public function testPath()
    {
        $this->core->route('GET foo /bar/@baz', 'foo');

        $this->assertEquals('/bar/qux?foo=bar', $this->core->path('foo', array('baz' => 'qux'), 'foo=bar'));
        $this->assertEquals('/bar', $this->core->path('bar'));
    }

    public function testOn()
    {
        $this->core->on('foo', 'foo1');
        $this->core->on('foo', 'foo2', 1);
        $this->core->on('foo', 'foo3', 2);
        $this->core->on('foo', 'foo4');

        $expected = array(
            array('foo3', false, 2),
            array('foo2', false, 1),
            array('foo1', false, 0),
            array('foo4', false, 0),
        );

        $this->assertEquals($expected, $this->core->get('EVENTS.foo'));
    }

    public function testOff()
    {
        $this->core->on('foo', 'foo1');
        $this->core->off('foo');

        $this->assertCount(0, $this->core->get('EVENTS'));
    }

    public function testOne()
    {
        $this->assertEquals(array('foo1', true, 0), $this->core->one('foo', 'foo1')->get('EVENTS.foo.0'));
    }

    public function testDispatch()
    {
        $this->core->on('foo', function (Event $event) {
            $event->stopPropagation();
        });

        $event = new Event();
        $this->core->dispatch('foo', $event, true);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertCount(0, $this->core->get('EVENTS'));
    }

    public function testDispatchException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Handler is not callable: "foo"');

        $this->core->on('foo', 'bar');
        $this->core->dispatch('foo', new Event());
    }

    public function testLog()
    {
        $this->core->set('LOG_DIR', $dir = TEST_TEMP.'/test-log/');
        $pattern = $dir.'*.log';

        foreach (glob($pattern) as $file) {
            unlink($file);
        }

        $this->core->log(0, 'Emergency');
        $this->core->log(3, 'Error');
        $this->core->log(6, 'Info');
        // disable
        $this->core->set('LOG', false);
        $this->core->log(1, 'Alert');

        $files = glob($pattern);

        $this->assertCount(1, $files);

        $content = file_get_contents($files[0]);

        $this->assertContains('Emergency', $content);
        $this->assertContains('Error', $content);
        $this->assertNotContains('Info', $content);
        $this->assertNotContains('Alert', $content);
    }

    public function testLogFiles()
    {
        $this->assertCount(0, $this->core->logFiles());

        $this->core->set('LOG_DIR', $dir = TEST_TEMP.'/test-log/');
        $pattern = $dir.'*.log';

        foreach (glob($pattern) as $file) {
            unlink($file);
        }

        $this->core->log(0, 'Emergency');

        $this->assertCount(1, $this->core->logFiles());
        $this->assertCount(0, $this->core->logFiles('invalid date'));
        $this->assertCount(1, $this->core->logFiles('today'));
        $this->assertCount(0, $this->core->logFiles('yesterday'));
    }

    public function testCookie()
    {
        $this->core->cookie('foo');
        $cookies = $this->core->get('COOKIE_SET');

        $this->assertCount(1, $cookies);
        $this->assertEquals('setcookie', $cookies['foo']['call']);
        $this->assertEquals('foo', $cookies['foo']['arguments'][0]);
        $this->assertEquals(0, $cookies['foo']['arguments'][2]);
    }

    public function testCookieRemove()
    {
        $this->core->cookieRemove('foo');
        $cookies = $this->core->get('COOKIE_SET');

        $this->assertCount(1, $cookies);
        $this->assertEquals('setcookie', $cookies['foo']['call']);
        $this->assertEquals('foo', $cookies['foo']['arguments'][0]);
        $this->assertNotEquals(strtotime('today'), $cookies['foo']['arguments'][2]);
        $this->assertLessThan(strtotime('today'), $cookies['foo']['arguments'][2]);
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStatus($expected, $code, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->core->status($code);

            return;
        }

        $this->core->status($code);

        $this->assertEquals($code, $this->core->get('CODE'));
        $this->assertEquals($expected, $this->core->get('STATUS'));
    }

    /**
     * @dataProvider expireProvider
     */
    public function testExpire($expected, $seconds)
    {
        $headers = array_merge(array(
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
        ), $expected);

        $this->core->expire($seconds);

        $this->assertEquals($headers, array_keys($this->core->get('RESPONSE')));
    }

    public function testSendHeaders()
    {
        // nothing to do, as we are in CLI mode
        $this->core->sendHeaders();

        $this->core->set('CLI', false);
        $this->core->cookie('foo');
        $this->core->set('RESPONSE.foo', 'bar');
        $this->core->set('RESPONSE.bar', array('foo'));

        $this->core->sendHeaders();

        $this->assertTrue(true);
    }

    /**
     * @dataProvider sendContentProvider
     */
    public function testSendContent($expected, $output, $kbps = 0)
    {
        $this->expectOutputString($expected);

        $this->core->set('KBPS', $kbps);
        $this->core->set('OUTPUT', $output);
        $this->core->sendContent();
    }

    public function testSend()
    {
        $this->expectOutputString('foo');

        $this->core->set('OUTPUT', 'foo');
        $this->core->send();
    }

    /**
     * @dataProvider stringifyProvider
     */
    public function testStringify($expected, $argument)
    {
        $this->assertEquals($expected, $this->core->stringify($argument));
    }

    public function testTrace()
    {
        $expected = 'Fal\Stick\Test\CoreTest->testTrace()';
        $trace = $this->core->trace();

        $this->assertContains($expected, $trace);
    }

    /**
     * @dataProvider errorProvider
     */
    public function testError($expected, $status, $code, $message = null, $ajax = false)
    {
        $this->core->set('QUIET', true);
        $this->core->set('AJAX', $ajax);
        $this->core->error($code, $message);
        // second call
        $this->core->error($code);

        $this->assertEquals($code, $this->core->get('CODE'));
        $this->assertEquals($status, $this->core->get('STATUS'));
        $this->assertContains($expected, $this->core->get('OUTPUT'));
    }

    public function testErrorListener()
    {
        $this->core->set('QUIET', true);
        $this->core->on('core.error', function ($event) {
            $event->setResponse('error intercepted');
        });
        $this->core->error(404);

        $this->assertEquals('error intercepted', $this->core->get('OUTPUT'));
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($expected, $code, $hive)
    {
        $this->core->set('QUIET', true);
        $this->core->allSet($hive);
        $this->core->route('GET /', function () {
            return 'Home';
        });
        $this->core->route('GET /number/@number(\d+)', function ($number) {
            return 'Number '.$number;
        });
        $this->core->route('GET /split/@str', 'str_split');
        $this->core->route('GET /not-callable', 'foo');
        $this->core->route('GET /callable', function () {
            return function ($core) {
                $core->set('OUTPUT', 'output from callable');
            };
        });
        $this->core->run();

        $this->assertEquals($code, $this->core->get('CODE'));
        $this->assertContains($expected, $this->core->get('OUTPUT'));
    }

    public function testRunListener()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /', function (\DateTime $time) {
            return 'OK';
        });
        $this->core->on('core.controller_arguments', function ($event) {
            $event->setArguments(array(new \DateTime()));
        });
        $this->core->on('core.view', function ($event) {
            $event->setResult('VeryOK');
        });
        $this->core->on('core.finish', function ($event) {
            $event->getCore()->set('foo', 'VeryOK');
        });
        $this->core->run();

        $this->assertEquals('VeryOK', $this->core->get('OUTPUT'));
        $this->assertEquals('VeryOK', $this->core->get('foo'));
    }

    public function testRunIntercepted()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /', function () {
            return 'Home';
        });
        $this->core->on('core.request', function ($event) {
            $event->setResponse('Intercepted', 500);
        });
        $this->core->run();

        $this->assertEquals(500, $this->core->get('CODE'));
        $this->assertEquals('Intercepted', $this->core->get('OUTPUT'));
    }

    public function testRunInterceptedArguments()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /', function () {
            return 'Home';
        });
        $this->core->on('core.controller_arguments', function ($event) {
            $event->stopPropagation();
        });
        $this->core->run();

        $this->assertEquals(500, $this->core->get('CODE'));
        $this->assertContains('500 - Internal Server Error', $this->core->get('OUTPUT'));
    }

    public function testRunNoRoute()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No route defined');

        $this->core->run();
    }

    public function testRunCached()
    {
        $this->core->route('GET / 1', function () {
            return 'Home';
        });
        $this->core->set('QUIET', true);
        $this->core->set('CACHE', true);
        $this->core->cacheReset();
        $this->core->run();

        $this->assertEquals('Home', $this->core->get('OUTPUT'));

        // second call
        $this->core->allClear('SENT,OUTPUT');
        $this->core->run();
        $this->assertEquals('Home', $this->core->get('OUTPUT'));

        // not modified
        $this->core->allClear('SENT,OUTPUT');
        $this->core->set('HEADERS.If-Modified-Since', 'next month');
        $this->core->run();
        $this->assertEquals(304, $this->core->get('CODE'));
        $this->assertNull($this->core->get('OUTPUT'));

        $this->core->cacheReset();
    }

    public function testMock()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /', function () {
            return 'Home';
        });
        $this->core->route('GET foo /foo', function () {
            return 'Foo';
        });
        $this->core->route('GET bar /bar/@baz', function ($baz) {
            return 'Bar'.$baz;
        });
        $this->core->route('POST baz /baz', function (Core $core) {
            return 'Query: '.http_build_query($core->get('QUERY')).' and request data: '.http_build_query($core->get('REQUEST'));
        });
        $this->core->route('PUT qux /qux', function (Core $core) {
            return 'Request content: '.$core->get('CONTENT');
        });
        $this->core->route('GET /get/@all*', function ($all) {
            return 'get '.implode(' ', $all);
        });

        $this->core->mock('GET /');
        $this->assertEquals('Home', $this->core->get('OUTPUT'));

        $this->core->mock('GET / ajax');
        $this->assertEquals('Home', $this->core->get('OUTPUT'));

        $this->core->mock('GET foo');
        $this->assertEquals('Foo', $this->core->get('OUTPUT'));

        $this->core->mock('GET bar(baz=qux)');
        $this->assertEquals('Barqux', $this->core->get('OUTPUT'));

        $this->core->mock('POST baz?foo=bar', array('bar' => 'qux'));
        $this->assertEquals('Query: foo=bar and request data: bar=qux', $this->core->get('OUTPUT'));

        $this->core->mock('PUT qux', array('foo' => 'bar'));
        $this->assertEquals('Request content: foo=bar', $this->core->get('OUTPUT'));

        $this->core->mock('GET /get/all/things');
        $this->assertEquals('get all things', $this->core->get('OUTPUT'));
    }

    public function testMockException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid mock expression: "foo"');

        $this->core->mock('foo');
    }

    /**
     * @dataProvider rerouteProvider
     */
    public function testReroute($expected, $target, $cli = true)
    {
        $this->core->set('QUIET', true);
        $this->core->set('CLI', $cli);
        $this->core->route('GET /', function () {
            return 'home';
        });
        $this->core->route('GET /foo', function () {
            return 'foo';
        });
        $this->core->route('GET bar /bar/@bar', function ($bar) {
            return 'foo'.$bar;
        });
        $this->core->route('GET qux /qux', function () {
            return 'qux';
        });
        $this->core->route('GET /query', function (Core $core) {
            return 'Query: '.http_build_query($core->get('QUERY'));
        });

        $this->core->reroute($target);

        if ($cli) {
            $this->assertEquals($expected, $this->core->get('OUTPUT'));
        } else {
            $this->assertStringEndsWith($expected, $this->core->get('RESPONSE.Location'));
        }
    }

    public function testRerouteIntercepted()
    {
        $this->core->set('QUIET', true);
        $this->core->route('GET /foo', function () {
            return 'foo';
        });
        $this->core->on('core.reroute', function ($event) {
            $event->getCore()->set('rerouted_to', $event->getUrl());
            $event->stopPropagation();
        });
        $this->core->reroute('/foo');

        $this->assertStringEndsWith('/foo', $this->core->get('rerouted_to'));
    }

    public function testHandleError()
    {
        $this->core->set('QUIET', true);
        $this->core->handleError(E_ERROR, 'There is an error', __FILE__, __LINE__);

        $this->assertContains('There is an error', $this->core->get('OUTPUT'));
        $this->assertEquals(500, $this->core->get('CODE'));
    }

    public function testHandleException()
    {
        $this->core->set('QUIET', true);
        $this->core->handleException(new \RuntimeException('There is an exception'));

        $this->assertContains('There is an exception', $this->core->get('OUTPUT'));
        $this->assertEquals(500, $this->core->get('CODE'));
    }

    public function testShutdown()
    {
        $this->core->on('core.shutdown', function ($event) {
            $event->getCore()->set('shutting_down', true);
        });
        $this->core->shutdown(getcwd());

        $this->assertTrue($this->core->get('shutting_down'));
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->core['CLI']));
    }

    public function testOffsetGet()
    {
        $this->assertTrue($this->core['CLI']);
    }

    public function testOffsetSet()
    {
        $this->core['foo'] = 'bar';

        $this->assertEquals('bar', $this->core['foo']);
    }

    public function testOffsetUnset()
    {
        $this->core['foo'] = 'bar';
        unset($this->core['foo']);

        $this->assertNull($this->core['foo']);
    }

    /**
     * @dataProvider transProvider
     */
    public function testTrans($expected, $message, $parameters = null, $fallback = null)
    {
        $this->core->set('LANGUAGE', 'id-ID,fo');
        $this->core->set('LANGUAGE_LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals($expected, $this->core->trans($message, $parameters, $fallback));
    }

    /**
     * @dataProvider choiceProvider
     */
    public function testChoice($expected, $message, $count, $parameters = null, $fallback = null)
    {
        $this->core->set('LANGUAGE_LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals($expected, $this->core->choice($message, $count, $parameters, $fallback));
    }

    /**
     * @dataProvider transAltProvider
     */
    public function testTransAlt($expected, $messages, $parameters = null)
    {
        $this->core->set('LANGUAGE', 'id-ID');
        $this->core->set('LANGUAGE_LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals($expected, $this->core->transAlt($messages, $parameters));
    }

    public function testLanguageReferenceException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Message is not a string');

        $this->core->set('LANGUAGE_LOCALES', TEST_FIXTURE.'/dict/');
        $this->core->trans('there.is');
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheExists($dsn)
    {
        $this->assertFalse($this->core->set('CACHE', $dsn)->cacheExists('foo'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheGet($dsn)
    {
        $this->assertNull($this->core->set('CACHE', $dsn)->cacheGet('foo'));
    }

    public function testCacheGetFile()
    {
        $this->core->set('CACHE', 'filesystem='.TEST_TEMP.'/cache-file/');

        $this->core->cacheSet('foo', new CacheItem('foo', 1, microtime(true) - 1000));
        $this->assertNull($this->core->cacheGet('foo'));
    }

    /**
     * @dataProvider cacheSetProvider
     */
    public function testCacheSet($expected, $value, $removed, $dsn)
    {
        $this->assertEquals($expected, $this->core->set('CACHE', $dsn)->cacheSet('foo', new CacheItem('foo')));

        if ($expected) {
            $this->assertTrue($this->core->cacheExists('foo'));
        }

        $item = $this->core->cacheGet('foo');
        $this->assertEquals($value, $value ? $item->getValue() : $item);
        $this->assertEquals($removed, $this->core->cacheClear('foo'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheClear($dsn)
    {
        $this->assertFalse($this->core->set('CACHE', $dsn)->cacheClear('foo'));
    }

    /**
     * @dataProvider cacheResetProvider
     */
    public function testCacheReset($expected, $affected, $dsn)
    {
        $this->assertEquals($expected, $this->core->set('CACHE', $dsn)->cacheSet('foo', new CacheItem('foo')));
        $this->assertEquals($affected, $this->core->cacheReset());
        $this->core->cacheClear('foo');
    }

    /**
     * @dataProvider castProvider
     */
    public function testCast($expected, $val)
    {
        $this->assertEquals($expected, $this->core->cast($val));
    }

    public function testConfig()
    {
        $this->core->config(TEST_FIXTURE.'/config/config.ini;'.TEST_FIXTURE.'/config/not-exists.ini');

        $this->assertEquals('foo', $this->core->get('config'));
        $this->assertEquals('baz', $this->core->get('bar'));
        $this->assertEquals('bar', $this->core->get('my_section.sub.foo'));
        $this->assertEquals('foo', $this->core->get('subconfig'));
        $this->assertEquals(array(
            'GET' => array('foo', 'home', 1, 2),
            'POST' => array('bar', 'home', 0, 0),
        ), $this->core->get('ROUTES./.all'));
        $this->assertEquals(array(
            'GET' => array('foo->all', 'rest', 0, 0),
            'POST' => array('foo->create', 'rest', 0, 0),
        ), $this->core->get('ROUTES./rest.all'));
        $this->assertEquals(array(
            'GET' => array('foo->get', 'rest_item', 0, 0),
            'PUT' => array('foo->put', 'rest_item', 0, 0),
            'DELETE' => array('foo->delete', 'rest_item', 0, 0),
        ), $this->core->get('ROUTES./rest/@item.all'));
        $this->assertEquals('old_homepage', $this->core->get('ROUTES./old-homepage.all.GET.1'));

        // this run will resulting error, because home is not callable
        $this->core->set('QUIET', true);
        $this->core->set('PATH', '/old-homepage');
        $this->core->run();

        $this->assertEquals(500, $this->core->get('CODE'));
        $this->assertContains('Internal Server Error', $this->core->get('OUTPUT'));
    }

    /**
     * @dataProvider camelCaseProvider
     */
    public function testCamelCase($expected, $text)
    {
        $this->assertEquals($expected, Core::camelCase($text));
    }

    /**
     * @dataProvider snakeCaseProvider
     */
    public function testSnakeCase($expected, $text)
    {
        $this->assertEquals($expected, Core::snakeCase($text));
    }

    /**
     * @dataProvider classNameProvider
     */
    public function testClassName($expected, $class)
    {
        $this->assertEquals($expected, Core::className($class));
    }

    public function normalizeUploadFilesProvider()
    {
        return array(
            array(array(), null),
            array(
                array('foo' => array(array('name' => 'bar'))),
                array('foo' => array('name' => 'bar')),
            ),
            array(
                array('foo' => array(array('name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => null))),
                array('foo' => array('name' => array('bar'))),
            ),
        );
    }

    public function initializeProvider()
    {
        return array(
            array('/', 'PATH', array(
                'SCRIPT_NAME' => '/foo.php',
                'REQUEST_URI' => '/foo.php',
            )),
            array(80, 'PORT', array(
            )),
            array(1000, 'PORT', array(
                'HTTP_X_FORWARDED_PORT' => 1000,
            )),
            array(1000, 'PORT', array(
                'SERVER_PORT' => 1000,
            )),
            array('1.1.1.1', 'IP', array(
                'HTTP_X_CLIENT_IP' => '1.1.1.1',
            )),
            array('1.1.1.1', 'IP', array(
                'HTTP_X_FORWARDED_FOR' => '1.1.1.1',
            )),
            array('1.1.1.1', 'IP', array(
                'REMOTE_ADDR' => '1.1.1.1',
            )),
            array('', 'IP', array(
            )),
            array(true, 'SECURE', array(
                'HTTP_X_FORWARDED_PROTO' => 'https',
            )),
            array(true, 'SECURE', array(
                'HTTPS' => 'on',
            )),
            array(false, 'SECURE', array(
            )),
        );
    }

    public function overrideRequestMethodProvider()
    {
        return array(
            array('FOO', array(
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'foo',
            )),
            array('FOO', array(
                'REQUEST_METHOD' => 'POST',
            ), array('_method' => 'foo')),
        );
    }

    public function emulateCliRequestProvider()
    {
        return array(
            array('/', null, null),
            array('/foo', null, array(
                'argv' => array(null, '/foo'),
            )),
            array('/foo', array('bar' => 'baz', 'qux' => ''), array(
                'argv' => array(null, '/foo?bar=baz&qux'),
            )),
            array('/foo/bar', array('f' => '', 'o' => '', 'bar' => 'baz'), array(
                'argv' => array(null, 'foo', 'bar', '-fo', '--bar=baz'),
            )),
        );
    }

    public function createInstanceProvider()
    {
        return array(
            array('Fixture\\FooService', 'Fixture\\FooService'),
            array(
                'Fixture\\BarService',
                'bar',
                array(
                    'use' => 'Fixture\\BarService',
                    'arguments' => array('name' => 'bar'),
                ),
            ),
            array(
                'Fixture\\FooService',
                'Fixture\\FooService',
                array(
                    'boot' => function ($instance, $core) {},
                ),
            ),
            array(
                'Fixture\\FooService',
                'Fixture\\FooService',
                array(
                    'factory' => function () {
                        return new \Fixture\FooService();
                    },
                ),
            ),
            array(
                'Factory should return instance of Fixture\\FooService (foo)',
                'foo',
                array(
                    'class' => 'Fixture\\FooService',
                    'factory' => function () {
                        return new \DateTime();
                    },
                ),
                'LogicException',
            ),
            array(
                'Cannot instantiate Fixture\\FooInterface (Fixture\\FooInterface)',
                'Fixture\\FooInterface',
                null,
                'LogicException',
            ),
        );
    }

    public function ruleProvider()
    {
        return array(
            array(array('class' => 'foo', 'shared' => true), 'foo', null),
            array(array('class' => 'foo', 'shared' => false, false), 'foo', false),
            array(array('class' => 'foo', 'shared' => false), 'foo', array('shared' => false)),
            array(array('factory' => 'trim', 'class' => 'foo', 'shared' => true), 'foo', 'trim'),
            array(array('class' => 'DateTime', 'shared' => true), 'foo', 'DateTime'),
            array(array('class' => 'DateTime', 'shared' => true), 'foo', new \DateTime()),
        );
    }

    public function serviceProvider()
    {
        return array(
            array('Fal\\Stick\\Core', 'core'),
            array('Fal\\Stick\\Core', 'Fal\\Stick\\Core'),
            array('Fixture\\FooService', 'Fixture\\FooService'),
            array('Fixture\\FooService', 'foo', 'Fixture\\FooService', 'Fixture\\FooService'),
            array('Fixture\\FooService', 'Fixture\\FooService', null, null, false),
            array('Fixture\\FooService', 'foo', array('class' => 'Fixture\\FooService', 'shared' => false), 'Fixture\\FooService'),
        );
    }

    public function callProvider()
    {
        $std = new \StdClass();
        $std->foo = 'bar';

        return array(
            array('foo', 'trim', array('foo')),
            array('baz class instance name', array(new \Fixture\BazClass(), 'getInstanceName')),
            array('baz class static name', array('Fixture\\BazClass', 'getStaticName')),
            array('foo bar baz qux quux', 'implode', array(' ', array('foo bar baz qux quux'))),
            array('success', function (\DateTime $datetime) {
                return 'success';
            }),
            array('success', function (\DateTime $datetime) {
                return 'success';
            }, array('DateTime')),
            array('success', function (\StdClass $std) {
                return isset($std->foo) && 'bar' === $std->foo ? 'success' : 'error';
            }, array($std)),
            array('success', function ($cli, \StdClass $std, $false = false) {
                return $cli && isset($std->foo) && 'bar' === $std->foo && false === $false ? 'success' : 'error';
            }, array('cli' => '%CLI%', '%std_service%')),
            array('success', function ($true = true) {
                return $true ? 'success' : 'error';
            }),
            array('success', function ($foo) {
                return 'success';
            }, array('bar' => 'foo')),
        );
    }

    public function grabProvider()
    {
        return array(
            array(phpversion(), 'phpversion'),
            array('baz class instance name', 'Fixture\\BazClass->getInstanceName'),
            array('baz class static name', 'Fixture\\BazClass::getStaticName'),
        );
    }

    public function routeExceptionProvider()
    {
        return array(
            array('Invalid route: "foo"', 'foo'),
            array('Route not exists: "foo"', 'GET foo'),
        );
    }

    public function aliasProvider()
    {
        return array(
            array('/', 'home'),
            array('/foo', 'foo'),
            array('/foo/any', 'bar', array('bar' => 'any')),
            array('/foo/1', 'baz', array('bar' => 1)),
            array('/foo/1/any', 'qux', array('bar' => 1, 'baz' => 'any')),
            array('/foo/1/any', 'qux', 'bar=1&baz=any'),
            array('/foo/1/any/take/all', 'quux', array('bar' => 1, 'baz' => 'any', 'take', 'all')),
            array('/unknown', 'unknown'),
            array('Parameter "bar" should be provided', 'bar', null, 'LogicException'),
            array('Parameter "bar" is not valid, given: "baz"', 'baz', array('bar' => 'baz'), 'LogicException'),
        );
    }

    public function statusProvider()
    {
        return array(
            array('OK', 200),
            array('Forbidden', 403),
            array('Internal Server Error', 500),
            array('Unsupported HTTP code: 900', 900, 'LogicException'),
        );
    }

    public function expireProvider()
    {
        return array(
            array(array(
                'Pragma',
                'Cache-Control',
                'Expires',
            ), 0),
            array(array(
                'Cache-Control',
                'Expires',
                'Last-Modified',
            ), 1),
        );
    }

    public function sendContentProvider()
    {
        return array(
            array('foo', 'foo'),
            array('foo', 'foo', 1),
            array('', array(), 0),
        );
    }

    public function stringifyProvider()
    {
        return array(
            array('Object(stdClass)', new \stdClass()),
            array("['foo', 'bar', Object(stdClass)]", array('foo', 'bar', new \stdClass())),
            array("'foo'", 'foo'),
            array(1, 1),
            array('true', true),
            array('NULL', null),
        );
    }

    public function errorProvider()
    {
        return array(
            array('GET / (404 Not Found)', 'Not Found', 404),
            array('Ajax request not found', 'Not Found', 404, 'Ajax request not found', true),
            array('Something wrong', 'Internal Server Error', 500, 'Something wrong'),
        );
    }

    public function runProvider()
    {
        return array(
            array('Home', 200, array(
            )),
            array('Number 1', 200, array(
                'PATH' => '/number/1',
            )),
            array('Number 1000', 200, array(
                'PATH' => '/number/1000',
            )),
            array('Not Found', 404, array(
                'PATH' => '/number/nan',
            )),
            array('a', 200, array(
                'PATH' => '/split/abcdef',
            )),
            array('Internal Server Error', 500, array(
                'PATH' => '/not-callable',
            )),
            array('output from callable', 200, array(
                'PATH' => '/callable',
            )),
            array('Forbidden', 403, array(
                'VERB' => 'POST',
            )),
        );
    }

    public function rerouteProvider()
    {
        return array(
            array('home', '/'),
            array('foo', '/foo'),
            array('foobaz', '/bar/baz'),
            array('fooqux', '/bar/qux'),
            array('foobar', array('bar', array('bar' => 'bar'))),
            array('foobar', 'bar(bar=bar)'),
            array('Query: foo=bar&baz=', '/query?foo=bar&baz'),
            array('home', null),
            array('qux', 'qux'),
            array('/bar/qux', '/bar/qux', false),
        );
    }

    public function transProvider()
    {
        return array(
            array('foo', 'foo'),
            array('Sebuah bendera nasional', 'a_flag'),
            array('There is a blueberry', 'there.is.one.blueberry'),
            array('Ada sebuah apel', 'there.is.one.apple'),
            array('Ada sebuah jeruk', 'there.is.one.orange'),
            array('Ada sebuah mangga', 'there.is.one.mango'),
            array('Ada sebuah strawberi', 'there.is.one.fruit', array('{fruit}' => 'strawberi')),
            array('Tidak ada buah yang diinginkan', 'there.is.one.pineaplle', null, 'Tidak ada buah yang diinginkan'),
        );
    }

    public function choiceProvider()
    {
        return array(
            array('foo', 'foo', 0),
            array('There is no apple', 'apples', 0),
            array('There is one apple', 'apples', 1),
            array('There is 2 apples', 'apples', 2),
            array('There is 99 apples', 'apples', 99),
            array('There is no fruits', 'fruits', 0, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('There is apple, orange and mango', 'fruits', 1, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('There is lots of apple, orange and mango', 'fruits', 2, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
        );
    }

    public function transAltProvider()
    {
        return array(
            array(
                'Ada sebuah apel',
                array('there.is.one.apple'),
            ),
            array(
                'Tidak ada buah yang diinginkan',
                array('there.is.one.melon', 'Tidak ada buah yang diinginkan'),
            ),
            array(
                'Ada sebuah melon',
                array('there.is.one.fruit'),
                array('{fruit}' => 'melon'),
            ),
        );
    }

    public function cacheProvider()
    {
        return array(
            array(null),
            array('apc'),
            array('apcu'),
            array('filesystem='.TEST_TEMP.'/cache-test/'),
            array('memcache=127.0.0.1'),
            array('memcached=127.0.0.1'),
            array('redis=127.0.0.1'),
            array(true),
        );
    }

    public function cacheSetProvider()
    {
        return array(
            array(false, null, false, null),
            array(true, 'foo', true, 'apc'),
            array(true, 'foo', true, 'apcu'),
            array(true, 'foo', true, 'filesystem='.TEST_TEMP.'/cache-test/'),
            array(true, 'foo', true, 'memcache=127.0.0.1'),
            array(true, 'foo', true, 'memcached=127.0.0.1'),
            array(true, 'foo', true, 'redis=127.0.0.1'),
            array(true, 'foo', true, 'redis=127.0.0.1:6379:1'),
            array(true, 'foo', true, true),
            // host/db error, fallback to automatic selection
            array(true, 'foo', true, 'memcache=foo'),
            array(true, 'foo', true, 'redis=foo'),
        );
    }

    public function cacheResetProvider()
    {
        return array(
            array(null, 0, null),
            array(true, 1, 'apc'),
            array(true, 1, 'apcu'),
            array(true, 1, 'filesystem='.TEST_TEMP.'/cache-test/'),
            array(true, 1, 'memcache=127.0.0.1'),
            array(true, 1, 'memcached=127.0.0.1'),
            array(true, 1, 'redis=127.0.0.1'),
            array(true, 1, true),
        );
    }

    public function castProvider()
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

    public function classNameProvider()
    {
        return array(
            array('CoreTest', $this),
            array('CoreTest', 'Fal\\Stick\\Test\CoreTest'),
            array('DateTime', new \DateTime()),
            array('DateTime', 'DateTime'),
        );
    }

    public function camelCaseProvider()
    {
        return array(
            array('snakeCase', 'snake_case'),
            array('snakeCase', 'SNAKE_CASE'),
            array('snakeCaseText', 'snake_case_text'),
            array('snakeCaseText', 'Snake_case_text'),
        );
    }

    public function snakeCaseProvider()
    {
        return array(
            array('camel_case', 'camelCase'),
            array('camel_case_text', 'camelCaseText'),
            array('camel_case_text', 'CamelCaseText'),
        );
    }
}
