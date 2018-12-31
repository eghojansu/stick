<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 05, 2018 09:55
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\Core;
use Fal\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    private $fw;

    public function setUp()
    {
        $this->fw = new Core('phpunit-test');
    }

    /**
     * @dataProvider camelCaseProvider
     */
    public function testCamelCase($expected, $text)
    {
        $this->assertEquals($expected, $this->fw->camelCase($text));
    }

    /**
     * @dataProvider snakeCaseProvider
     */
    public function testSnakeCase($expected, $text)
    {
        $this->assertEquals($expected, $this->fw->snakeCase($text));
    }

    /**
     * @dataProvider classNameProvider
     */
    public function testClassName($expected, $class)
    {
        $this->assertEquals($expected, $this->fw->className($class));
    }

    /**
     * @dataProvider fixSlashesProvider
     */
    public function testFixSlashes($expected, $text)
    {
        $this->assertEquals($expected, $this->fw->fixSlashes($text));
    }

    /**
     * @dataProvider variableNameProvider
     */
    public function testVariableName($expected, $text)
    {
        $this->assertEquals($expected, $this->fw->variableName($text));
    }

    /**
     * @dataProvider splitProvider
     */
    public function testSplit($expected, $var, $delimiter = null)
    {
        $this->assertEquals($expected, $this->fw->split($var, $delimiter));
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoin($expected, $var, $glue = null)
    {
        $this->assertEquals($expected, $this->fw->join($var, $glue));
    }

    /**
     * @dataProvider pickProvider
     */
    public function testPick($expected, $keys, $collections = null, $default = null, $twoTier = false)
    {
        $this->assertEquals($expected, $this->fw->pick($keys, $collections, $default, $twoTier));
    }

    /**
     * @dataProvider urlEncodeProvider
     */
    public function testUrlEncode($expected, $var, $glue = '/')
    {
        $this->assertEquals($expected, $this->fw->urlEncode($var, $glue));
    }

    public function testMkdir()
    {
        $dir = TEST_TEMP.'test-mkdir';

        $this->assertFalse(is_dir($dir));
        $this->fw->mkdir($dir);
        $this->assertTrue(is_dir($dir));
        rmdir($dir);
    }

    public function testRead()
    {
        $this->assertEquals("foo\nbar", $this->fw->read(TEST_FIXTURE.'files/foobar.txt', true));
    }

    public function testWrite()
    {
        $this->assertEquals(3, $this->fw->write(TEST_TEMP.'test-write.txt', 'foo'));
        $this->assertEquals(6, $this->fw->write(TEST_TEMP.'test-write.txt', 'barbaz', true));
        $this->assertEquals('foobarbaz', $this->fw->read(TEST_TEMP.'test-write.txt'));
    }

    public function testDelete()
    {
        $this->assertFalse($this->fw->delete(TEST_TEMP.'test-delete.txt'));
        touch(TEST_TEMP.'test-delete.txt');
        $this->assertTrue($this->fw->delete(TEST_TEMP.'test-delete.txt'));
    }

    /**
     * @dataProvider referenceProvider
     */
    public function testReference($expected, $key, $update = null, $add = true, $var = null, $found = null)
    {
        $reference = &$this->fw->reference($key, $add, $var, $found);

        $this->assertEquals($expected, $found);

        if ($add && null !== $update) {
            $reference = $update;

            $this->assertEquals($update, $this->fw->reference($key, false, $hive, $found));
            $this->assertTrue($found);
        }
    }

    public function testReferenceObject()
    {
        $reference = &$this->fw->reference('foo', true, $hive, $found);
        $reference = new \StdClass();
        $reference->bar = 'baz';

        $this->assertFalse($found);
        $this->assertEquals('baz', $this->fw->reference('foo.bar', false, $hive, $found));
        $this->assertTrue($found);
    }

    public function testReferenceObjectInvalidProperty()
    {
        $reference = &$this->fw->reference('foo', true, $hive, $found);
        $reference = new \StdClass();

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid property name: "1foo".');

        $this->fw->reference('foo.1foo');
    }

    /**
     * @dataProvider dereferenceProvider
     */
    public function testDeReference($expected, $key, $var = null)
    {
        $this->assertEquals($expected, $this->fw->deReference($key, $var)->reference($key, false, $var));
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($expected, $key)
    {
        $this->assertEquals($expected, $this->fw->exists($key));
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($expected, $key)
    {
        $this->assertEquals($expected, $this->fw->get($key));
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($key, $value, $get = null, $expected = null)
    {
        $this->assertEquals($expected ?? $value, $this->fw->set($key, $value)->get($get ?? $key));
    }

    /**
     * @dataProvider clearProvider
     */
    public function testClear($expected, $key)
    {
        $this->assertEquals($expected, $this->fw->clear($key)->get($key));
    }

    /**
     * @dataProvider allExistsProvider
     */
    public function testAllExists($expected, $keys)
    {
        $this->assertEquals($expected, $this->fw->allExists($keys));
    }

    /**
     * @dataProvider allGetProvider
     */
    public function testAllGet($expected, $keys, $lowerize = false, $maps = null)
    {
        $this->assertEquals($expected, $this->fw->allGet($keys, $lowerize, $maps));
    }

    /**
     * @dataProvider allSetProvider
     */
    public function testAllSet($values, $prefix = null)
    {
        $this->fw->allSet($values, $prefix);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $this->fw->get($prefix.$key));
        }
    }

    /**
     * @dataProvider allClearProvider
     */
    public function testAllClear($expected, $keys, $prefix = null)
    {
        $this->fw->allClear($keys, $prefix);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $this->fw->get($prefix.$key));
        }
    }

    public function testCopy()
    {
        $this->assertTrue($this->fw->copy('CLI', 'foo')->get('foo'));
    }

    public function testCut()
    {
        $this->assertEquals('bar', $this->fw->set('foo', 'bar')->cut('foo'));
        $this->assertNull($this->fw->get('foo'));
    }

    /**
     * @dataProvider prependProvider
     */
    public function testPrepend($expected, $key, $init, $value)
    {
        $this->assertEquals($expected, $this->fw->set($key, $init)->prepend($key, $value)->get($key));
    }

    /**
     * @dataProvider appendProvider
     */
    public function testAppend($expected, $key, $init, $value)
    {
        $this->assertEquals($expected, $this->fw->set($key, $init)->append($key, $value)->get($key));
    }

    public function testConfig()
    {
        $this->assertTrue($this->fw->config(TEST_FIXTURE.'files/config.php')->get('config'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheExists($dsn)
    {
        $this->assertFalse($this->fw->set('CACHE', $dsn)->cacheExists('foo'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheGet($dsn)
    {
        $this->assertNull($this->fw->set('CACHE', $dsn)->cacheGet('foo'));
    }

    /**
     * @dataProvider cacheSetProvider
     */
    public function testCacheSet($dsn, $set, $value, $removed)
    {
        $this->assertEquals($set, $this->fw->set('CACHE', $dsn)->cacheSet('foo', 'foo'));
        $this->assertEquals($value, $this->fw->cacheGet('foo'));
        $this->assertEquals($removed, $this->fw->cacheClear('foo'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testCacheClear($dsn)
    {
        $this->assertFalse($this->fw->set('CACHE', $dsn)->cacheClear('foo'));
    }

    /**
     * @dataProvider cacheResetProvider
     */
    public function testCacheReset($dsn, $set, $affected)
    {
        $this->assertEquals($set, $this->fw->set('CACHE', $dsn)->cacheSet('foo', 'foo'));
        $this->assertEquals($affected, $this->fw->cacheReset());
        $this->fw->cacheClear('foo');
    }

    public function testCacheNoMemcacheHack()
    {
        $this->fw->set('MEMCACHE_HACK', false);
        $this->assertTrue($this->fw->set('CACHE', 'memcache=127.0.0.1')->cacheSet('foo', 'bar'));
        $this->fw->cacheClear('foo');
    }

    public function testMark()
    {
        $this->assertNotNull($this->fw->mark()->get('MARKS.0'));
        $this->assertNotNull($this->fw->mark('foo')->get('MARKS.foo'));
    }

    public function testEllapsed()
    {
        $this->assertGreaterThan(0.0, $this->fw->ellapsed());
        $this->assertGreaterThan(0.0, $this->fw->mark()->ellapsed());
        $this->assertGreaterThan(0.0, $this->fw->mark('foo')->ellapsed('foo'));
    }

    /**
     * @dataProvider hashProvider
     */
    public function testHash($expected, $text)
    {
        $this->assertEquals($expected, $this->fw->hash($text));
    }

    public function testBlacklisted()
    {
        $this->assertTrue(true);
    }

    public function testRegisterClassLoader()
    {
        $this->assertSame($this->fw, $this->fw->registerClassLoader());
        $this->assertSame($this->fw, $this->fw->unregisterClassLoader());
    }

    public function testUnregisterClassLoader()
    {
        $this->assertSame($this->fw, $this->fw->unregisterClassLoader());
    }

    public function testFindClass()
    {
        $this->fw->set('CACHE', true);

        // first try
        $this->assertNull($this->fw->findClass('SpecialNamespace\\LoadOnceOnlyClass'));

        // add namespace and fallback
        $this->fw->set('AUTOLOAD', array('SpecialNamespace\\' => TEST_FIXTURE.'special-namespace/'));
        $this->fw->set('AUTOLOAD_FALLBACK', array(TEST_FIXTURE.'special-namespace/different-namespace/'));

        $this->assertEquals(TEST_FIXTURE.'special-namespace/LoadOnceOnlyClass.php', $this->fw->findClass('SpecialNamespace\\LoadOnceOnlyClass'));
        // second call hit cache
        $this->assertEquals(TEST_FIXTURE.'special-namespace/LoadOnceOnlyClass.php', $this->fw->findClass('SpecialNamespace\\LoadOnceOnlyClass'));

        // fallback
        $this->assertEquals(TEST_FIXTURE.'special-namespace/different-namespace/Divergent/DifferentClass.php', $this->fw->findClass('Divergent\\DifferentClass'));
    }

    public function testLoadClass()
    {
        $this->assertFalse(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));

        // register autoloader
        $this->fw->set('AUTOLOAD', array('SpecialNamespace\\' => TEST_FIXTURE.'special-namespace/'));
        $this->fw->registerClassLoader();

        $this->assertTrue(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));
        $this->assertFalse(class_exists('SpecialNamespace\\UnknownClass'));
    }

    public function testOverrideRequestMethod()
    {
        $this->fw->set('REQUEST.X-Http-Method-Override', 'POST');
        $this->fw->set('POST._method', 'put');

        $this->fw->overrideRequestMethod();

        $this->assertEquals('PUT', $this->fw->get('VERB'));
    }

    /**
     * @dataProvider emulateCliRequestProvider
     */
    public function testEmulateCliRequest($argv, $path, $uri, $get)
    {
        array_unshift($argv, $_SERVER['argv'][0]);

        $this->fw->set('SERVER.argv', $argv);
        $this->fw->emulateCliRequest();

        $this->assertEquals($path, $this->fw->get('PATH'));
        $this->assertEquals($uri, $this->fw->get('URI'));
        $this->assertEquals($get, $this->fw->get('GET'));
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRoute($expression, $expression2 = null, $expected = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->route($expression, 'foo');

            return;
        }

        $this->fw->route($expression, 'foo');

        if ($expression2) {
            $this->fw->route($expression2, 'foo');
        }

        $this->assertNotEmpty($this->fw->get('ROUTES'));

        foreach ($expected ?? array() as $key => $value) {
            if (is_int($value)) {
                $this->assertCount($value, $this->fw->get($key));
            } else {
                $this->assertEquals($value, $this->fw->get($key));
            }
        }
    }

    public function testController()
    {
        $this->fw->controller('foo', array(
            'GET home /home' => 'home',
        ));

        $this->assertEquals('/home', $this->fw->get('ROUTE_ALIASES.home'));
        $this->assertCount(1, $this->fw->get('ROUTE_ALIASES'));
        $this->assertCount(1, $this->fw->get('ROUTE_HANDLERS'));
        $this->assertCount(1, $this->fw->get('ROUTES'));
    }

    public function testRest()
    {
        $this->fw->rest('home /home', 'foo');

        $this->assertEquals('/home', $this->fw->get('ROUTE_ALIASES.home'));
        $this->assertEquals('/home/@item', $this->fw->get('ROUTE_ALIASES.home_item'));
        $this->assertCount(2, $this->fw->get('ROUTE_ALIASES'));
        $this->assertCount(5, $this->fw->get('ROUTE_HANDLERS'));
        $this->assertCount(2, $this->fw->get('ROUTES'));
    }

    public function testRedirect()
    {
        $this->fw->redirect('GET /home', '/go-away');

        $this->assertNull($this->fw->get('ROUTE_ALIASES'));
        $this->assertCount(1, $this->fw->get('ROUTES'));
    }

    /**
     * @dataProvider assetProvider
     */
    public function testAsset($expected, $path, $asset = null)
    {
        $this->fw->set('ASSET', $asset);
        $this->fw->set('ASSET_MAP', array(
            'foo' => 'foo.css',
        ));
        $this->fw->set('ASSET_VERSION', 'v0.1.0');

        $expected = $this->fw->get('BASEURL').'/'.$expected;

        if ('dynamic' === $asset) {
            $this->assertStringStartsWith($expected, $this->fw->asset($path));
        } else {
            $this->assertEquals($expected, $this->fw->asset($path));
        }
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testAlias($expected, $alias, $arguments = null, $exception = null)
    {
        $this->fw->route('GET home /', 'foo');
        $this->fw->route('GET foo /foo', 'foo');
        $this->fw->route('GET bar /foo/@bar', 'foo');
        $this->fw->route('GET baz /foo/@bar(\d+)', 'foo');
        $this->fw->route('GET qux /foo/@bar(\d+)/@baz', 'foo');
        $this->fw->route('GET quux /foo/@bar(\d+)/@baz/@qux*', 'foo');

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->alias($alias, $arguments);

            return;
        }

        $this->assertEquals($expected, $this->fw->alias($alias, $arguments));
    }

    public function testPath()
    {
        $this->fw->route('GET foo /foo/@bar', 'foo');

        $this->assertStringEndsWith('/foo/bar', $this->fw->path('foo', array('bar' => 'bar')));
        $this->assertStringEndsWith('/foo/bar?foo', $this->fw->path('foo', array('bar' => 'bar'), 'foo'));
    }

    /**
     * @dataProvider createInstanceProvider
     */
    public function testCreateInstance($id, $rule = null, $expected = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->createInstance($id, $rule);

            return;
        }

        $this->assertInstanceOf($expected ?? $id, $this->fw->createInstance($id, $rule));
    }

    /**
     * @dataProvider ruleProvider
     */
    public function testRule($expected, $id, $rule)
    {
        $this->assertEquals($expected, $this->fw->rule($id, $rule)->get('SERVICE_RULES.'.$id));
    }

    /**
     * @dataProvider serviceProvider
     */
    public function testService($expected, $id, $rule = null, $secondCall = null, $useCreated = true)
    {
        if (!in_array($id, array('fw', 'Fal\\Stick\\Core'))) {
            $this->fw->rule($id, $rule);
        }

        $service = $rule['service'] ?? $useCreated;

        $this->assertInstanceOf($expected, $instance = $this->fw->service($id));

        if ($service) {
            $this->assertSame($instance, $this->fw->service($secondCall ?? $id));
        } else {
            $this->assertInstanceOf($expected, $secondInstance = $this->fw->service($secondCall ?? $id, $useCreated));
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
        $this->fw->rule('std_service', $std);

        $this->assertEquals($expected, $this->fw->call($callback, $arguments));
    }

    public function testExecute()
    {
        $foo = null;

        $this->fw->execute(function () use (&$foo) {
            $foo = 'bar';
        });

        $this->assertEquals('bar', $foo);
    }

    /**
     * @dataProvider grabProvider
     */
    public function testGrab($expected, $expression)
    {
        $callback = $this->fw->grab($expression);

        $this->assertEquals($expected, $callback());
    }

    /**
     * @dataProvider transProvider
     */
    public function testTrans($message, $expected = null, $arguments = null, $fallback = null, $alternatives = array())
    {
        $this->fw->set('LANGUAGE', 'id-id');
        $this->fw->set('LOCALES', TEST_FIXTURE.'dict/');

        $this->assertEquals($expected ?? $message, $this->fw->trans($message, $arguments, $fallback, ...$alternatives));
    }

    /**
     * @dataProvider choiceProvider
     */
    public function testChoice($message, $count = 0, $expected = null, $arguments = null, $fallback = null)
    {
        $this->fw->set('LOCALES', TEST_FIXTURE.'dict/');

        $this->assertEquals($expected ?? $message, $this->fw->choice($message, $count, $arguments, $fallback));
    }

    public function testLanguageReferenceException()
    {
        $this->fw->set('LOCALES', TEST_FIXTURE.'dict/');

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Message is not a string.');

        $this->fw->trans('invalid_ref');
    }

    public function testLog()
    {
        $directory = $this->prepareLogTest();

        $this->fw->log('emergency', 'Log emergency.');

        $this->assertCount(1, $files = glob($directory.'*'));
        $this->assertContains('Log emergency.', file_get_contents($files[0]));
    }

    public function testLogCode()
    {
        $directory = $this->prepareLogTest();

        $this->fw->logCode(E_ERROR, 'Log E_ERROR.');

        $this->assertCount(1, $files = glob($directory.'*'));
        $this->assertContains('Log E_ERROR.', file_get_contents($files[0]));
    }

    public function testLogFiles()
    {
        $this->assertEquals(array(), $this->fw->logFiles());

        $directory = $this->prepareLogTest();

        $this->assertEquals(array(), $this->fw->logFiles());

        $this->fw->log('debug', 'Log files');

        $this->assertCount(1, $this->fw->logFiles());

        // valid date parameter
        $this->assertCount(1, $this->fw->logFiles('-1 day', '+1 day'));

        // invalid date parameter
        $this->assertCount(0, $this->fw->logFiles('invalid date'));
    }

    public function testOn()
    {
        $this->assertEquals(array('bar', false), $this->fw->on('foo', 'bar')->get('EVENTS.foo'));
    }

    public function testSubscribe()
    {
        $this->assertEquals(array('Fixture\\FooSubscriber->bar', false), $this->fw->subscribe('Fixture\\FooSubscriber')->get('EVENTS.foo'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Subscriber "foo" should implements Fal\\Stick\\EventSubscriberInterface.');

        $this->fw->subscribe('foo');
    }

    public function testOne()
    {
        $this->assertEquals(array('bar', true), $this->fw->one('foo', 'bar')->get('EVENTS.foo'));
    }

    public function testOff()
    {
        $this->assertNull($this->fw->on('foo', 'bar')->off('foo')->get('EVENTS.foo'));
    }

    public function testDispatch()
    {
        $this->assertNull($this->fw->dispatch('foo'));

        $this->fw->on('version', 'phpversion');
        $this->fw->on('foo', function () {
            return 'bar';
        });

        $this->assertEquals(phpversion(), $this->fw->dispatch('version'));
        $this->assertEquals('bar', $this->fw->dispatch('foo', null, true));
        $this->assertNull($this->fw->get('EVENTS.foo'));
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStatus($code, $expected, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->status($code);

            return;
        }

        $this->fw->status($code);

        $this->assertEquals($code, $this->fw->get('CODE'));
        $this->assertEquals($expected, $this->fw->get('STATUS'));
    }

    /**
     * @dataProvider expireProvider
     */
    public function testExpire($seconds, array $headers)
    {
        $expected = array_merge(array(
            'X-Powered-By',
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
        ), $headers);

        $this->fw->expire($seconds);

        $this->assertEquals($expected, array_keys($this->fw->get('RESPONSE')));
    }

    public function testSend()
    {
        header_remove();
        $this->fw->set('CLI', false);

        $this->expectOutputString('Output sent.');

        $this->updateInitialValue('COOKIE', array('xfoo' => 'xbar'));
        $this->fw->set('COOKIE.foo', 'bar');
        $this->fw->send(500, array('Location' => '/foo'), 'Output sent.', 'text/plain', 1);
        // resend
        $this->fw->send();

        $this->assertTrue($this->fw->get('SENT'));
        $this->assertEquals(500, $this->fw->get('CODE'));
        $this->assertEquals('Internal Server Error', $this->fw->get('STATUS'));
        $this->assertEquals('Output sent.', $this->fw->get('OUTPUT'));
        $this->assertEquals('text/plain', $this->fw->get('MIME'));
        $this->assertEquals(array('Location' => '/foo'), $this->fw->get('RESPONSE'));

        if (function_exists('xdebug_get_headers')) {
            $actualHeaders = xdebug_get_headers();
            $expected = array(
                'Set-Cookie: foo=bar; HttpOnly',
                'Set-Cookie: xfoo=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; HttpOnly',
                'Location: /foo',
                'Content-type: text/plain;charset=UTF-8',
                'Content-Length: 12',
            );

            $this->assertEquals($expected, $actualHeaders);
        }

        header_remove();
    }

    public function testSendNoKbps()
    {
        $this->expectOutputString('Foo');

        $this->fw->send(null, null, 'Foo');
    }

    /**
     * @dataProvider errorProvider
     */
    public function testError($code, $sets, $expected, $mime = null)
    {
        $this->fw->set('QUIET', true);
        $this->fw->allSet($sets ?? array());
        $this->fw->error($code);
        // second call
        $this->fw->error($code);

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
        $this->assertEquals($code, $this->fw->get('CODE'));
        $this->assertEquals($mime, $this->fw->get('MIME'));
    }

    public function testErrorListener()
    {
        $this->fw->set('QUIET', true);
        $this->fw->on('fw_error', function () {
            return 'error intercepted';
        });
        $this->fw->error(404);

        $this->assertEquals('error intercepted', $this->fw->get('OUTPUT'));
        $this->assertEquals(404, $this->fw->get('CODE'));
    }

    public function testErrorListenerMakeError()
    {
        $this->fw->set('QUIET', true);
        $this->fw->set('DEBUG', true);
        $this->fw->on('fw_error', function () {
            throw new \Exception('error make error');
        });
        $this->fw->error(404);

        $expected = 'Status: Internal Server Error'.PHP_EOL.'Text  : error make error'.PHP_EOL;

        $this->assertNotEquals($expected, $this->fw->get('OUTPUT'));
        $this->assertStringStartsWith($expected, $this->fw->get('OUTPUT'));
        $this->assertEquals(500, $this->fw->get('CODE'));
    }

    public function testUnload()
    {
        $this->assertTrue(true);
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($expected, $sets = null)
    {
        $this->fw->set('QUIET', true);
        $this->fw->allSet($sets ?? array());

        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->route('GET /foo', function () {
            return function ($fw) {
                $fw->set('OUTPUT', 'modified by controller internal closure');
            };
        });
        $this->fw->route('GET /bar/@bar', function ($bar) {
            return 'bar'.$bar;
        });
        $this->fw->route('GET /baz/@baz(\d+)', function ($baz) {
            return 'baz'.$baz;
        });
        $this->fw->route('GET /qux/@qux*', function ($qux) {
            return 'qux'.implode('', $qux);
        });
        $this->fw->route('GET /json', function () {
            return array('foo' => 'bar');
        });
        $this->fw->route('GET /throw-error', function () {
            throw new HttpException('Error thrown', 404);
        });
        $this->fw->route('GET /sync-only sync', function () {
            return 'sync';
        });
        $this->fw->route('GET /controller-home', 'Fixture\\TestController->home');
        $this->fw->route('GET /controller-not-callable', 'controller-not-callable');
        $this->fw->route('GET /controller-not-callable2', 'UnknownClass->foo');

        $this->fw->run();

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    public function testRunInterception()
    {
        $this->fw->set('QUIET', true);
        $this->fw->on('fw_preroute', function () {
            return 'request intercepted';
        });
        $this->fw->run();

        $this->assertEquals('request intercepted', $this->fw->get('OUTPUT'));
    }

    public function testRunModification()
    {
        $this->fw->set('QUIET', true);
        $this->fw->on('fw_postroute', function () {
            return 'request modified';
        });
        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->run();

        $this->assertEquals('request modified', $this->fw->get('OUTPUT'));
    }

    public function testRunNoRoutes()
    {
        $this->fw->set('QUIET', true);
        $this->fw->run();

        $this->assertContains('No route defined.', $this->fw->get('OUTPUT'));
    }

    public function testRunCaching()
    {
        $out = null;
        $this->fw->set('QUIET', true);
        $this->fw->set('CACHE', true);
        $this->fw->cacheReset();
        $this->fw->route('GET / 1', function () use (&$out) {
            return $out = sprintf('%s', microtime());
        });
        $this->fw->run();

        $this->assertEquals($out, $this->fw->get('OUTPUT'));

        $this->fw->set('foo', 'bar');

        // second call
        $this->fw->run();
        $this->assertEquals($out, $this->fw->get('OUTPUT'));

        // with modified time check
        $this->fw->set('REQUEST.If-Modified-Since', '+1 year');
        $this->fw->allClear('OUTPUT,SENT');
        $this->fw->run();
        $this->assertNull($this->fw->get('OUTPUT'));
        $this->assertEquals(304, $this->fw->get('CODE'));
        $this->assertEquals('Not Modified', $this->fw->get('STATUS'));
    }

    public function testRunCorsExposeHeaders()
    {
        $this->fw->set('QUIET', true);
        $this->fw->set('CORS.origin', 'foo');
        $this->fw->set('CORS.expose', 'foo');
        $this->fw->set('REQUEST.Origin', 'foo');
        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->run();
        $headers = array(
            'X-Powered-By' => 'Stick-Framework',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Expires' => 'Thu, 01 Jan 1970 00:00:00 +0000',
            'Access-Control-Allow-Origin' => 'foo',
            'Access-Control-Allow-Credentials' => 'false',
            'Access-Control-Expose-Headers' => 'foo',
        );

        $this->assertEquals('home', $this->fw->get('OUTPUT'));
        $this->assertEquals($headers, $this->fw->get('RESPONSE'));
    }

    public function testRunCorsOptions()
    {
        $this->fw->set('QUIET', true);
        $this->fw->set('CORS.origin', 'foo');
        $this->fw->set('REQUEST.Origin', 'foo');
        $this->fw->set('VERB', 'OPTIONS');
        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->run();
        $headers = array(
            'X-Powered-By' => 'Stick-Framework',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Expires' => 'Thu, 01 Jan 1970 00:00:00 +0000',
            'Access-Control-Allow-Origin' => 'foo',
            'Access-Control-Allow-Credentials' => 'false',
            'Allow' => 'GET',
            'Access-Control-Allow-Methods' => 'OPTIONS,GET',
            'Access-Control-Allow-Headers' => null,
            'Access-Control-Max-Age' => null,
        );

        $this->assertNull($this->fw->get('OUTPUT'));
        $this->assertEquals($headers, $this->fw->get('RESPONSE'));
    }

    /**
     * @dataProvider mockProvider
     */
    public function testMock($expected, $expression, $arguments = null, $server = null, $body = null, $exception = null)
    {
        $this->fw->set('QUIET', true);
        $this->fw->route('GET home /', function () {
            return 'home';
        });
        $this->fw->route('GET foo /foo/@bar', function ($bar) {
            return 'foo'.$bar;
        });
        $this->fw->route('POST /bar', function (Core $fw) {
            return $fw->get('POST.bar');
        });

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->mock($expression, $arguments, $server, $body);

            return;
        }

        $this->fw->mock($expression, $arguments, $server, $body);

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider rerouteProvider
     */
    public function testReroute($cli, $expected, $target, $permanent = false)
    {
        $this->fw->set('QUIET', true);
        $this->fw->set('CLI', $cli);
        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->route('GET /foo', function () {
            return 'foo';
        });
        $this->fw->route('GET bar /bar/@bar', function ($bar) {
            return 'foo'.$bar;
        });

        $this->fw->reroute($target, $permanent);

        if ($cli) {
            $this->assertEquals($expected, $this->fw->get('OUTPUT'));
        } else {
            $this->assertStringEndsWith($expected, $this->fw->get('RESPONSE.Location'));
        }
    }

    public function testRerouteInterception()
    {
        $this->fw->on('fw_reroute', function (Core $fw, $url) {
            return $fw->set('rerouted_to', $url);
        });

        $this->fw->reroute('/foo');

        $this->assertStringEndsWith('/foo', $this->fw->get('rerouted_to'));
    }

    public function testRerouteInRedirection()
    {
        $this->fw->set('QUIET', true);
        $this->fw->set('PATH', '/bar');
        $this->fw->route('GET foo /foo', function () {
            return 'foo';
        });
        $this->fw->redirect('GET /bar', 'foo');
        $this->fw->run();

        $this->assertEquals('foo', $this->fw->get('OUTPUT'));
    }

    public function testSetInterceptor()
    {
        $this->fw->set('QUIET', true);

        // config
        $this->fw->set('CONFIGS', TEST_FIXTURE.'files/config.php');
        $this->assertTrue($this->fw->get('config'));

        // routes
        $this->fw->set('ROUTES', array(
            array('GET /route', function () {
                return 'route';
            }),
        ));
        $this->fw->allSet(array(
            'PATH' => '/route',
            'VERB' => 'GET',
        ));
        $this->fw->allClear('OUTPUT,RESPONSE,SENT');
        $this->fw->run();
        $this->assertEquals('route', $this->fw->get('OUTPUT'));

        // redirects
        $this->fw->set('REDIRECTS', array(
            array('GET /redirect', '/route'),
        ));
        $this->fw->allSet(array(
            'PATH' => '/redirect',
            'VERB' => 'GET',
        ));
        $this->fw->allClear('OUTPUT,RESPONSE,SENT');
        $this->fw->run();
        $this->assertEquals('route', $this->fw->get('OUTPUT'));

        // rest
        $this->fw->set('RESTS', array(
            array('rest /rest/foo', 'Fixture\\RestController'),
        ));
        $registered = array(
            array('GET', '/rest/foo', 'rest all'),
            array('POST', '/rest/foo', 'rest create'),
            array('GET', '/rest/foo/1', 'rest get 1'),
            array('PUT', '/rest/foo/1', 'rest put 1'),
            array('DELETE', '/rest/foo/1', 'rest delete 1'),
        );

        foreach ($registered as list($verb, $path, $output)) {
            $this->fw->allSet(array(
                'PATH' => $path,
                'VERB' => $verb,
            ));
            $this->fw->allClear('OUTPUT,RESPONSE,SENT');
            $this->fw->run();
            $this->assertEquals($output, $this->fw->get('OUTPUT'));
        }

        // controllers
        $this->fw->set('CONTROLLERS', array(
            array('Fixture\\TestController', array(
                'GET /controller' => 'home',
            )),
        ));
        $this->fw->allSet(array(
            'PATH' => '/controller',
            'VERB' => 'GET',
        ));
        $this->fw->allClear('OUTPUT,RESPONSE,SENT');
        $this->fw->run();
        $this->assertEquals('test controller home', $this->fw->get('OUTPUT'));

        // rules
        $this->fw->set('RULES', array(
            array('mydate', 'DateTime'),
        ));
        $this->assertInstanceOf('DateTime', $this->fw->service('mydate'));

        // events
        $this->fw->set('EVENTS', array(
            array('on_event', function () {
                return 'on_event returns value';
            }),
        ));
        $this->assertEquals('on_event returns value', $this->fw->dispatch('on_event'));

        // subscribers
        $this->fw->set('SUBSCRIBERS', 'Fixture\\FooSubscriber');
        $this->assertEquals('subscribe foo', $this->fw->dispatch('foo'));
    }

    public function testConstruct()
    {
        $server = array(
            'CONTENT_TYPE' => 'text/html',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        );
        $fw = new Core('phpunit-test', null, null, null, $server);

        $this->assertEquals('text/html', $fw->get('REQUEST.Content-Type'));
        $this->assertEquals('XMLHttpRequest', $fw->get('REQUEST.X-Requested-With'));
        $this->assertTrue($fw->get('AJAX'));
    }

    public function testCreate()
    {
        $fw = Core::create('phpunit-test', array('foo' => 'bar'));

        $this->assertEquals(array('foo' => 'bar'), $fw->get('GET'));
    }

    public function testCreateFromGlobals()
    {
        $fw = Core::createFromGlobals('phpunit-test');

        $this->assertEquals($_SERVER, $fw->get('SERVER'));
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

    public function classNameProvider()
    {
        return array(
            array('CoreTest', $this),
            array('CoreTest', 'Fal\\Stick\\Test\CoreTest'),
            array('DateTime', new \DateTime()),
            array('DateTime', 'DateTime'),
        );
    }

    public function fixSlashesProvider()
    {
        return array(
            array('foo', 'foo'),
            array('/foo', '\\foo'),
            array('/foo/bar', '\\foo/bar'),
            array('/foo/bar/baz', '\\foo/bar\\baz'),
        );
    }

    public function variableNameProvider()
    {
        return array(
            array(true, 'foo'),
            array(true, '_foo'),
            array(true, '__foo'),
            array(false, '1foo'),
            array(false, '-foo'),
            array(false, 'baz\\s'),
        );
    }

    public function splitProvider()
    {
        return array(
            array(array(), null),
            array(array(), ''),
            array(array(), array()),
            array(array('foo', 'bar'), array('foo', 'bar')),
            array(array('foo', 'bar'), 'foo,bar'),
            array(array('foo', 'bar'), 'foo;bar'),
            array(array('foo', 'bar'), 'foo|bar'),
            array(array('foo', 'bar'), 'foo|bar|'),
            array(array('foo', 'bar'), 'foo||bar'),
            array(array('foo', 'bar'), 'foo-bar', '-'),
            array(array('foo-bar'), 'foo-bar'),
        );
    }

    public function joinProvider()
    {
        return array(
            array('', array()),
            array('', null),
            array('', ''),
            array('foo,bar', array('foo', 'bar')),
            array('foo|bar', array('foo', 'bar'), '|'),
        );
    }

    public function pickProvider()
    {
        return array(
            array('bar', 'foo', array('foo' => 'bar', 'baz' => 'qux')),
            array('qux', 'baz', array('foo' => 'bar', 'baz' => 'qux')),
            array(null, 'foo'),
            array(null, 'qux', array('foo' => 'bar', 'baz' => 'qux')),
            array('bar', 'foo', array(array('foo' => 'bar', 'baz' => 'qux')), null, true),
        );
    }

    public function urlEncodeProvider()
    {
        return array(
            array('foo', 'foo'),
            array('foo/bar', array('foo', 'bar')),
            array('foo/bar', array(array('foo', 'bar'))),
            array('1', 1),
            array('foo/bar+baz/1', array('foo', 'bar baz', 1)),
        );
    }

    public function referenceProvider()
    {
        return array(
            array(true, 'CLI'),
            array(false, 'foo'),
            array(false, 'foo.bar', 'baz'),
            array(false, 'foo.bar.baz', 'qux'),
            array(false, 'foo.bar', new \StdClass()),
            array(false, 'foo', null, false),
            array(true, 'foo', null, false, array('foo' => 'bar')),
            array(false, 'SESSION.foo'),
        );
    }

    public function dereferenceProvider()
    {
        $foo = new \StdClass();
        $foo->bar = 'baz';

        return array(
            array(null, 'CLI'),
            array(null, 'foo.bar'),
            array(null, 'SESSION.foo'),
            array(array(), 'SESSION'),
            array(null, 'foo', array('foo' => 'bar')),
            array(null, 'foo.bar', array('foo' => $foo)),
        );
    }

    public function existsProvider()
    {
        return array(
            array(true, 'CLI'),
            array(true, 'JAR.expire'),
            array(false, 'foo'),
            array(false, 'foo.bar'),
        );
    }

    public function getProvider()
    {
        return array(
            array(true, 'CLI'),
            array(0, 'JAR.expire'),
            array(null, 'foo'),
            array(null, 'foo.bar'),
        );
    }

    public function setProvider()
    {
        return array(
            array('foo', 'bar'),
            array('foo', array('bar' => 'baz'), 'foo.bar', 'baz'),
            array('CACHE', 'apc', 'CACHE_ENGINE', 'apc'),
            array('LANGUAGE', 'en'),
            array('EVENTS', array(array('foo', 'bar')), 'EVENTS.foo', array('bar', false)),
        );
    }

    public function clearProvider()
    {
        return array(
            array(null, 'foo'),
            array(true, 'CLI'),
            array(0, 'JAR.expire'),
        );
    }

    public function allExistsProvider()
    {
        return array(
            array(true, 'CLI'),
            array(true, 'CLI,PATH'),
            array(true, array('CLI', 'PATH')),
            array(false, 'foo'),
            array(false, 'CLI,foo'),
        );
    }

    public function allGetProvider()
    {
        return array(
            array(array('CLI' => true), 'CLI'),
            array(array('foo' => null), 'foo'),
            array(array('CLI' => true, 'PATH' => '/'), 'CLI,PATH'),
            array(array('CLI' => true, 'PATH' => '/'), array('CLI', 'PATH')),
            array(array('CLI' => true, 'foo' => null), array('CLI', 'foo')),
            array(array('cli' => true, 'foo' => null), array('CLI', 'foo'), true),
            array(array('is_cli' => true, 'foo' => null), array('CLI', 'foo'), true, array('CLI' => 'is_cli')),
        );
    }

    public function allSetProvider()
    {
        return array(
            array(array('foo' => 'bar')),
            array(array('foo' => 'bar', 'CLI' => false)),
            array(array('foo' => 'bar'), 'root.'),
        );
    }

    public function allClearProvider()
    {
        return array(
            array(array('CLI' => true), 'CLI'),
            array(array('CLI' => true, 'foo' => null), 'CLI,foo'),
        );
    }

    public function prependProvider()
    {
        return array(
            array(array('bar', 'baz'), 'foo', array('baz'), 'bar'),
            array('barbaz', 'foo', 'baz', 'bar'),
        );
    }

    public function appendProvider()
    {
        return array(
            array(array('bar', 'baz'), 'foo', array('bar'), 'baz'),
            array('barbaz', 'foo', 'bar', 'baz'),
        );
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->fw['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->fw['foo']);
    }

    public function testOffsetSet()
    {
        $this->fw['foo'] = 'bar';

        $this->assertEquals('bar', $this->fw['foo']);
    }

    public function testOffsetUnset()
    {
        $this->fw['foo'] = 'bar';
        unset($this->fw['foo']);

        $this->assertNull($this->fw['foo']);
    }

    /**
     * @dataProvider castProvider
     */
    public function testCast($expected, $val)
    {
        $this->assertEquals($expected, $this->fw->cast($val));
    }

    public function testIncludeFile()
    {
        $this->assertEquals('foo', \Fal\Stick\includeFile(TEST_FIXTURE.'files/foo.php'));
    }

    public function testRequireFile()
    {
        $this->assertEquals('foo', \Fal\Stick\requireFile(TEST_FIXTURE.'files/foo.php'));
    }

    public function cacheProvider()
    {
        return array(
            array(null),
            array('apc'),
            array('apcu'),
            array('filesystem='.TEST_TEMP.'cache-test/'),
            array('memcache=127.0.0.1'),
            array('memcached=127.0.0.1'),
            array('redis=127.0.0.1'),
            array(true),
        );
    }

    public function cacheSetProvider()
    {
        return array(
            array(null, false, null, false),
            array('apc', true, 'foo', true),
            array('apcu', true, 'foo', true),
            array('filesystem='.TEST_TEMP.'cache-test/', true, 'foo', true),
            array('memcache=127.0.0.1', true, 'foo', true),
            array('memcached=127.0.0.1', true, 'foo', true),
            array('redis=127.0.0.1', true, 'foo', true),
            array('redis=127.0.0.1:6379:1', true, 'foo', true),
            array(true, true, 'foo', true),
            // host/db error, fallback to automatic selection
            array('memcache=foo', true, 'foo', true),
            array('redis=foo', true, 'foo', true),
        );
    }

    public function cacheResetProvider()
    {
        return array(
            array(null, null, 0),
            array('apc', true, 1),
            array('apcu', true, 1),
            array('filesystem='.TEST_TEMP.'cache-test/', true, 1),
            array('memcache=127.0.0.1', true, 1),
            array('memcached=127.0.0.1', true, 1),
            array('redis=127.0.0.1', true, 1),
            array(true, true, 1),
        );
    }

    public function hashProvider()
    {
        return array(
            array('1xnmsgr3l2f5f', 'foo'),
            array('z98tcrk4v1vx', 'bar'),
            array('28yc24y4a7vow', 'baz'),
        );
    }

    public function emulateCliRequestProvider()
    {
        return array(
            array(array(), '/', '/', array()),
            array(array('/foo'), '/foo', '/foo', array()),
            array(array('/foo?bar=baz&qux'), '/foo', '/foo?bar=baz&qux', array('bar' => 'baz', 'qux' => '')),
            array(array('foo', 'bar', '-fo', '--bar=baz'), '/foo/bar', '/foo/bar?bar=baz&f=&o=', array('f' => '', 'o' => '', 'bar' => 'baz')),
        );
    }

    public function routeProvider()
    {
        return array(
            array(
                'GET home /home',
                'POST home',
                array(
                    'ROUTE_ALIASES.home' => '/home',
                    'ROUTE_ALIASES' => 1,
                    'ROUTE_HANDLERS' => 2,
                    'ROUTES' => 1,
                ),
            ),
            array(
                'GET',
                null,
                'Invalid route expression: "GET".',
                'LogicException',
            ),
            array(
                'GET foo',
                null,
                'Route not exists: "foo".',
                'LogicException',
            ),
        );
    }

    public function assetProvider()
    {
        return array(
            array('bar', 'bar'),
            array('foo.css', 'foo'),
            array('foo.css?', 'foo', 'dynamic'),
            array('foo.css?v0.1.0', 'foo', 'static'),
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
            array('Route "bar", parameter "bar" should be provided.', 'bar', null, 'LogicException'),
            array('Route "baz", parameter "bar" is not valid, given: "baz".', 'baz', array('bar' => 'baz'), 'LogicException'),
        );
    }

    public function createInstanceProvider()
    {
        return array(
            array('Fixture\\FooService'),
            array(
                'bar',
                array(
                    'use' => 'Fixture\\BarService',
                    'arguments' => array('name' => 'bar'),
                ),
                'Fixture\\BarService',
            ),
            array(
                'Fixture\\FooService',
                array(
                    'boot' => function ($instance, $fw) {
                    },
                ),
            ),
            array(
                'Fixture\\FooService',
                array(
                    'constructor' => function () {
                        return new \Fixture\FooService();
                    },
                ),
            ),
            array(
                'foo',
                array(
                    'class' => 'Fixture\\FooService',
                    'constructor' => function () {
                        return new \DateTime();
                    },
                ),
                'Constructor of "foo" should return instance of Fixture\\FooService.',
                'LogicException',
            ),
            array(
                'Fixture\\FooInterface',
                null,
                'Unable to create instance for "Fixture\\FooInterface". Please provide instantiable version of Fixture\\FooInterface.',
                'LogicException',
            ),
        );
    }

    public function ruleProvider()
    {
        return array(
            array(array('class' => 'foo', 'service' => true), 'foo', null),
            array(array('class' => 'foo', 'service' => false, false), 'foo', false),
            array(array('class' => 'foo', 'service' => false), 'foo', array('service' => false)),
            array(array('constructor' => 'trim', 'class' => 'foo', 'service' => true), 'foo', 'trim'),
            array(array('class' => 'DateTime', 'service' => true), 'foo', 'DateTime'),
            array(array('class' => 'DateTime', 'service' => true), 'foo', new \DateTime()),
        );
    }

    public function serviceProvider()
    {
        return array(
            array('Fal\\Stick\\Core', 'fw'),
            array('Fal\\Stick\\Core', 'Fal\\Stick\\Core'),
            array('Fixture\\FooService', 'Fixture\\FooService'),
            array('Fixture\\FooService', 'foo', 'Fixture\\FooService', 'Fixture\\FooService'),
            array('Fixture\\FooService', 'Fixture\\FooService', null, null, false),
            array('Fixture\\FooService', 'foo', array('class' => 'Fixture\\FooService', 'service' => false), 'Fixture\\FooService'),
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
            array('success', function ($foo, ...$all) {
                return 'bar' === $foo && array('baz', 'qux') === $all ? 'success' : 'error';
            }, array('bar', 'baz', 'qux')),
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

    public function transProvider()
    {
        return array(
            array('foo'),
            array('a_flag', 'Sebuah bendera nasional'),
            array('there.is.one.blueberry', 'There is a blueberry'),
            array('there.is.one.apple', 'Ada sebuah apel'),
            array('there.is.one.orange', 'Ada sebuah jeruk'),
            array('there.is.one.mango', 'Ada sebuah mangga'),
            array('there.is.one.fruit', 'Ada sebuah strawberi', array('{fruit}' => 'strawberi')),
            array('there.is.one.pineaplle', 'Ada sebuah apel', null, null, array('there.is.one.apple')),
            array('there.is.one.pineaplle', 'Tidak ada buah yang diinginkan', null, 'Tidak ada buah yang diinginkan', array('there.is.one.durian')),
        );
    }

    public function choiceProvider()
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

    public function statusProvider()
    {
        return array(
            array(200, 'OK'),
            array(403, 'Forbidden'),
            array(500, 'Internal Server Error'),
            array(900, 'Unsupported HTTP code: 900.', 'LogicException'),
        );
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

    public function errorProvider()
    {
        return array(
            array(404, null, 'Status: Not Found'.PHP_EOL.'Text  : HTTP 404 (GET /)'.PHP_EOL.PHP_EOL),
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

    public function runProvider()
    {
        return array(
            array('home'),
            array('modified by controller internal closure', array('PATH' => '/foo')),
            array('barbaz', array('PATH' => '/bar/baz')),
            array('baz1', array('PATH' => '/baz/1')),
            array('quxfoobarbaz', array('PATH' => '/qux/foo/bar/baz')),
            array('{"foo":"bar"}', array('PATH' => '/json')),
            array('test controller home', array('PATH' => '/controller-home')),
            array('sync', array('PATH' => '/sync-only', 'CLI' => false)),
            array("Status: Not Found\nText  : HTTP 404 (GET /sync-only)\n\n", array('PATH' => '/sync-only')),
            array("Status: Method Not Allowed\nText  : HTTP 405 (POST /foo)\n\n", array('PATH' => '/foo', 'VERB' => 'POST')),
            array("Status: Not Found\nText  : HTTP 404 (GET /unknown)\n\n", array('PATH' => '/unknown')),
            array("Status: Method Not Allowed\nText  : HTTP 405 (GET /controller-not-callable)\n\n", array('PATH' => '/controller-not-callable')),
            array("Status: Not Found\nText  : HTTP 404 (GET /controller-not-callable2)\n\n", array('PATH' => '/controller-not-callable2')),
            array("Status: Not Found\nText  : Error thrown\n\n", array('PATH' => '/throw-error')),
        );
    }

    public function mockProvider()
    {
        return array(
            array('home', 'GET home'),
            array('foobaz', 'GET foo(bar=baz)'),
            array('foo', 'POST /bar', array('bar' => 'foo')),
            array('Invalid mock expression: "foo"', 'foo', null, null, null, 'LogicException'),
        );
    }

    public function rerouteProvider()
    {
        return array(
            array(true, 'home', '/'),
            array(true, 'foo', '/foo'),
            array(true, 'foobaz', '/bar/baz'),
            array(true, 'fooqux', '/bar/qux'),
            array(true, 'foobar', array('bar', array('bar' => 'bar'))),
            array(true, 'foobar', 'bar(bar=bar)'),
            array(true, 'home', null),
            array(false, '/bar/qux', '/bar/qux'),
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

    private function prepareLogTest()
    {
        $directory = TEST_TEMP.'logs-test/';

        if (is_dir($directory)) {
            foreach (glob($directory.'*') as $file) {
                unlink($file);
            }
        }

        $this->fw->set('LOG', $directory);
        $this->fw->set('THRESHOLD', 'debug');

        return $directory;
    }

    private function updateInitialValue($name, $value)
    {
        $ref = new \ReflectionProperty($this->fw, 'init');
        $ref->setAccessible(true);
        $val = $ref->getValue($this->fw);
        $val[$name] = $value;
        $ref->setValue($this->fw, $val);
    }
}
