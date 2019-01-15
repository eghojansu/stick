<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\CacheItem;
use Fal\Stick\Core;
use Fal\Stick\Event;
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
        $expected = [
            'Content-Length'   => 0,
            'X-Requested-With' => 'Foo',
        ];
        $server = [
            'CONTENT_LENGTH'        => 0,
            'HTTP_X_REQUESTED_WITH' => 'Foo',
        ];

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
        $this->assertEquals(['foo', 'bar'], Core::split(['foo', 'bar']));
        $this->assertEquals([], Core::split(null));
        $this->assertEquals(['foo', 'bar'], Core::split('foo,bar'));
        $this->assertEquals(['foo', 'bar'], Core::split('foo:bar', ':'));
        $this->assertEquals(['foo', 'bar', 'baz'], Core::split('foo, bar|baz '));
    }

    public function testJoin()
    {
        $this->assertEquals('foo,bar', Core::join(['foo', 'bar']));
        $this->assertEquals('foo:bar', Core::join(['foo', 'bar'], ':'));
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

        $hive = ['foo' => ['bar' => 'baz']];
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

        $hive = ['foo' => ['bar' => 'baz']];
        $this->core->deReference('foo.bar', $hive);

        $this->assertEquals([], $hive['foo']);
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
        $expected = ['foo' => null, 'cli' => true, 'is_ajax' => ''];

        $this->assertEquals($expected, $this->core->allGet('foo,CLI,AJAX', true, ['AJAX' => 'is_ajax']));
        $this->assertEquals(['CLI' => true], $this->core->allGet('CLI'));
    }

    public function testAllSet()
    {
        $expected = ['foo' => 'bar', 'bar' => 'baz'];

        $this->assertEquals($expected, $this->core->allSet($expected, 'foo.')->get('foo'));
        $this->assertEquals('bar', $this->core->get('foo.foo'));
    }

    public function testAllClear()
    {
        $set = ['foo' => 'bar', 'bar' => 'baz'];

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
        $this->core->set('bar', ['baz']);

        $this->assertEquals('foobar', $this->core->prepend('foo', 'foo')->get('foo'));
        $this->assertEquals(['foo', 'baz'], $this->core->prepend('bar', 'foo')->get('bar'));
        $this->assertEquals(['foo'], $this->core->prepend('baz', 'foo', true)->get('baz'));
    }

    public function testAppend()
    {
        $this->core->set('foo', 'bar');
        $this->core->set('bar', ['baz']);

        $this->assertEquals('barfoo', $this->core->append('foo', 'foo')->get('foo'));
        $this->assertEquals(['baz', 'foo'], $this->core->append('bar', 'foo')->get('bar'));
        $this->assertEquals(['foo'], $this->core->append('baz', 'foo', true)->get('baz'));
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
        if (!in_array($id, ['core', 'Fal\\Stick\\Core'])) {
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
        $this->core->controller('foo', [
            'GET home /home' => 'home',
        ]);

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
        $this->core->set('ASSETS', [
            'foo' => 'bar',
        ]);
        $this->core->set('ASSETS_VERSION', 3);

        $this->assertEquals('/bar?3', $this->core->asset('foo'));
    }

    public function testPath()
    {
        $this->core->route('GET foo /bar/@baz', 'foo');

        $this->assertEquals('/bar/qux?foo=bar', $this->core->path('foo', ['baz' => 'qux'], 'foo=bar'));
        $this->assertEquals('/bar', $this->core->path('bar'));
    }

    public function testOn()
    {
        $this->core->on('foo', 'foo1');
        $this->core->on('foo', 'foo2', 1);
        $this->core->on('foo', 'foo3', 2);
        $this->core->on('foo', 'foo4');

        $expected = [
            ['foo3', false, 2],
            ['foo2', false, 1],
            ['foo1', false, 0],
            ['foo4', false, 0],
        ];

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
        $this->assertEquals(['foo1', true, 0], $this->core->one('foo', 'foo1')->get('EVENTS.foo.0'));
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
        $headers = array_merge([
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
        ], $expected);

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
        $this->core->set('RESPONSE.bar', ['foo']);

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
            $event->setArguments([new \DateTime()]);
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

        $this->core->mock('POST baz?foo=bar', ['bar' => 'qux']);
        $this->assertEquals('Query: foo=bar and request data: bar=qux', $this->core->get('OUTPUT'));

        $this->core->mock('PUT qux', ['foo' => 'bar']);
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
        $this->assertEquals([
            'GET'  => ['foo', 'home', 1, 2],
            'POST' => ['bar', 'home', 0, 0],
        ], $this->core->get('ROUTES./.all'));
        $this->assertEquals([
            'GET'  => ['foo->all', 'rest', 0, 0],
            'POST' => ['foo->create', 'rest', 0, 0],
        ], $this->core->get('ROUTES./rest.all'));
        $this->assertEquals([
            'GET'    => ['foo->get', 'rest_item', 0, 0],
            'PUT'    => ['foo->put', 'rest_item', 0, 0],
            'DELETE' => ['foo->delete', 'rest_item', 0, 0],
        ], $this->core->get('ROUTES./rest/@item.all'));
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
        return [
            [[], null],
            [
                ['foo' => [['name' => 'bar']]],
                ['foo' => ['name' => 'bar']],
            ],
            [
                ['foo' => [['name' => 'bar', 'type' => null, 'size' => null, 'tmp_name' => null, 'error' => null]]],
                ['foo' => ['name' => ['bar']]],
            ],
        ];
    }

    public function initializeProvider()
    {
        return [
            ['/', 'PATH', [
                'SCRIPT_NAME' => '/foo.php',
                'REQUEST_URI' => '/foo.php',
            ]],
            [80, 'PORT', [
            ]],
            [1000, 'PORT', [
                'HTTP_X_FORWARDED_PORT' => 1000,
            ]],
            [1000, 'PORT', [
                'SERVER_PORT' => 1000,
            ]],
            ['1.1.1.1', 'IP', [
                'HTTP_X_CLIENT_IP' => '1.1.1.1',
            ]],
            ['1.1.1.1', 'IP', [
                'HTTP_X_FORWARDED_FOR' => '1.1.1.1',
            ]],
            ['1.1.1.1', 'IP', [
                'REMOTE_ADDR' => '1.1.1.1',
            ]],
            ['', 'IP', [
            ]],
            [true, 'SECURE', [
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ]],
            [true, 'SECURE', [
                'HTTPS' => 'on',
            ]],
            [false, 'SECURE', [
            ]],
        ];
    }

    public function overrideRequestMethodProvider()
    {
        return [
            ['FOO', [
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'foo',
            ]],
            ['FOO', [
                'REQUEST_METHOD' => 'POST',
            ], ['_method' => 'foo']],
        ];
    }

    public function emulateCliRequestProvider()
    {
        return [
            ['/', null, null],
            ['/foo', null, [
                'argv' => [null, '/foo'],
            ]],
            ['/foo', ['bar' => 'baz', 'qux' => ''], [
                'argv' => [null, '/foo?bar=baz&qux'],
            ]],
            ['/foo/bar', ['f' => '', 'o' => '', 'bar' => 'baz'], [
                'argv' => [null, 'foo', 'bar', '-fo', '--bar=baz'],
            ]],
        ];
    }

    public function createInstanceProvider()
    {
        return [
            ['Fixture\\FooService', 'Fixture\\FooService'],
            [
                'Fixture\\BarService',
                'bar',
                [
                    'use'       => 'Fixture\\BarService',
                    'arguments' => ['name' => 'bar'],
                ],
            ],
            [
                'Fixture\\FooService',
                'Fixture\\FooService',
                [
                    'boot' => function ($instance, $core) {
                    },
                ],
            ],
            [
                'Fixture\\FooService',
                'Fixture\\FooService',
                [
                    'factory' => function () {
                        return new \Fixture\FooService();
                    },
                ],
            ],
            [
                'Factory should return instance of Fixture\\FooService (foo)',
                'foo',
                [
                    'class'   => 'Fixture\\FooService',
                    'factory' => function () {
                        return new \DateTime();
                    },
                ],
                'LogicException',
            ],
            [
                'Cannot instantiate Fixture\\FooInterface (Fixture\\FooInterface)',
                'Fixture\\FooInterface',
                null,
                'LogicException',
            ],
        ];
    }

    public function ruleProvider()
    {
        return [
            [['class' => 'foo', 'shared' => true], 'foo', null],
            [['class' => 'foo', 'shared' => false, false], 'foo', false],
            [['class' => 'foo', 'shared' => false], 'foo', ['shared' => false]],
            [['factory' => 'trim', 'class' => 'foo', 'shared' => true], 'foo', 'trim'],
            [['class' => 'DateTime', 'shared' => true], 'foo', 'DateTime'],
            [['class' => 'DateTime', 'shared' => true], 'foo', new \DateTime()],
        ];
    }

    public function serviceProvider()
    {
        return [
            ['Fal\\Stick\\Core', 'core'],
            ['Fal\\Stick\\Core', 'Fal\\Stick\\Core'],
            ['Fixture\\FooService', 'Fixture\\FooService'],
            ['Fixture\\FooService', 'foo', 'Fixture\\FooService', 'Fixture\\FooService'],
            ['Fixture\\FooService', 'Fixture\\FooService', null, null, false],
            ['Fixture\\FooService', 'foo', ['class' => 'Fixture\\FooService', 'shared' => false], 'Fixture\\FooService'],
        ];
    }

    public function callProvider()
    {
        $std = new \StdClass();
        $std->foo = 'bar';

        return [
            ['foo', 'trim', ['foo']],
            ['baz class instance name', [new \Fixture\BazClass(), 'getInstanceName']],
            ['baz class static name', ['Fixture\\BazClass', 'getStaticName']],
            ['foo bar baz qux quux', 'implode', [' ', ['foo bar baz qux quux']]],
            ['success', function (\DateTime $datetime) {
                return 'success';
            }],
            ['success', function (\DateTime $datetime) {
                return 'success';
            }, ['DateTime']],
            ['success', function (\StdClass $std) {
                return isset($std->foo) && 'bar' === $std->foo ? 'success' : 'error';
            }, [$std]],
            ['success', function ($cli, \StdClass $std, $false = false) {
                return $cli && isset($std->foo) && 'bar' === $std->foo && false === $false ? 'success' : 'error';
            }, ['cli' => '%CLI%', '%std_service%']],
            ['success', function ($true = true) {
                return $true ? 'success' : 'error';
            }],
            ['success', function ($foo) {
                return 'success';
            }, ['bar' => 'foo']],
        ];
    }

    public function grabProvider()
    {
        return [
            [phpversion(), 'phpversion'],
            ['baz class instance name', 'Fixture\\BazClass->getInstanceName'],
            ['baz class static name', 'Fixture\\BazClass::getStaticName'],
        ];
    }

    public function routeExceptionProvider()
    {
        return [
            ['Invalid route: "foo"', 'foo'],
            ['Route not exists: "foo"', 'GET foo'],
        ];
    }

    public function aliasProvider()
    {
        return [
            ['/', 'home'],
            ['/foo', 'foo'],
            ['/foo/any', 'bar', ['bar' => 'any']],
            ['/foo/1', 'baz', ['bar' => 1]],
            ['/foo/1/any', 'qux', ['bar' => 1, 'baz' => 'any']],
            ['/foo/1/any', 'qux', 'bar=1&baz=any'],
            ['/foo/1/any/take/all', 'quux', ['bar' => 1, 'baz' => 'any', 'take', 'all']],
            ['/unknown', 'unknown'],
            ['Parameter "bar" should be provided', 'bar', null, 'LogicException'],
            ['Parameter "bar" is not valid, given: "baz"', 'baz', ['bar' => 'baz'], 'LogicException'],
        ];
    }

    public function statusProvider()
    {
        return [
            ['OK', 200],
            ['Forbidden', 403],
            ['Internal Server Error', 500],
            ['Unsupported HTTP code: 900', 900, 'LogicException'],
        ];
    }

    public function expireProvider()
    {
        return [
            [[
                'Pragma',
                'Cache-Control',
                'Expires',
            ], 0],
            [[
                'Cache-Control',
                'Expires',
                'Last-Modified',
            ], 1],
        ];
    }

    public function sendContentProvider()
    {
        return [
            ['foo', 'foo'],
            ['foo', 'foo', 1],
            ['', [], 0],
        ];
    }

    public function stringifyProvider()
    {
        return [
            ['Object(stdClass)', new \stdClass()],
            ["['foo', 'bar', Object(stdClass)]", ['foo', 'bar', new \stdClass()]],
            ["'foo'", 'foo'],
            [1, 1],
            ['true', true],
            ['NULL', null],
        ];
    }

    public function errorProvider()
    {
        return [
            ['GET / (404 Not Found)', 'Not Found', 404],
            ['Ajax request not found', 'Not Found', 404, 'Ajax request not found', true],
            ['Something wrong', 'Internal Server Error', 500, 'Something wrong'],
        ];
    }

    public function runProvider()
    {
        return [
            ['Home', 200, [
            ]],
            ['Number 1', 200, [
                'PATH' => '/number/1',
            ]],
            ['Number 1000', 200, [
                'PATH' => '/number/1000',
            ]],
            ['Not Found', 404, [
                'PATH' => '/number/nan',
            ]],
            ['a', 200, [
                'PATH' => '/split/abcdef',
            ]],
            ['Internal Server Error', 500, [
                'PATH' => '/not-callable',
            ]],
            ['output from callable', 200, [
                'PATH' => '/callable',
            ]],
            ['Forbidden', 403, [
                'VERB' => 'POST',
            ]],
        ];
    }

    public function rerouteProvider()
    {
        return [
            ['home', '/'],
            ['foo', '/foo'],
            ['foobaz', '/bar/baz'],
            ['fooqux', '/bar/qux'],
            ['foobar', ['bar', ['bar' => 'bar']]],
            ['foobar', 'bar(bar=bar)'],
            ['Query: foo=bar&baz=', '/query?foo=bar&baz'],
            ['home', null],
            ['qux', 'qux'],
            ['/bar/qux', '/bar/qux', false],
        ];
    }

    public function transProvider()
    {
        return [
            ['foo', 'foo'],
            ['Sebuah bendera nasional', 'a_flag'],
            ['There is a blueberry', 'there.is.one.blueberry'],
            ['Ada sebuah apel', 'there.is.one.apple'],
            ['Ada sebuah jeruk', 'there.is.one.orange'],
            ['Ada sebuah mangga', 'there.is.one.mango'],
            ['Ada sebuah strawberi', 'there.is.one.fruit', ['{fruit}' => 'strawberi']],
            ['Tidak ada buah yang diinginkan', 'there.is.one.pineaplle', null, 'Tidak ada buah yang diinginkan'],
        ];
    }

    public function choiceProvider()
    {
        return [
            ['foo', 'foo', 0],
            ['There is no apple', 'apples', 0],
            ['There is one apple', 'apples', 1],
            ['There is 2 apples', 'apples', 2],
            ['There is 99 apples', 'apples', 99],
            ['There is no fruits', 'fruits', 0, [
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            ]],
            ['There is apple, orange and mango', 'fruits', 1, [
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            ]],
            ['There is lots of apple, orange and mango', 'fruits', 2, [
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            ]],
        ];
    }

    public function transAltProvider()
    {
        return [
            [
                'Ada sebuah apel',
                ['there.is.one.apple'],
            ],
            [
                'Tidak ada buah yang diinginkan',
                ['there.is.one.melon', 'Tidak ada buah yang diinginkan'],
            ],
            [
                'Ada sebuah melon',
                ['there.is.one.fruit'],
                ['{fruit}' => 'melon'],
            ],
        ];
    }

    public function cacheProvider()
    {
        return [
            [null],
            ['apc'],
            ['apcu'],
            ['filesystem='.TEST_TEMP.'/cache-test/'],
            ['memcache=127.0.0.1'],
            ['memcached=127.0.0.1'],
            ['redis=127.0.0.1'],
            [true],
        ];
    }

    public function cacheSetProvider()
    {
        return [
            [false, null, false, null],
            [true, 'foo', true, 'apc'],
            [true, 'foo', true, 'apcu'],
            [true, 'foo', true, 'filesystem='.TEST_TEMP.'/cache-test/'],
            [true, 'foo', true, 'memcache=127.0.0.1'],
            [true, 'foo', true, 'memcached=127.0.0.1'],
            [true, 'foo', true, 'redis=127.0.0.1'],
            [true, 'foo', true, 'redis=127.0.0.1:6379:1'],
            [true, 'foo', true, true],
            // host/db error, fallback to automatic selection
            [true, 'foo', true, 'memcache=foo'],
            [true, 'foo', true, 'redis=foo'],
        ];
    }

    public function cacheResetProvider()
    {
        return [
            [null, 0, null],
            [true, 1, 'apc'],
            [true, 1, 'apcu'],
            [true, 1, 'filesystem='.TEST_TEMP.'/cache-test/'],
            [true, 1, 'memcache=127.0.0.1'],
            [true, 1, 'memcached=127.0.0.1'],
            [true, 1, 'redis=127.0.0.1'],
            [true, 1, true],
        ];
    }

    public function castProvider()
    {
        return [
            [1, '1'],
            [1.2, '1.2'],
            ['foo', 'foo'],
            [null, 'null'],
            [true, 'true'],
            [false, 'false'],
            [['foo'], ['foo']],
        ];
    }

    public function classNameProvider()
    {
        return [
            ['CoreTest', $this],
            ['CoreTest', 'Fal\\Stick\\Test\CoreTest'],
            ['DateTime', new \DateTime()],
            ['DateTime', 'DateTime'],
        ];
    }

    public function camelCaseProvider()
    {
        return [
            ['snakeCase', 'snake_case'],
            ['snakeCase', 'SNAKE_CASE'],
            ['snakeCaseText', 'snake_case_text'],
            ['snakeCaseText', 'Snake_case_text'],
        ];
    }

    public function snakeCaseProvider()
    {
        return [
            ['camel_case', 'camelCase'],
            ['camel_case_text', 'camelCaseText'],
            ['camel_case_text', 'CamelCaseText'],
        ];
    }
}
