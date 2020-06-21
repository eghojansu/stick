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

namespace Ekok\Stick\Tests;

use Ekok\Stick\Fw;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Fw
 */
final class FwTest extends TestCase
{
    private $fw;

    protected function setUp(): void
    {
        $this->fw = new Fw();
    }

    public function testCreateFromGlobals()
    {
        $fw = Fw::createFromGlobals();

        $this->assertInstanceOf(Fw::class, $fw);
        $this->assertNotSame($this->fw, $fw);
        $this->assertEquals('/', $fw->get('PATH'));
        $this->assertNotNull($fw->get('SERVER'));
    }

    public function testCreate()
    {
        $server = array(
            'SERVER_NAME' => '0.0.0.0',
            'SERVER_ADDR' => '10.10.0.1',
            'REQUEST_URI' => '/base/path/',
            'SCRIPT_NAME' => '/base/path/index.php',
        );
        $fw = Fw::create(null, null, null, null, $server);

        $this->assertInstanceOf(Fw::class, $fw);
        $this->assertNotSame($this->fw, $fw);
        $this->assertEquals('/', $fw->get('PATH'));
        $this->assertEquals('10.10.0.1', $fw->get('HOST'));
        $this->assertEquals($server, $fw->get('SERVER'));
    }

    public function testMagicIsset()
    {
        $this->assertFalse(isset($this->fw->foo));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->fw->foo);
    }

    public function testMagicSet()
    {
        $this->fw->foo = 'bar';

        $this->assertEquals('bar', $this->fw->foo);
    }

    public function testMagicUnset()
    {
        $this->fw->foo = 'bar';
        unset($this->fw->foo);

        $this->assertFalse(isset($this->fw->foo));
    }

    public function testMagicCall()
    {
        $this->fw->set('FUNCTION.foo', 'trim');
        $this->fw->set('METHOD.bar', function ($fw) {
            return $fw->cast('true');
        });

        $this->assertEquals('bar', $this->fw->foo(' bar '));
        $this->assertTrue($this->fw->bar());
    }

    public function testMagicCallException()
    {
        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage("Call to unregistered method: 'baz'.");
        $this->fw->baz();
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

        $this->assertFalse(isset($this->fw['foo']));
    }

    public function testCookieExpiresTime()
    {
        $this->assertEquals(0, Fw::cookieExpiresTime(null));
        $this->assertEquals(10, Fw::cookieExpiresTime(10));
        $this->assertLessThan(time(), Fw::cookieExpiresTime(-10));
        $this->assertGreaterThan(time(), Fw::cookieExpiresTime('tomorrow'));
        $this->assertGreaterThan(time(), Fw::cookieExpiresTime(new \DateTime('tomorrow')));

        $this->expectException('LogicException');
        $this->expectExceptionMessage("Invalid cookie expiration time: 'not a time'.");
        Fw::cookieExpiresTime('not a time');
    }

    /** @dataProvider cookieCreateProvider */
    public function testCookieCreate($expected, string $name, string $value = null, array $options = null)
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());

            Fw::cookieCreate($name, $value, $options);
        } elseif ('/' === $expected[0]) {
            $this->assertRegExp($expected, Fw::cookieCreate($name, $value, $options));
        } else {
            $this->assertEquals($expected, Fw::cookieCreate($name, $value, $options));
        }
    }

    /** @dataProvider headerParseContentProvider */
    public function testHeaderParseContent(array $expected, string $content, bool $sort = true)
    {
        $this->assertEquals($expected, Fw::headerParseContent($content, $sort));
    }

    /** @dataProvider headerQualitySortProvider */
    public function testHeaderQualitySort(int $expected, array $current, array $next)
    {
        $this->assertEquals($expected, Fw::headerQualitySort($current, $next));
    }

    public function testFixSlashes()
    {
        $this->assertEquals('/foo/bar', Fw::fixSlashes('/foo/bar'));
        $this->assertEquals('/foo/bar', Fw::fixSlashes('\\foo\\bar'));
        $this->assertEquals('/foo/bar/', Fw::fixSlashes('\\foo\\bar\\'));
    }

    /** @dataProvider splitProvider */
    public function testSplit(array $expected, $input, bool $noEmpty = true)
    {
        $this->assertEquals($expected, Fw::split($input, $noEmpty));
    }

    /** @dataProvider castProvider */
    public function testCast($expected, $value)
    {
        $this->assertSame($expected, Fw::cast($value));
    }

    public function testExport()
    {
        $this->assertEquals("'foo'", Fw::export('foo'));
    }

    public function testStringify()
    {
        $expected = 'array('.
            "'foo'=>stdClass::__set_state(array('foo'=>*RECURSION*,'baz'=>'qux'))".
            ')';

        $bar = new \stdClass();
        $bar->foo = $bar;
        $bar->baz = 'qux';
        $input = array(
            'foo' => $bar,
        );

        $this->assertEquals($expected, Fw::stringify($input));
    }

    public function testStringifyObject()
    {
        $expected = "stdClass::__set_state(array('foo'=>*RECURSION*,'baz'=>'qux'))";

        $input = new \stdClass();
        $input->foo = $input;
        $input->baz = 'qux';

        $this->assertEquals($expected, Fw::stringifyObject($input));
    }

    public function testStringifyArray()
    {
        $expected = "array('foo','bar')";
        $input = array('foo', 'bar');

        $this->assertEquals($expected, Fw::stringifyArray($input));
    }

    public function testCsv()
    {
        $this->assertEquals("1,2,'foo'", Fw::csv(array(1, 2, 'foo')));
    }

    public function testIniParse()
    {
        $str = file_get_contents(TEST_FIXTURE.'/config/sample.ini');
        $parsed = Fw::iniParse($str);

        $this->assertCount(2, $parsed);
        $this->assertEquals('section.name', $parsed[0]['section']);
        $this->assertEquals('key', $parsed[1]['lval']);
        $this->assertEquals('value', $parsed[1]['rval']);
    }

    public function testIniRead()
    {
        $parsed = Fw::iniRead(TEST_FIXTURE.'/config/sample.ini');

        $this->assertCount(2, $parsed);
        $this->assertEquals('section.name', $parsed[0]['section']);
        $this->assertEquals('key', $parsed[1]['lval']);
        $this->assertEquals('value', $parsed[1]['rval']);
    }

    public function testMakeRef()
    {
        $stdClass = new \stdClass();
        $stdClass->foo = 'bar';
        $data = array(
            'foo' => array(
                'is' => array(
                    'stdClass' => $stdClass,
                ),
            ),
        );

        // no assignment
        $this->assertNull(Fw::makeRef('foo', $var, false, $exists, $parts));
        $this->assertNull($var);
        $this->assertFalse($exists);
        $this->assertEquals(array('foo'), $parts);

        // foo is assigned
        $this->assertNull(Fw::makeRef('foo', $var, true, $exists));
        $this->assertEquals(array('foo' => null), $var);
        $this->assertFalse($exists);

        // recheck foo is assigned
        $this->assertNull(Fw::makeRef('foo', $var, false, $exists));
        $this->assertTrue($exists);

        // add new member
        $this->assertNull(Fw::makeRef('bar', $var));
        $this->assertEquals(array('foo' => null, 'bar' => null), $var);

        // seet deep
        $ref = &Fw::makeRef('foo.bar', $var);
        $ref = 'bar';
        $this->assertEquals('bar', Fw::makeRef('foo.bar', $var));
        $this->assertEquals(array('foo' => array('bar' => 'bar'), 'bar' => null), $var);

        // deep with nothing found
        unset($var);
        $var = $data;
        $this->assertNull(Fw::makeRef('deep.down.but.nothing.found', $var, false, $exists));
        $this->assertFalse($exists);

        // access object properties
        unset($var);
        $var = $data;
        $this->assertEquals('bar', Fw::makeRef('foo.is.stdClass.foo', $var, false, $exists, $parts));
        $this->assertTrue($exists);
        $this->assertEquals(array('foo', 'is', 'stdClass', 'foo'), $parts);
    }

    public function testEncode()
    {
        $this->assertEquals('foo &amp; bar', $this->fw->encode('foo & bar'));
    }

    public function testDecode()
    {
        $this->assertEquals('foo & bar', $this->fw->decode('foo &amp; bar'));
    }

    public function testTransRaw()
    {
        $this->fw->set('LANGUAGE', 'en-US');
        $this->fw->set('LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals('Welcome US', $this->fw->transRaw('welcome'));
        $this->assertNull($this->fw->transRaw('none'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage("Translated message is not a string: 'item'.");
        $this->fw->transRaw('item');
    }

    public function testTrans()
    {
        $this->fw->set('LANGUAGE', 'en-US');
        $this->fw->set('LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals('foo', $this->fw->trans('foo'));
        $this->assertEquals('Welcome Foo', $this->fw->trans('welcome_user', array('%user%' => 'Foo')));
    }

    /** @dataProvider choiceProvider */
    public function testChoice(string $expected, string $message, $count, array $parameters = null)
    {
        $this->fw->set('LANGUAGE', 'en-US');
        $this->fw->set('LOCALES', TEST_FIXTURE.'/dict/');

        $this->assertEquals($expected, $this->fw->choice($message, $count, $parameters));
    }

    public function testConfigAll()
    {
        $this->fw->configAll(TEST_FIXTURE.'/config/sample.ini');

        $this->assertEquals('value', $this->fw->get('section.name.key'));
    }

    public function testConfig()
    {
        $this->fw->config(TEST_FIXTURE.'/config/config.ini', true);
        $check = $this->fw->getAll('foo,bar,qux,quux,glob_foo,glob_bar,directories.suite,sub_config,upper');
        $expected = array(
            'foo' => 'bar',
            'bar' => array('baz', 'qux'),
            'qux' => array(1, 2, 3),
            'quux' => array(1, null, 3),
            'glob_foo' => 'bar',
            'glob_bar' => 'baz, qux',
            'directories.suite' => TEST_FIXTURE,
            'sub_config' => array(
                'foo' => 'bar',
                'bar' => 'baz',
                'not_parsed' => '@{NOT_PARSED}',
            ),
            'upper' => array(
                'foo_up' => 'BAR',
                'bar_up' => 'BAZ',
            ),
        );
        $aliases = array(
            'home' => '/home',
            'dashboard_home' => '/dashboard',
            'dashboard_logout' => '/dashboard/logout',
        );
        $routes = array(
            '/home' => array(
                0 => array(
                    'GET' => array(
                        '*/*' => array('home', 'home', array(), array()),
                    ),
                ),
            ),
            '/dashboard' => array(
                0 => array(
                    'GET' => array(
                        '*/*' => array('DashboardController@home', 'dashboard_home', array('extra1', 'extra2'), array()),
                    ),
                    'POST' => array(
                        '*/*' => array('DashboardController@home', 'dashboard_home', array('extra1', 'extra2'), array()),
                    ),
                ),
            ),
            '/dashboard/logout' => array(
                0 => array(
                    'GET' => array(
                        '*/*' => array('DashboardController@logout', 'dashboard_logout', array('extra1', 'extra2'), array()),
                    ),
                ),
            ),
        );

        $this->assertSame($expected, $check);
        $this->assertSame($routes, $this->fw->get('ROUTES'));
        $this->assertSame($aliases, $this->fw->get('ALIASES'));
    }

    public function testRef()
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $server = array(
            'foo' => $obj,
            'bar' => array('bar' => 'baz'),
        );

        $fw = Fw::create(null, null, null, null, $server);

        $this->assertSame($server, $fw->ref('SERVER'));
        $this->assertSame($obj, $fw->ref('SERVER.foo'));
        $this->assertSame(array('bar' => 'baz'), $fw->ref('SERVER.bar'));
        $this->assertSame('baz', $fw->ref('SERVER.bar.bar'));
        $this->assertNull($fw->ref('SERVER.bar.bar.baz'));
        $this->assertSame('bar', $fw->ref('SERVER.foo.foo'));

        $ref = &$fw->ref('SERVER.my_foo', true, $exists);
        $ref = 'bar';
        unset($ref);
        $this->assertFalse($exists);
        $this->assertSame('bar', $fw->ref('SERVER.my_foo'));

        $ref = &$fw->ref('SERVER.foo.my_foo', true, $exists);
        $ref = 'bar';
        unset($ref);
        $this->assertFalse($exists);
        $this->assertSame('bar', $fw->ref('SERVER.foo.my_foo'));

        $this->assertCount(0, $fw->ref('SESSION'));
    }

    public function testUnref()
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $server = array(
            'foo' => $obj,
            'bar' => array('bar' => 'baz'),
        );

        $fw = Fw::create(null, null, null, null, $server);
        $fw->unref('SERVER.foo');
        $fw->unref('PATH');
        $fw->unref('SESSION');

        $this->assertNull($fw->ref('PATH'));
        $this->assertNull($fw->ref('SERVER.foo'));
    }

    public function testHas()
    {
        $this->assertTrue($this->fw->has('PATH'));
        $this->assertTrue($this->fw->has('AJAX'));
        $this->assertFalse($this->fw->has('ajax'));
        $this->assertFalse($this->fw->has('FOO'));
    }

    public function testGet()
    {
        $this->assertFalse($this->fw->get('AJAX'));
        $this->assertInstanceOf('stdClass', $this->fw->get('stdClass'));

        $this->fw->set('GETTER.foo', function () {
            return 'bar';
        });
        $this->fw->set('CREATOR.bar', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $this->fw->get('foo'));
        $this->assertEquals('bar', $this->fw->get('bar'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->fw->set('foo', 'bar')->get('foo'));
        $this->assertEquals(array('bar'), $this->fw->set('HEADER.foo', 'bar')->get('HEADER.foo'));
    }

    public function testRem()
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $server = array(
            'foo' => $obj,
            'bar' => array('bar' => 'baz'),
        );

        $fw = Fw::create(null, null, null, null, $server);

        $this->assertNull($fw->set('foo', 'bar')->rem('foo')->get('foo'));
        $this->assertEquals('/', $fw->rem('PATH')->get('PATH'));
        $this->assertNull($fw->rem('SESSION')->get('SESSION'));
    }

    public function testHasAll()
    {
        $this->assertTrue($this->fw->hasAll('PATH,AJAX'));
        $this->assertFalse($this->fw->hasAll('foo'));
    }

    public function testGetAll()
    {
        $this->assertEquals(array(
            'requestPath' => '/',
            'AJAX' => false,
        ), $this->fw->getAll(array(
            'PATH' => 'requestPath',
            'AJAX',
        )));
    }

    public function testSetAll()
    {
        $this->assertEquals(array(
            'bar' => 'baz',
            'baz' => 'qux',
        ), $this->fw->setAll(array(
            'bar' => 'baz',
            'baz' => 'qux',
        ), 'foo.')->get('foo'));
    }

    public function testRemAll()
    {
        $this->fw->set('foo.bar', 'bar');

        $this->assertCount(0, $this->fw->remAll('bar', 'foo.')->get('foo'));
    }

    public function testCopy()
    {
        $this->assertEquals('/', $this->fw->copy('PATH', 'foo')->get('foo'));
    }

    public function testCut()
    {
        $this->fw->set('foo', 'bar');
        $foo = $this->fw->cut('foo');

        $this->assertEquals('bar', $foo);
        $this->assertFalse($this->fw->has('foo'));
    }

    public function testMove()
    {
        $this->fw->set('foo', 'bar');
        $this->fw->move('foo', 'bar');

        $this->assertFalse($this->fw->has('foo'));

        $this->assertEquals('bar', $this->fw->get('bar'));
        $this->assertTrue($this->fw->has('bar'));
    }

    public function testPrepend()
    {
        $this->assertEquals('baz', $this->fw->prepend('foo', 'baz', false)->get('foo'));
        $this->assertEquals('barbaz', $this->fw->prepend('foo', 'bar', false)->get('foo'));
        $this->assertEquals(array('foo', 'barbaz'), $this->fw->prepend('foo', 'foo', true)->get('foo'));
    }

    public function testAppend()
    {
        $this->assertEquals('foo', $this->fw->append('foo', 'foo', false)->get('foo'));
        $this->assertEquals('foobar', $this->fw->append('foo', 'bar', false)->get('foo'));
        $this->assertEquals(array('foobar', 'baz'), $this->fw->append('foo', 'baz', true)->get('foo'));
    }

    public function testGrabExpression()
    {
        $this->fw->set('GETTER.foo', function () {
            return new \DateTime('2000-10-20');
        });
        $foo = $this->fw->grabExpression('foo@format');
        $bar = $this->fw->grabExpression('DateTime:createFromFormat');
        $baz = $this->fw->grabExpression('trim');

        $this->assertEquals('2000-10-20', $foo('Y-m-d'));
        $this->assertEquals('2000-10-20', $bar('Y-m-d', '2000-10-20')->format('Y-m-d'));
        $this->assertEquals('foo', $baz(' foo '));
    }

    public function testChain()
    {
        $a = function () {
            return 'foo';
        };
        $b = function ($fw, $prev) {
            $fw->set('result', $prev.'bar');
        };

        $this->assertSame('foobar', $this->fw->chain($a, $b)->get('result'));
    }

    public function testCall()
    {
        $callback = 'DateTime:createFromFormat';
        $arguments = array('Y-m-d', '2000-10-20');

        $this->assertEquals('2000-10-20', $this->fw->call($callback, ...$arguments)->format('Y-m-d'));
    }

    public function testOn()
    {
        $this->assertCount(1, $this->fw->on('foo', 'bar')->get('EVENT.foo'));
    }

    public function testOne()
    {
        $this->assertTrue($this->fw->one('foo', 'bar')->get('EVENT.foo.0.1'));
    }

    public function testOff()
    {
        $this->assertCount(0, $this->fw->on('foo', 'bar')->off('foo')->get('EVENT'));
    }

    public function testDispatch()
    {
        $this->assertFalse($this->fw->dispatch('foo'));

        $this->fw->on('foo', function ($prev) {
            return $prev + 1;
        });
        $this->fw->on('foo', function ($prev) {
            return $this->fw->get('EVENT_DISPATCH_RESULT') + $prev + 1;
        });
        $this->assertTrue($this->fw->dispatch('foo', array(0), $result, true));
        $this->assertEquals(2, $result);

        $this->assertFalse($this->fw->dispatch('foo'));

        $this->fw->one('foo', function () {
            return 2;
        });
        $this->assertTrue($this->fw->dispatch('foo', null, $result));
        $this->assertEquals(2, $result);

        $this->assertFalse($this->fw->dispatch('foo'));
    }

    public function testLog()
    {
        $dir = TEST_TEMP.'/logs/';
        $file = $dir.'log_'.date('Y-m-d').'.txt';

        testRemoveTemp('logs');

        $this->fw->set('LOG.directory', $dir);
        $this->fw->set('LOG.level_threshold', 'info');

        $this->fw->log('info', 'log info', array('info' => 'data'));
        $this->fw->log('warning', 'log warning', array('warning' => 'data'));

        // enable flush
        $this->fw->set('LOG.flush', true);
        $this->fw->log('error', 'log error', array('error' => 'data'));

        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertStringContainsString('log info', $content);
        $this->assertStringContainsString('"info": "data"', $content);

        $this->assertStringContainsString('log warning', $content);
        $this->assertStringContainsString('"warning": "data"', $content);

        $this->assertStringContainsString('log error', $content);
        $this->assertStringContainsString('"error": "data"', $content);
    }

    public function testLogException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage("Invalid log level: 'foo'.");

        $this->fw->log('foo', 'bar');
    }

    public function testHive()
    {
        $this->assertCount(62, $this->fw->hive());
    }

    public function testElapsed()
    {
        $this->assertGreaterThan(0, $this->fw->elapsed());
    }

    public function testAsset()
    {
        $this->fw->setAll(array(
            'PREFIX' => '/foo',
            'MAP' => array(
                'foo' => '/bar',
            ),
            'ABSOLUTE' => true,
        ), 'ASSET_');

        $this->assertEquals('http://localhost/foo/bar', $this->fw->asset('foo'));

        $this->fw->set('ASSET_VERSION', 'dynamic');
        $this->assertStringStartsWith('http://localhost/foo/bar?v', $this->fw->asset('foo'));

        $this->fw->set('ASSET_VERSION', 'v=2');
        $this->assertStringStartsWith('http://localhost/foo/bar?v=2', $this->fw->asset('foo'));
    }

    public function testBaseUrl()
    {
        $this->fw->set('PORT', 80);
        $this->assertEquals('http://localhost/foo', $this->fw->baseUrl('foo'));

        $this->fw->set('PORT', 8080);
        $this->assertEquals('http://localhost:8080/foo', $this->fw->baseUrl('foo'));
        $this->assertEquals('http://localhost:8080/foo', $this->fw->baseUrl('/foo'));
        $this->assertEquals('http://localhost:8080', $this->fw->baseUrl());
    }

    public function testBasePath()
    {
        $this->assertEquals('/foo', $this->fw->basePath('foo'));
        $this->assertEquals('/foo', $this->fw->basePath('/foo'));
        $this->assertEquals('', $this->fw->basePath());
    }

    public function testSiteUrl()
    {
        $this->assertEquals('http://localhost/foo', $this->fw->siteUrl('foo'));

        $this->fw->set('FRONT', 'page.php');
        $this->fw->set('PORT', 80);
        $this->assertEquals('http://localhost/page.php/foo', $this->fw->siteUrl('foo'));

        $this->fw->set('PORT', 8080);
        $this->assertEquals('http://localhost:8080/page.php/foo', $this->fw->siteUrl('foo'));
        $this->assertEquals('http://localhost:8080/page.php/foo', $this->fw->siteUrl('/foo'));
        $this->assertEquals('http://localhost:8080/page.php', $this->fw->siteUrl());
    }

    public function testSitePath()
    {
        $this->assertEquals('/foo', $this->fw->sitePath('foo'));

        $this->fw->set('FRONT', 'page.php');
        $this->assertEquals('/page.php/foo', $this->fw->sitePath('foo'));
        $this->assertEquals('/page.php/foo', $this->fw->sitePath('/foo'));
        $this->assertEquals('/page.php', $this->fw->sitePath());
    }

    public function testPath()
    {
        $this->fw->set('ALIASES.path', '/foo/bar');

        $this->assertEquals('/foo?foo=bar', $this->fw->path('foo', array('foo' => 'bar')));
        $this->assertEquals('/foo/bar', $this->fw->path('path'));
    }

    /** @dataProvider aliasProvider */
    public function testAlias($env, $expected, string $alias, $parameters = null)
    {
        if (is_array($env)) {
            $this->fw->setAll($env);
        } else {
            $this->fw->set('ALIASES.'.$alias, $env);
        }

        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());

            $this->fw->alias($alias, $parameters);
        } else {
            $this->assertEquals($expected, $this->fw->alias($alias, $parameters));
        }
    }

    public function testRouteAll()
    {
        $dashboard = function () {};
        $expectedAliases = array(
            'home' => '/home',
            'dashboard' => '/dashboard',
        );
        $expectedRoutes = array(
            '/home' => array(
                0 => array(
                    'GET' => array(
                        '*/*' => array('home', 'home', array(), array()),
                    ),
                ),
            ),
            '/dashboard' => array(
                0 => array(
                    'GET' => array(
                        '*/*' => array($dashboard, 'dashboard', array(), array()),
                    ),
                ),
            ),
        );
        $routes = array(
            'GET home /home' => 'home',
            'GROUP /dashboard' => null,
            'GET dashboard' => $dashboard,
        );
        $this->fw->routeAll($routes);

        $this->assertSame($expectedAliases, $this->fw->get('ALIASES'));
        $this->assertSame($expectedRoutes, $this->fw->get('ROUTES'));
    }

    public function testRoute()
    {
        $this->fw->route('GET /', 'handler');

        $this->assertNull($this->fw->get('ALIASES'));
        $this->assertCount(1, $this->fw->get('ROUTES'));
    }

    /** @dataProvider routeExceptionProvider */
    public function testRouteException(string $message, string $route, $handler, ...$extras)
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage($message);

        $this->fw->route($route, $handler, ...$extras);
    }

    public function testRedirectAll()
    {
        $patterns = array(
            'get /home' => '/destination',
            'get /user' => '/new/destination',
        );
        $this->fw->redirectAll($patterns);

        $this->assertCount(2, $this->fw->get('ROUTES'));
    }

    public function testRedirect()
    {
        $this->fw->redirect('GET /home', '/destination');
        $this->fw->mock('GET /home');

        $expected = 'http://localhost/destination';

        $this->assertEquals($expected, $this->fw->get('HEADER.Location.0'));
    }

    /** @dataProvider routeMatchProvider */
    public function testRouteMatch(?array $expected, string $pattern, string $path)
    {
        $this->fw->set('PATH', $path);

        $this->assertEquals($expected, $this->fw->routeMatch($pattern));
    }

    /** @dataProvider findRouteProvider */
    public function testFindRoute(?array $routes, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($routes) {
            $this->fw->routeAll($routes);
        }

        $this->assertCount(6, $this->fw->findRoute());
    }

    public function testTrace()
    {
        $trace = debug_backtrace();
        // no file
        $trace[] = array();

        $expected = 'Ekok\Stick\Tests\FwTest->testTrace()';
        $this->fw->set('DEBUG', 3);

        $this->assertStringContainsString($expected, $this->fw->trace($trace));
    }

    public function testStatus()
    {
        $this->fw->status(404);

        $this->assertEquals('Not Found', $this->fw->get('TEXT'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid HTTP Code: 1000.');
        $this->fw->status(1000);
    }

    public function testSendHeader()
    {
        $this->fw->set('CONTENT', 'foo');
        $this->fw->set('MIME', 'text/html');

        $this->assertFalse($this->fw->get('HEADER_SENT'));

        $this->fw->sendHeader();
        $this->assertTrue($this->fw->get('HEADER_SENT'));

        header_remove();
    }

    public function testSendContent()
    {
        $this->expectOutputString('foo');
        $this->fw->set('CONTENT', 'foo');
        $this->fw->sendContent();
    }

    public function testSendResult()
    {
        $this->fw->set('RESULT', function ($fw) {
            $fw->set('result', 'foo');
        });
        $this->fw->sendResult();

        $this->assertEquals('foo', $this->fw->get('result'));
    }

    public function testSend()
    {
        $this->expectOutputString('foo');
        $this->fw->set('CONTENT', 'foo');
        $this->fw->send();
    }

    /** @dataProvider rerouteProvider */
    public function testReroute(string $expected, $target, array $routes = null, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($routes) {
            $this->fw->routeAll($routes);
        }

        $this->fw->reroute($target);

        $this->assertEquals($expected, $this->fw->get('HEADER.Location.0'));
    }

    /** @dataProvider errorProvider */
    public function testError($expected, int $code, string $message = null, array $originalTrace = null, array $hive = null, callable $handler = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        if ($handler) {
            $this->fw->on('fw.error', $handler);
        }

        if ($expected instanceof \Exception) {
            $this->expectException($expected);
            $this->expectExceptionMessage($expected->getMessage());

            $this->fw->error($code, $message, $originalTrace);
        } else {
            $this->fw->error($code, $message, $originalTrace);

            $this->assertStringContainsString($expected, $this->fw->get('CONTENT'));
        }
    }

    /** @dataProvider mockProvider */
    public function testMock($expected, string $route, array $arguments = null, string $body = null, array $server = null)
    {
        $this->fw->route('GET|POST|PUT home /home', function ($fw) {
            return implode(' ', array_filter(array(
                $fw->get('VERB'),
                $fw->get('PATH'),
                http_build_query($fw->get('GET') ?? array()),
                http_build_query($fw->get('POST') ?? array()),
                $fw->get('BODY'),
                http_build_query($fw->get('SERVER.CUSTOM') ?? array()),
            )));
        });

        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());

            $this->fw->mock($route, $arguments, $body, $server);
        } else {
            $this->fw->mock($route, $arguments, $body, $server);

            $this->assertEquals($expected, $this->fw->get('CONTENT'));
        }
    }

    /** @dataProvider runProvider */
    public function testRun(string $expected, array $hive = null)
    {
        if ($hive) {
            $this->fw->setAll($hive);
        }

        $this->fw->route('GET /', function () {
            return 'home';
        });
        $this->fw->route('GET /error', function () {
            throw new \LogicException('error thrown');
        });
        $this->fw->route('GET /callable', function () {
            return function () {
                echo 'callable result';
            };
        });
        $this->fw->route('GET /accept *text/html;q=0.5', function () {
            return 'html response';
        });
        $this->fw->route('GET /accept *application/json', function () {
            return 'json response';
        });

        if ('/' === $expected[0]) {
            $this->expectOutputRegex($expected);
        } else {
            $this->expectOutputString($expected);
        }

        $this->fw->run();
    }

    /** @dataProvider setGetInternalProvider */
    public function testSetGetInternal($expected, string $key, array $set = null)
    {
        if ($set) {
            $this->fw->setAll($set);
        }

        $this->assertEquals($expected, $this->fw->get($key));
    }

    public function testHandleShutdown()
    {
        $workingDir = getcwd();

        // first call
        $this->fw->handleShutdown($workingDir);
        $this->assertTrue(true);

        $this->fw->set('HANDLE_SHUTDOWN', false);
        @$result = 1 / 0;
        $this->fw->handleShutdown($workingDir);
        error_clear_last();
        $this->assertTrue(true);
    }

    public function headerParseContentProvider(): array
    {
        return array(
            'empty content' => array(
                array(),
                '',
            ),
            'actual content' => array(
                array(
                    'text/html' => array(),
                    'image/webp' => array(),
                    'image/apng' => array(),
                    'application/xhtml+xml' => array(),
                    'application/xml' => array('q' => 0.9),
                    'application/signed-exchange' => array('v' => 'b3', 'q' => 0.9),
                    '*/*' => array('q' => 0.8),
                ),
                'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            ),
            'no sort' => array(
                array(
                    'text/html' => array(),
                    'application/xhtml+xml' => array(),
                    'application/xml' => array('q' => 0.9),
                    'image/webp' => array(),
                    'image/apng' => array(),
                    '*/*' => array('q' => 0.8),
                    'application/signed-exchange' => array('v' => 'b3', 'q' => 0.9),
                ),
                'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                false,
            ),
            'default test' => array(
                array(
                    'a' => array(),
                    'b' => array('q' => 1),
                ),
                'a,b;q=1',
            ),
        );
    }

    public function headerQualitySortProvider()
    {
        return array(
            'equal' => array(
                0,
                array('q' => 1),
                array('q' => 1),
            ),
            'greater than' => array(
                1,
                array('q' => 1),
                array('q' => 2),
            ),
            'less than' => array(
                -1,
                array('q' => 2),
                array('q' => 1),
            ),
            'using level' => array(
                1,
                array('level' => 1),
                array('level' => 2),
            ),
        );
    }

    public function splitProvider()
    {
        return array(
            'no empty' => array(
                array('foo', 'bar'),
                'foo,;|bar',
            ),
            'with empty' => array(
                array('foo', '', '', 'bar'),
                'foo,;|bar',
                false,
            ),
            'array' => array(
                array('foo'),
                array('foo'),
            ),
            'scalar' => array(
                array('0'),
                0,
            ),
        );
    }

    public function castProvider()
    {
        return array(
            'original' => array(
                'foo',
                'foo',
            ),
            'number' => array(
                20,
                '20',
            ),
            'hexa' => array(
                20,
                '0x14',
            ),
            'octal' => array(
                20,
                '024',
            ),
            'binary' => array(
                20,
                '0b10100',
            ),
            'constant' => array(
                true,
                'true',
            ),
            'array' => array(
                array(),
                array(),
            ),
        );
    }

    public function stringifyProvider()
    {
        return array(
            'complete' => array(
                'array()',
                array(
                    array('foo' => 'bar'),
                ),
            ),
        );
    }

    public function aliasProvider()
    {
        return array(
            'no parameter' => array(
                '/',
                '/',
                'home',
            ),
            'parameters as query' => array(
                '/',
                '/?foo=bar',
                'home',
                array(
                    'foo' => 'bar',
                ),
            ),
            'parameters' => array(
                '/foo/@bar/@baz:digit/@qux:(^a\\d+$)/@quux*/@corge/@bleh*',
                '/foo/bar/11/a11/a/b/c/data/all/in/here',
                'home',
                array(
                    'bar' => 'bar',
                    'baz' => 11,
                    'qux' => 'a11',
                    'quux' => array('a', 'b', 'c'),
                    'corge' => 'data',
                    'bleh' => array('all', 'in', 'here'),
                ),
            ),
            'parameters as string' => array(
                '/foo/@bar/@baz:digit/@qux:(^a\\d+$)/@quux*/@corge/@bleh*',
                '/foo/bar/11/a11/a/b/c/data/all/in/here',
                'home',
                'bar=bar&baz=11&qux=a11&quux[]=a&quux[]=b&quux[]=c&corge=data&bleh[]=all&bleh[]=in&bleh[]=here',
            ),
            'invalid route' => array(
                null,
                new \LogicException("Route not exists: 'home'."),
                'home',
            ),
            'no parameter' => array(
                '/foo/@bar',
                new \LogicException('Parameter should be provided (bar@home).'),
                'home',
            ),
            'invalid parameter' => array(
                '/foo/@bar:digit',
                new \LogicException("Invalid route parameter: 'baz' (bar@home)"),
                'home',
                'bar=baz',
            ),
        );
    }

    public function routeExceptionProvider()
    {
        return array(
            'invalid pattern' => array(
                "Invalid route pattern: 'get*'.",
                'get*',
                null,
            ),
            'no path' => array(
                "Route contains no path: 'get'.",
                'get',
                null,
            ),
            'invalid alias' => array(
                "Invalid route alias: 'foo'.",
                'get foo',
                null,
            ),
        );
    }

    public function routeMatchProvider()
    {
        return array(
            'static' => array(
                array(),
                '/foo',
                '/foo',
            ),
            'simple' => array(
                array('foo' => 'bar'),
                '/foo/@foo',
                '/foo/bar',
            ),
            'full' => array(
                array(
                    'id' => '123',
                    'trans' => 'word',
                    'all' => 'all/next/word',
                    'normal' => 'normal',
                    'rest' => 'rest',
                ),
                '/foo/@id:digit/@trans:(\w+)/@all*/@normal/@rest*',
                '/foo/123/word/all/next/word/normal/rest',
            ),
            'no match' => array(
                null,
                '/foo',
                '/bar',
            ),
        );
    }

    public function findRouteProvider()
    {
        return array(
            'not found' => array(
                array(
                    'GET /foo' => 'handler',
                ),
            ),
            'not found by request type check' => array(
                array(
                    'GET / _ajax' => 'handler',
                ),
            ),
            'not allowed' => array(
                array(
                    'POST /' => 'handler',
                ),
            ),
            'found' => array(
                array(
                    'GET /' => 'handler',
                ),
            ),
        );
    }

    public function rerouteProvider()
    {
        return array(
            'refresh' => array(
                'http://localhost/',
                null,
            ),
            'array' => array(
                'http://localhost/',
                array('home'),
                array(
                    'GET home /' => 'home',
                ),
            ),
            'alias' => array(
                'http://localhost/',
                'home',
                array(
                    'GET home /' => 'home',
                ),
            ),
            'expression' => array(
                'http://localhost/foo?bar=baz',
                'home(foo=foo,bar=baz)',
                array(
                    'GET home /@foo' => 'home',
                ),
            ),
            'path' => array(
                'http://localhost/foo',
                '/foo',
            ),
            'absolute' => array(
                'http://example.com',
                'http://example.com',
            ),
            'event' => array(
                '/foo changed',
                '/foo',
                null,
                array(
                    'EVENT.fw.reroute' => function ($fw, $url) {
                        $fw->set('HEADER.Location', $url.' changed');

                        return true;
                    },
                ),
            ),
        );
    }

    public function choiceProvider()
    {
        return array(
            array('No green apple', 'apple', 0, array('%color%' => 'green')),
            array('One green apple', 'apple', 1, array('%color%' => 'green')),
            array('Two green apples', 'apple', 2, array('%color%' => 'green')),
            array('Many green apples', 'apple', 3, array('%color%' => 'green')),
            array('Invalid item', 'item', -1),
            array('No item', 'item', 0),
            array('One item', 'item', 1),
            array('Two items', 'item', 2),
            array('More items', 'item', 3),
            array('More items', 'item', 4),
        );
    }

    public function cookieCreateProvider()
    {
        return array(
            'delete' => array(
                'foo=deleted; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0',
                'foo',
            ),
            'delete array' => array(
                'foo[bar]=deleted; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0',
                'foo.bar',
            ),
            'normal' => array(
                'foo=bar',
                'foo',
                'bar',
            ),
            'complete' => array(
                '/foo=bar; Expires=.+ GMT; Max-Age=0; Domain=www\.example\.com; Path=\/path; Secure; HttpOnly; SameSite=Lax/',
                'foo',
                'bar',
                array(
                    'lifetime' => 'Oct 20, 2000 20:01:02',
                    'path' => '/path',
                    'domain' => 'www.example.com',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ),
            ),
            'exception' => array(
                new \LogicException("Invalid cookie samesite: 'foo'."),
                'foo',
                'bar',
                array(
                    'samesite' => 'foo',
                ),
            ),
        );
    }

    public function errorProvider()
    {
        return array(
            'normal' => array(
                'GET / (HTTP 404)',
                404,
            ),
            'normal ajax' => array(
                '"message":"from ajax"',
                404,
                'from ajax',
                null,
                array(
                    'AJAX' => true,
                ),
            ),
            'normal override' => array(
                'error behaviour override',
                404,
                null,
                null,
                null,
                function () {
                    return 'error behaviour override';
                },
            ),
            'override trigger another error' => array(
                'another error',
                404,
                null,
                null,
                null,
                function () {
                    throw new \LogicException('another error');
                },
            ),
        );
    }

    public function mockProvider()
    {
        return array(
            'normal' => array(
                'GET /home',
                'GET /home',
            ),
            'alias' => array(
                'GET /home',
                'GET home',
            ),
            'alias with queries' => array(
                'GET /home with=queries&and=others',
                'GET home(with=queries,and=others)',
            ),
            'GET with arguments' => array(
                'GET /home with=queries and body with=server',
                'GET home',
                array('with' => 'queries'),
                'and body',
                array('CUSTOM' => array('with' => 'server')),
            ),
            'POST with arguments' => array(
                'POST /home with=queries with=data',
                'POST home(with=queries)',
                array('with' => 'data'),
            ),
            'PUT with arguments' => array(
                'PUT /home with=queries with=body',
                'PUT home(with=queries)',
                array('with' => 'body'),
            ),
            'exception' => array(
                new \LogicException("Invalid mocking pattern: 'GET'."),
                'GET',
            ),
        );
    }

    public function runProvider()
    {
        return array(
            'normal' => array(
                'home',
            ),
            'exception' => array(
                '/error thrown/',
                array(
                    'PATH' => '/error',
                ),
            ),
            'override on boot' => array(
                'overriden on boot',
                array(
                    'EVENT.fw.boot' => function ($fw) {
                        $fw->set('CONTENT', 'overriden on boot');

                        return false;
                    },
                ),
            ),
            'override controller and arguments' => array(
                'arg1 arg2',
                array(
                    'EVENT.fw.controller' => function ($fw) {
                        return function ($fw, $arguments) {
                            return implode(' ', $arguments);
                        };
                    },
                    'EVENT.fw.controller_arguments' => function ($fw) {
                        return array('arg1', 'arg2');
                    },
                ),
            ),
            'callable result' => array(
                'callable result',
                array(
                    'PATH' => '/callable',
                ),
            ),
            'not found' => array(
                '/GET \/none \(HTTP 404\)/',
                array(
                    'PATH' => '/none',
                ),
            ),
            'not allowed' => array(
                '/POST \/ \(HTTP 405\)/',
                array(
                    'VERB' => 'POST',
                ),
            ),
            'request html' => array(
                'html response',
                array(
                    'PATH' => '/accept',
                    'SERVER.HTTP_ACCEPT' => 'text/html',
                ),
            ),
            'request json' => array(
                'json response',
                array(
                    'PATH' => '/accept',
                    'SERVER.HTTP_ACCEPT' => 'application/json',
                ),
            ),
            'request any application response' => array(
                'json response',
                array(
                    'PATH' => '/accept',
                    'SERVER.HTTP_ACCEPT' => 'application/*',
                ),
            ),
            'request unsupported mime, fallback to highest quality' => array(
                'json response',
                array(
                    'PATH' => '/accept',
                    'SERVER.HTTP_ACCEPT' => 'text/plain',
                ),
            ),
        );
    }

    public function setGetInternalProvider()
    {
        return array(
            'accept' => array(
                array('*/*' => array()),
                'ACCEPT',
            ),
            'agent' => array(
                'none',
                'AGENT',
            ),
            'ajax' => array(
                false,
                'AJAX',
            ),
            'ip' => array(
                '::1',
                'IP',
            ),
            'ip behind proxy' => array(
                '10.10.0.1',
                'IP',
                array(
                    'SERVER.HTTP_X_FORWARDED_FOR' => '10.10.0.1',
                ),
            ),
            'set cookie' => array(
                array('cookie' => 'value'),
                'COOKIE',
                array(
                    'COOKIE' => array('cookie' => 'value'),
                ),
            ),
            'set header' => array(
                array('header' => array('content')),
                'HEADER',
                array(
                    'HEADER' => array('header' => 'content'),
                ),
            ),
            'set events' => array(
                array('event' => array(array('handler', true))),
                'EVENT',
                array(
                    'EVENT' => array('event' => array('handler', true)),
                ),
            ),
            'set jar' => array(
                10,
                'JAR.lifetime',
                array(
                    'JAR.lifetime' => 10,
                ),
            ),
            'set encoding' => array(
                'UTF-8',
                'ENCODING',
                array(
                    'ENCODING' => 'UTF-8',
                ),
            ),
            'set timezone' => array(
                'Asia/Jakarta',
                'TZ',
                array(
                    'TZ' => 'Asia/Jakarta',
                ),
            ),
            'set language' => array(
                'id-ID',
                'LANGUAGE',
                array(
                    'LANGUAGE' => 'id-ID',
                ),
            ),
            'set locales' => array(
                TEST_FIXTURE.'/dict',
                'LOCALES',
                array(
                    'LOCALES' => TEST_FIXTURE.'/dict',
                ),
            ),
            'set log' => array(
                false,
                'LOG.append_context',
                array(
                    'LOG.append_context' => false,
                ),
            ),
        );
    }
}
