<?php

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
use Fal\Stick\Event;
use Fal\Stick\GetControllerArgsEvent;
use Fal\Stick\ResponseException;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperParameterConverter;
use FixtureMapper\User;
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
        spl_autoload_unregister(array($this->app, 'loadClass'));
    }

    private function updateInitialValue($name, $value)
    {
        $ref = new \ReflectionProperty($this->app, 'init');
        $ref->setAccessible(true);
        $val = $ref->getValue($this->app);
        $val[$name] = $value;
        $ref->setValue($this->app, $val);
    }

    private function registerServiceLoader()
    {
        $this->app->mset(array(
            'AUTOLOAD' => array(
                'FixtureServices\\' => array(FIXTURE.'classes/services/'),
            ),
        ))->registerAutoloader();
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
                    $app->set('RESPONSE', 'From callable');
                };
            })
            ->route('GET /null', function () {
                return null;
            })
            ->route('GET /obj', function (App $app) {
                return $app;
            })
            ->route('GET unlimited /unlimited/*', function ($_p) {
                return implode(', ', $_p);
            })
            ->route('GET /custom/@name/(\d)', function ($name, $_p1) {
                return $name.' '.$_p1;
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
        $this->assertNull($app->get('REQUEST'));
        $this->assertNull($app->get('QUERY'));
    }

    public function testCreateFromGlobals()
    {
        $app = App::createFromGlobals();

        $this->assertInstanceOf(App::class, $app);
        $this->assertNotSame($this->app, $app);
        $this->assertEquals($app->get('REQUEST'), $_POST);
        $this->assertEquals($app->get('QUERY'), $_GET);
    }

    public function testPick()
    {
        $list = array('foo' => 'bar', 'bar' => 'baz');

        $this->assertEquals('bar', App::pick($list, 'foo'));
        $this->assertEquals('qux', App::pick($list, 'none', 'qux'));
    }

    public function testPickFirst()
    {
        $list = array('foo' => 'bar', 'bar' => 'baz');

        $this->assertEquals('bar', App::pickFirst($list, 'foo|bar'));
        $this->assertEquals('bar', App::pickFirst($list, array('foo', 'bar')));
        $this->assertEquals('baz', App::pickFirst($list, array('bar', 'foo')));
        $this->assertEquals('quux', App::pickFirst($list, array('qux'), 'quux'));
    }

    public function testSplit()
    {
        $target = array('foo', 'bar', 'baz', 'qux');

        $this->assertEquals(array('foo'), App::split('foo'));
        $this->assertEquals($target, App::split('foo,bar,baz,qux'));
        $this->assertEquals($target, App::split('foo;bar;baz;qux'));
        $this->assertEquals($target, App::split('foo|bar|baz|qux'));
        $this->assertEquals($target, App::split('foo,bar;baz|qux'));
        $this->assertEquals($target, App::split('foo,bar;baz|qux|'));
        $this->assertEquals($target, App::split('foo,bar ;baz|qux|'));
    }

    public function testFixslashes()
    {
        $this->assertEquals('/foo', App::fixslashes('\\foo'));
        $this->assertEquals('/foo/bar', App::fixslashes('\\foo/bar'));
    }

    public function testArr()
    {
        $target = array('foo', 'bar', 'baz', 'qux');

        $this->assertEquals($target, App::arr('foo,bar,baz,qux'));
        $this->assertEquals($target, App::arr($target));
    }

    public function testCutbefore()
    {
        $this->assertEquals('foo', App::cutbefore('foo?bar', '?'));
        $this->assertEquals('foo?', App::cutbefore('foo?bar', '?', null, true));
        $this->assertEquals('foo?bar', App::cutbefore('foo?bar', '#'));
        $this->assertEquals('x', App::cutbefore('foo?bar', '#', 'x'));
    }

    public function testCutafter()
    {
        $this->assertEquals('bar', App::cutafter('foo?bar', '?'));
        $this->assertEquals('?bar', App::cutafter('foo?bar', '?', null, true));
        $this->assertEquals('foo?bar', App::cutafter('foo?bar', '#'));
        $this->assertEquals('x', App::cutafter('foo?bar', '#', 'x'));
    }

    public function testCutprefix()
    {
        $this->assertEquals('bar', App::cutprefix('foobar', 'foo'));
        $this->assertEquals('default', App::cutprefix('foobar', 'foobar', 'default'));
        $this->assertEquals('qux', App::cutprefix('foobar', 'xoo', 'qux'));
    }

    public function testCutsuffix()
    {
        $this->assertEquals('foo', App::cutsuffix('foobar', 'bar'));
        $this->assertEquals('default', App::cutsuffix('foobar', 'foobar', 'default'));
        $this->assertEquals('qux', App::cutsuffix('foobar', 'xar', 'qux'));
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
    }

    public function testGet()
    {
        $isCli = &$this->app->get('CLI');
        $this->assertTrue($isCli);
        $isCli = false;
        $this->assertFalse($this->app->get('CLI'));

        $this->assertEquals('bar', $this->app->get('foo', 'bar'));
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
            'SERVER.HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST',
            'REQUEST._method' => 'put',
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
        $this->assertEquals(array('bar' => 'baz', 'qux' => ''), $this->app->get('QUERY'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo#bar'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));
        $this->assertEquals('/foo#bar', $this->app->get('URI'));
        $this->assertEquals(array(), $this->app->get('QUERY'));
        $this->assertEquals('bar', $this->app->get('FRAGMENT'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, '/foo?bar=baz&qux#quux'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo', $this->app->get('PATH'));
        $this->assertEquals('/foo?bar=baz&qux#quux', $this->app->get('URI'));
        $this->assertEquals(array('bar' => 'baz', 'qux' => ''), $this->app->get('QUERY'));
        $this->assertEquals('quux', $this->app->get('FRAGMENT'));

        $this->app->mset(array(
            'SERVER.argv' => array($entry, 'foo', 'bar', '-fo', '--bar=baz'),
        ))->emulateCliRequest();
        $this->assertEquals('/foo/bar', $this->app->get('PATH'));
        $this->assertEquals('/foo/bar?bar=baz&f=&o=', $this->app->get('URI'));
        $this->assertEquals(array('f' => '', 'o' => '', 'bar' => 'baz'), $this->app->get('QUERY'));
    }

    public function testRegisterAutoloader()
    {
        $this->app->registerAutoloader();
        $this->assertContains(array($this->app, 'loadClass'), spl_autoload_functions());
    }

    public function testUnregisterAutoloader()
    {
        $this->app->registerAutoloader();
        $this->app->unregisterAutoloader();
        $this->assertNotContains(array($this->app, 'loadClass'), spl_autoload_functions());
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->app['CLI']));
    }

    public function testOffsetSet()
    {
        $this->app['foo'] = 'bar';

        $this->assertEquals('bar', $this->app->get('foo'));
    }

    public function testOffsetGet()
    {
        $this->assertTrue($this->app['CLI']);

        $foo = &$this->app['foo'];
        $foo = 'bar';
        $this->assertEquals('bar', $this->app['foo']);

        $this->app['bar']['baz'] = 'qux';
        $this->assertEquals('qux', $this->app->get('bar.baz'));
    }

    public function testOffsetUnset()
    {
        $this->app->mset(array(
            'foo' => 'bar',
        ));
        unset($this->app['foo']);

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
        $actual = array_keys($this->app->expire($secs)->get('HEADERS'));

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

    public function testSendHeaders()
    {
        $this->assertEquals($this->app, $this->app->sendHeaders());

        if (function_exists('xdebug_get_headers')) {
            $this->assertEmpty(xdebug_get_headers());
        }

        $realm = $this->app->get('REALM');
        $this->app->mset(array(
            'CLI' => false,
            'HEADERS' => array(
                'Location' => $realm,
            ),
            'COOKIE' => array(
                'foo' => 'bar',
            ),
        ));
        $this->updateInitialValue('COOKIE', array(
            'xfoo' => 'xbar',
        ));

        $this->assertEquals($this->app, $this->app->sendHeaders());

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $expected = array(
                'Set-Cookie: foo=bar; path=/; HttpOnly',
                'Set-Cookie: xfoo=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; path=/; HttpOnly',
                'Location: '.$realm,
            );

            $this->assertEquals(array_map('strtolower', $expected), array_map('strtolower', $headers));
        }
    }

    public function testSendContent()
    {
        $this->app->set('RESPONSE', 'foo');
        $this->expectOutputString('foo');
        $this->app->sendContent();
        $this->assertTrue($this->app->get('SENT'));
    }

    public function testSendContentQuiet()
    {
        $this->app->set('QUIET', true);
        $this->expectOutputString('');
        $this->app->sendContent();
        $this->assertFalse($this->app->get('SENT'));
    }

    public function testSendContentChunked()
    {
        $this->app->mset(array(
            'KBPS' => 1,
            'RESPONSE' => 'foo',
        ));
        $this->expectOutputString('foo');
        $this->app->sendContent();
        $this->assertTrue($this->app->get('SENT'));
    }

    public function testSend()
    {
        $this->expectOutputString('');
        $this->app->send();
        $this->assertTrue($this->app->get('SENT'));

        if (function_exists('xdebug_get_headers')) {
            $this->assertEmpty(xdebug_get_headers());
        }
    }

    public function testLoadClass()
    {
        $this->app->mset(array(
            'AUTOLOAD' => array(
                'Fixture\\' => array(FIXTURE.'classes/nsa/', FIXTURE.'classes/nsb/'),
            ),
        ));
        $aClass = 'Fixture\\AClass';
        $bClass = 'Fixture\\BClass';
        $uClass = 'UnknownClass';
        $fuClass = 'Fixture\\UnknownClass';

        $this->assertFalse(class_exists($aClass));
        $this->assertFalse(class_exists($bClass));
        $this->assertFalse(class_exists($uClass));
        $this->assertFalse(class_exists($fuClass));

        $this->app->loadClass($aClass);
        $this->app->loadClass($bClass);
        $this->app->loadClass($uClass);
        $this->app->loadClass($fuClass);

        $this->assertTrue(class_exists($aClass));
        $this->assertTrue(class_exists($bClass));
        $this->assertFalse(class_exists($uClass));
        $this->assertFalse(class_exists($fuClass));
    }

    public function testOn()
    {
        $this->app->on('foo', 'bar');

        $this->assertEquals('bar', $this->app->get('EVENTS.foo'));
    }

    public function testOne()
    {
        $this->app->one('foo', 'bar');

        $this->assertEquals('bar', $this->app->get('EVENTS.foo'));
        $this->assertTrue($this->app->get('EVENTS_ONCE.foo'));
    }

    public function testOff()
    {
        $this->app->one('foo', 'bar')->off('foo');

        $this->assertNull($this->app->get('EVENTS.foo'));
        $this->assertNull($this->app->get('EVENTS_ONCE.foo'));
    }

    public function testInstance()
    {
        $this->registerServiceLoader();
        $this->app->rule('post', array(
            'class' => 'FixtureServices\\BlogPost',
            'service' => false,
        ));
        $this->app->rule('now', 'DateTime');
        $this->app->rule('FixtureServices\\Author', array(
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

        $now = $this->app->instance('now');
        $now2 = $this->app->instance('now2');
        $post = $this->app->instance('post', array(
            'title' => 'Foo',
            'postedDate' => '%now%',
            'postNow' => '%CLI%',
            'author' => 'FixtureServices\\Author',
        ));
        $post2 = $this->app->instance('FixtureServices\\BlogPost', array(
            'Foo',
            false,
            $now,
        ));

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
        $this->registerServiceLoader();
        $this->app->rule('author', 'FixtureServices\\Author');

        $this->assertSame($this->app, $this->app->service('app'));
        $this->assertSame($this->app, $this->app->service(App::class));

        $author = $this->app->service('FixtureServices\\Author');
        $this->assertSame($author, $this->app->service('author'));
        $this->assertSame($author, $this->app->service('FixtureServices\\Author'));
    }

    public function testGrab()
    {
        $this->assertEquals('foo', $this->app->grab('foo'));
        $this->assertEquals(array('FixtureServices\\Author', 'getName'), $this->app->grab('FixtureServices\\Author->getName', false));
        $this->assertEquals(array('FixtureServices\\Author', 'getName'), $this->app->grab('FixtureServices\\Author::getName'));

        $mark = time();
        $grabbed = $this->app->grab('DateTime->getTimestamp');
        $this->assertLessthan(1, $grabbed[0]->getTimestamp() - $mark);
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->app->call('trim', ' foo '));
        $this->assertEquals(' foo ', $this->app->call('trim', array(' foo ;', ';')));

        $mark = time();
        $timestamp = $this->app->call('DateTime->getTimestamp');
        $this->assertLessthan(1, $timestamp - $mark);
    }

    /**
     * @expectedException \BadFunctionCallException
     * @expectedExceptionMessage Call to undefined function xxxfooxxx.
     */
    public function testCallException()
    {
        $this->app->call('xxxfooxxx');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Call to undefined method DateTime::xxxfooxxx.
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
        $event = new Event();
        $this->app->trigger('foo', $event);
        $this->assertFalse($event->isPropagationStopped());

        $this->app->one('foo', function ($event) {
            $event->stopPropagation();
        });
        $event = new Event();
        $this->app->trigger('foo', $event);
        $this->assertTrue($event->isPropagationStopped());
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

        $this->assertEquals($response, $this->app->get('RESPONSE'));

        if ($header) {
            $this->assertContains($header, $this->app->get('HEADERS'));
        }
    }

    public function testErrorHandler()
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
        ))->one('app_error', function ($event) {
            $event->setResponse('foo - '.$event->getStatus());
        });

        $this->app->error(404);

        $this->assertEquals('foo - Not Found', $this->app->get('RESPONSE'));
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

        $this->assertContains('Not Found', $this->app->get('RESPONSE'));
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
        ;

        $this->app->reroute($target);
        $this->assertEquals($expected, $this->app->get('RESPONSE'));

        if ($header && !$cli) {
            $headers = $this->app->get('HEADERS');

            $this->assertTrue(isset($headers['Location']));
            $this->assertEquals($header, $this->app->get('STATUS'));
        }
    }

    public function testRerouteHandler()
    {
        $this->app->one('app_reroute', function (App $app, $event) {
            $app->set('rerouted_to', $event->getUrl());
            $event->stopPropagation();
        });

        $this->app->reroute('/unknown-route');

        $this->assertEquals($this->app->get('BASEURL').'/unknown-route', $this->app->get('rerouted_to'));
    }

    public function runProvider()
    {
        return array(
            array('/unknown', 'HTTP 404 (GET /unknown)'),
            array('/sync-only', 'HTTP 405 (GET /sync-only)'),
            array('/uncallable', 'HTTP 404 (GET /uncallable)'),
            array('/str', 'String response', false),
            array('/arr', '["Array response"]', false),
            array('/call', 'From callable', false),
            array('/null', '', false),
            array('/obj', '', false),
            array('/unlimited/foo/bar/baz', 'foo, bar, baz', false),
            array('/custom/foo/1', 'foo 1', false),
            array('/ajax-access', 'Access granted', false, 1),
            array('/cli-access', 'Access granted', false),
            array('/sync-access', 'Access granted', false, 4),
            array('/uncallable/dinamic/method', 'HTTP 405 (GET /uncallable/dinamic/method)'),
        );
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($path, $expected, $contains = true, $mode = 2)
    {
        $this->app->mset(array(
            'AJAX' => (bool) ($mode & 1),
            'CLI' => (bool) ($mode & 2),
            'QUIET' => true,
            'PATH' => $path,
        ));
        $this->registerRoutes();
        $this->app->run();

        if ($contains) {
            $this->assertContains($expected, $this->app->get('RESPONSE'));
        } else {
            $this->assertEquals($expected, $this->app->get('RESPONSE'));
        }
    }

    public function testRunNoRoute()
    {
        $this->app->mset(array(
            'CLI' => true,
            'QUIET' => true,
        ));

        $this->app->run();
        $this->assertContains('No route specified.', $this->app->get('RESPONSE'));
    }

    public function testRunInterception()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->registerRoutes();

        // Intercept before route
        $this->app->one('app_preroute', function ($event) {
            $event->setResponse('Intercepted');
        })->run();
        $this->assertEquals('Intercepted', $this->app->get('RESPONSE'));
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
        $this->app->one('app_postroute', function ($event) {
            $event->setResponse('Modified');
        })->run();
        $this->assertEquals('Modified', $this->app->get('RESPONSE'));
    }

    public function testRunException()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->app->route('GET /', function () {
            throw new ResponseException(404, 'Data not found.');
        });
        $this->app->run();

        $this->assertEquals(404, $this->app->get('CODE'));
        $this->assertContains('Data not found.', $this->app->get('RESPONSE'));
    }

    public function testMock()
    {
        $this->app->mset(array(
            'QUIET' => true,
        ));
        $this->registerRoutes();

        // mock named route
        $this->app->mock('GET str');
        $this->assertEquals('String response', $this->app->get('RESPONSE'));

        // mock named route with unlimited arg
        $this->app->mock('GET unlimited(p1=foo,p2=bar,p3=baz)');
        $this->assertEquals('foo, bar, baz', $this->app->get('RESPONSE'));

        // mock un-named route
        $this->app->mock('GET /custom/foo/1');
        $this->assertEquals('foo 1', $this->app->get('RESPONSE'));

        // modify body and server
        $this->app->mock('PUT /put', null, array('Custom' => 'foo'), 'put content');
        $this->assertEquals('Put mode', $this->app->get('RESPONSE'));
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

        $this->assertEquals('You are home.', $this->app->get('RESPONSE'));
    }

    public function testHandleException()
    {
        $this->app->set('QUIET', true);

        $this->app->handleException(new \Exception('Exception handled.'));
        $this->assertEquals(500, $this->app->get('CODE'));
        $this->assertContains('Exception handled.', $this->app->get('RESPONSE'));

        $this->app->set('ERROR', false);
        $this->app->handleException(new ResponseException(404));
        $this->assertEquals(404, $this->app->get('CODE'));
        $this->assertContains('HTTP 404 (GET /)', $this->app->get('RESPONSE'));
    }

    public function testHandleError()
    {
        $this->app->set('QUIET', true);
        $this->app->handleError(E_USER_ERROR, 'Error handled.', __FILE__, __LINE__);

        $this->assertContains('Error handled.', $this->app->get('RESPONSE'));
    }

    public function testHash()
    {
        $this->assertEquals(13, strlen(App::hash('foo')));
        $this->assertEquals(13, strlen(App::hash('foobar')));
    }

    public function testMkdir()
    {
        if (file_exists($dir = TEMP.'test-mkdir')) {
            rmdir($dir);
        }

        $this->assertTrue(App::mkdir($dir));
        rmdir($dir);
    }

    public function testRead()
    {
        $this->assertEquals('foo', App::read(FIXTURE.'files/foo.txt'));
    }

    public function testWrite()
    {
        $this->assertEquals(3, App::write(TEMP.'write.txt', 'foo'));
    }

    public function testDelete()
    {
        if (!is_file($file = TEMP.'test-delete.txt')) {
            touch($file);
        }

        $this->assertTrue(App::delete($file));
        $this->assertFalse(App::delete($file));
    }

    public function testRequireFile()
    {
        $this->assertEquals('foo', App::requireFile(FIXTURE.'files/foo.php'));
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
        usleep(1e6 /* one second */);
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
        $this->assertEquals('Foo 1', $this->app->get('RESPONSE'));
        $this->assertEquals(1, $counter);

        // second call
        $this->app->mock('GET /foo');
        $this->assertEquals('Foo 1', $this->app->get('RESPONSE'));
        $this->assertEquals(1, $counter);

        // with modified time check
        $this->app->set('SERVER.HTTP_IF_MODIFIED_SINCE', '+1 year');
        $this->app->mock('GET /foo');
        $this->assertEquals('', $this->app->get('RESPONSE'));
        $this->assertEquals(1, $counter);
        $this->assertEquals('Not Modified', $this->app->get('STATUS'));

        $this->app->cacheReset();
    }

    public function testEllapsedTime()
    {
        $this->assertContains('seconds', $this->app->ellapsedTime());
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

        $this->app->logByCode(E_ERROR, 'E_ERROR');
        $this->assertContains('E_ERROR', file_get_contents($file));
    }

    public function testClassname()
    {
        $this->assertEquals('AppTest', App::classname(AppTest::class));
        $this->assertEquals('AppTest', App::classname($this));
    }

    public function testCast()
    {
        $this->assertSame(true, App::cast('true'));
        $this->assertSame(true, App::cast('TRUE'));
        $this->assertSame(null, App::cast('null'));
        $this->assertSame(0, App::cast('0'));
        $this->assertSame('foo', App::cast('foo'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('fooBar', App::camelcase('foo_bar'));
    }

    public function testSnakeCase()
    {
        $this->assertEquals('foo_bar', App::snakecase('fooBar'));
        $this->assertEquals('foo_bar', App::snakecase('FooBar'));
    }

    public function testTitleCase()
    {
        $this->assertEquals('Foo', App::titleCase('foo'));
        $this->assertEquals('Foo Bar', App::titleCase('foo_bar'));
        $this->assertEquals('FOO Bar', App::titleCase('FOO_bar'));
    }

    public function testStartswith()
    {
        $this->assertTrue(App::startswith('foobar', 'fo'));
        $this->assertFalse(App::startswith('foobar', 'Fo'));
        $this->assertFalse(App::startswith('foobar', 'xo'));
    }

    public function testEndswith()
    {
        $this->assertTrue(App::endswith('foobar', 'ar'));
        $this->assertFalse(App::endswith('foobar', 'aR'));
        $this->assertFalse(App::endswith('foobar', 'as'));
    }

    public function parseExprProvider()
    {
        return array(
            array(
                '',
                array(),
            ),
            array(
                'foo',
                array(
                    'foo' => array(),
                ),
            ),
            array(
                'foo|bar',
                array(
                    'foo' => array(),
                    'bar' => array(),
                ),
            ),
            array(
                'foo:1|bar:arg|baz:[0,1,2]|qux:{"foo":"bar"}|quux:1,arg,[0,1,2],{"foo":"bar"}',
                array(
                    'foo' => array(1),
                    'bar' => array('arg'),
                    'baz' => array(array(0, 1, 2)),
                    'qux' => array(array('foo' => 'bar')),
                    'quux' => array(1, 'arg', array(0, 1, 2), array('foo' => 'bar')),
                ),
            ),
        );
    }

    /**
     * @dataProvider parseExprProvider
     */
    public function testParseExpr($expr, $expected)
    {
        $this->assertEquals($expected, App::parseExpr($expr));
    }

    public function testRegisterErrorExceptionHandler()
    {
        $this->assertSame($this->app, $this->app->registerErrorExceptionHandler());

        restore_error_handler();
        restore_exception_handler();
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
        $this->assertEquals('quux', $this->app->transAlt('foo.baz', null, null, array('foo.qux')));
        $this->assertEquals('foo.baz', $this->app->transAlt('foo.baz', null, null, array('foo.quux')));
        $this->assertEquals('none', $this->app->transAlt('foo.baz', null, 'none', array('foo.quux')));
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

        $this->app->mock('GET /');
        $this->assertEquals('registered from config', $this->app->get('RESPONSE'));

        $this->app->mock('GET /foo');
        $this->assertEquals($this->app->get('BASEURL').'/', $this->app->get('HEADERS.Location'));

        $event = new Event();
        $this->assertFalse($event->isPropagationStopped());
        $this->app->trigger('foo', $event);
        $this->assertTrue($event->isPropagationStopped());

        $event = new Event();
        $this->assertFalse($event->isPropagationStopped());
        $this->app->trigger('foo_once', $event);
        $this->assertTrue($event->isPropagationStopped());

        $this->assertInstanceOf('DateTime', $this->app->service('foo'));
    }

    public function testFilterNullFalse()
    {
        $this->assertTrue(App::filterNullFalse(''));
        $this->assertTrue(App::filterNullFalse(-1));
        $this->assertTrue(App::filterNullFalse(0));
        $this->assertFalse(App::filterNullFalse(null));
        $this->assertFalse(App::filterNullFalse(false));
    }

    public function testWalk()
    {
        $this->assertEquals(array(true, false, false), App::walk(array(1, 'foo', 'bar'), 'is_numeric'));
    }

    public function testColumn()
    {
        $arr = array('foo' => array('foo' => 'bar'), 'bar' => array('foo' => 'baz'));

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), App::column($arr, 'foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage foo.
     */
    public function testThrows()
    {
        App::throws(true, 'foo.');
    }

    public function testThrowsNone()
    {
        App::throws(false, 'foo.');
    }

    public function testMapperParameterConverter()
    {
        $this->app->mset(array(
            'QUIET' => true,
            'AUTOLOAD' => array(
                'FixtureMapper\\' => array(FIXTURE.'classes/mapper/'),
            ),
        ));
        $this->app->registerAutoloader();
        $this->app->rule(Connection::class, array(
            'args' => array(
                'options' => array(
                    'dsn' => 'sqlite::memory:',
                    'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
                ),
            ),
            'boot' => function ($db) {
                $db->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
            },
        ));
        $this->app->on('app_controller_args', function (App $app, Connection $db, GetControllerArgsEvent $event) {
            $converter = new MapperParameterConverter($app, $db, $event->getController(), $event->getArgs());

            $event->setArgs($converter->resolve());
        });
        $this->app->route('GET /users/@user/@info', function (User $user, $info) {
            return 'User with id: '.$user->get('id').', username: '.$user->get('username').', info: '.$info;
        });

        $expected = 'User with id: 1, username: foo, info: first-user';
        $this->app->mock('GET /users/1/first-user');
        $this->assertEquals($expected, $this->app->get('RESPONSE'));

        $expected = 'User with id: 2, username: bar, info: second-user';
        $this->app->mock('GET /users/2/second-user');
        $this->assertEquals($expected, $this->app->get('RESPONSE'));

        $expected = 'Record of user is not found.';
        $this->app->mock('GET /users/4/third-user');
        $this->assertContains($expected, $this->app->get('RESPONSE'));
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
}
