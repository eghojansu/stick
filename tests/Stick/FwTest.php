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

use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;

class FwTest extends MyTestCase
{
    public function teardown(): void
    {
        header_remove();
        $this->sessdestroy();
        $this->clear($this->tmp());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::hash
     */
    public function testHash($expected, $text, $suffix = null)
    {
        $this->assertEquals($expected, Fw::hash($text, $suffix));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::snakeCase
     */
    public function testSnakeCase($expected, $text)
    {
        $this->assertEquals($expected, Fw::snakeCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::camelCase
     */
    public function testCamelCase($expected, $text)
    {
        $this->assertEquals($expected, Fw::camelCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::pascalCase
     */
    public function testPascalCase($expected, $text)
    {
        $this->assertEquals($expected, Fw::pascalCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::titleCase
     */
    public function testTitleCase($expected, $text)
    {
        $this->assertEquals($expected, Fw::titleCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::dashCase
     */
    public function testDashCase($expected, $text)
    {
        $this->assertEquals($expected, Fw::dashCase($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::classname
     */
    public function testClassname($expected, $class)
    {
        $this->assertEquals($expected, Fw::classname($class));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::split
     */
    public function testSplit($expected, $var, $delimiter = null)
    {
        $this->assertEquals($expected, Fw::split($var, $delimiter));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::join
     */
    public function testJoin($expected, $var, $glue = ',')
    {
        $this->assertEquals($expected, Fw::join($var, $glue));
    }

    public function testMkdir()
    {
        $dir = $this->tmp();

        $this->assertFalse(is_dir($dir));
        $this->assertTrue(Fw::mkdir($dir));
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(Fw::mkdir($dir));
        $this->assertTrue(is_dir($dir));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::read
     */
    public function testRead($expected, $file, $normalizeLinefeed = false)
    {
        $this->assertEquals($expected, Fw::read($file, $normalizeLinefeed));
    }

    public function testWrite()
    {
        $file = $this->tmp('/foo.txt', true);

        $this->assertEquals(3, Fw::write($file, 'foo'));
        $this->assertEquals(3, Fw::write($file, 'bar', true));
        $this->assertEquals('foobar', file_get_contents($file));
    }

    public function testDelete()
    {
        $file = $this->tmp('/foo.txt', true);
        touch($file);

        $this->assertFileExists($file);
        $this->assertTrue(Fw::delete($file));
        $this->assertFileNotExists($file);
        $this->assertFalse(Fw::delete($file));
    }

    public function testFiles()
    {
        $dir = $this->tmp(null, true);

        $this->assertInstanceOf('RecursiveIteratorIterator', Fw::files($dir));

        // update
        $dir .= '/foo';
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Directory not exists: '.$dir);
        Fw::files($dir);
    }

    public function testRequireFile()
    {
        $this->assertEquals('foo', Fw::requireFile($this->fixture('/files/foo.php')));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cast
     */
    public function testCast($expected, $var)
    {
        $this->assertSame($expected, Fw::cast($var));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::stringify
     */
    public function testStringify($expected, $arg)
    {
        $this->assertEquals($expected, Fw::stringify($arg));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::csv
     */
    public function testCsv($expected, $arguments)
    {
        $this->assertEquals($expected, Fw::csv($arguments));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::resolveRequestHeaders
     */
    public function testResolveRequestHeaders($expected, $server)
    {
        $this->assertEquals($expected, Fw::resolveRequestHeaders($server));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::resolveUploadFiles
     */
    public function testResolveUploadFiles($expected, $files)
    {
        $this->assertEquals($expected, Fw::resolveUploadFiles($files));
    }

    public function testIpValid()
    {
        $this->assertTrue(Fw::ipValid('127.0.0.1', '127.0.0.1'));
        $this->assertTrue(Fw::ipValid('127.0.0.5', '127.0.0.1..127.0.0.10'));
        $this->assertFalse(Fw::ipValid('127.0.0.11', '127.0.0.1..127.0.0.10'));
    }

    public function testFixLinefeed()
    {
        $this->assertEquals("foo\nbar\nbaz", Fw::fixLinefeed("foo\nbar\r\nbaz"));
    }

    public function testTrimTrailingSpace()
    {
        // this is a fake test!
        $this->assertEquals('foo', Fw::trimTrailingSpace('foo'));
    }

    public function testBuildUrl()
    {
        $this->assertEquals('http://localhost:123/foo', Fw::buildUrl('http', 'localhost', 123, '/foo'));
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\Fw', $fw = Fw::create());
        $this->assertNotEquals($fw->get('SERVER'), $_SERVER);
    }

    public function testCreateFromGlobals()
    {
        $this->assertInstanceOf('Fal\\Stick\\Fw', $fw = Fw::createFromGlobals());
        $this->assertEquals($fw->get('SERVER'), $_SERVER);
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
        $this->fw->foo = 'foo';

        $this->assertEquals('foo', $this->fw->foo);
        $this->assertTrue(isset($this->fw->foo));
    }

    public function testMagicUnset()
    {
        $this->fw->foo = 'foo';
        unset($this->fw->foo);

        $this->assertFalse(isset($this->fw->foo));
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
        $this->fw['foo'] = 'foo';

        $this->assertEquals('foo', $this->fw['foo']);
        $this->assertTrue(isset($this->fw['foo']));
    }

    public function testOffsetUnset()
    {
        $this->fw['foo'] = 'foo';
        unset($this->fw['foo']);

        $this->assertFalse(isset($this->fw['foo']));
    }

    public function testConstruct()
    {
        $fw = new Fw(array('foo' => 'bar'), null, null, null, null, array(
            'SERVER_NAME' => 'foo',
        ));

        $this->assertEquals('foo', $fw->get('JAR.domain'));
        $this->assertEquals('bar', $fw->get('foo'));
    }

    public function testEllapsed()
    {
        $this->assertGreaterThan(0, $this->fw->ellapsed());
    }

    public function testOverrideRequestMethod()
    {
        $this->fw->set('POST._method', 'PUT');
        $this->fw->set('VERB', 'POST');
        $this->fw->overrideRequestMethod();

        $this->assertEquals('PUT', $this->fw->get('VERB'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::emulateCliRequest
     */
    public function testEmulateCliRequest($expected, $query, $argv = null)
    {
        $this->fw->set('SERVER.argv', $argv);
        $this->fw->emulateCliRequest();

        $this->assertEquals($expected, $this->fw->get('PATH'));
        $this->assertEquals($query, $this->fw->get('GET'));
    }

    public function testRegisterAutoloader()
    {
        $this->assertSame($this->fw, $this->fw->registerAutoloader());
        spl_autoload_unregister(array($this->fw, 'loadClass'));
    }

    public function testUnregisterAutoloader()
    {
        spl_autoload_register(array($this->fw, 'loadClass'));
        $this->assertSame($this->fw, $this->fw->unregisterAutoloader());
    }

    public function testFindClass()
    {
        $this->fw->set('AUTOLOAD', array(
            'InvalidNamespace\\' => __DIR__.'/invalid',
            'Fal\\Stick\\TestSuite\\' => $this->fixture(),
        ));
        $this->fw->set('AUTOLOAD_FALLBACK', $this->fixture('/Classes'));

        $this->assertEquals($this->fixture('/Classes/ClassA.php'), $this->fw->findClass('Fal\\Stick\\TestSuite\\Classes\\ClassA'));
        $this->assertEquals($this->fixture('/Classes/Independent/ClassA.php'), $this->fw->findClass('Independent\\ClassA'));
        $this->assertEquals(null, $this->fw->findClass('InvalidNamespace\\ClassA'));
        // missing
        $this->assertEquals(null, $this->fw->findClass('InvalidNamespace\\ClassA'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Namespace should ends with backslash: MyNamespace.');
        $this->fw->set('AUTOLOAD', array(
            'MyNamespace' => __DIR__.'/invalid',
        ));
    }

    public function testBaseUrl()
    {
        $expected = 'http://localhost/foo';

        $this->assertEquals($expected, $this->fw->baseUrl('/foo', true));
        $this->assertEquals('/foo', $this->fw->baseUrl('/foo'));
    }

    public function testSiteUrl()
    {
        $expected = '/foo';

        $this->assertEquals($expected, $this->fw->siteUrl('/foo'));
    }

    public function testCurrentUrl()
    {
        $this->assertEquals('/', $this->fw->currentUrl());
        $this->assertEquals('/?foo=bar', $this->fw->set('GET.foo', 'bar')->currentUrl());
    }

    public function testAsset()
    {
        $this->fw->set('ASSETS', array(
            'foo' => 'bar',
        ));

        // no changes
        $this->assertEquals('http://localhost/bar', $this->fw->asset('foo', true));

        // dynamic
        $this->fw->set('ASSET', 'dynamic');
        $this->assertEquals('/bar?v'.$this->fw->get('TIME'), $this->fw->asset('bar'));

        // static
        $this->fw->set('ASSET', 'v1');
        $this->assertEquals('/bar?v1', $this->fw->asset('bar'));
    }

    public function testHive()
    {
        $this->assertCount(71, $this->fw->hive());
    }

    public function testRef()
    {
        $ref = $this->fw->ref('foo', false, $found);
        $this->assertNull($ref);
        $this->assertFalse($found);

        $ref = $this->fw->ref('PATH', false, $found);
        $this->assertEquals('/', $ref);
        $this->assertTrue($found);

        $ref = &$this->fw->ref('foo', true, $found);
        $ref = 'foo';
        $this->assertFalse($found);
        $this->assertEquals('foo', $this->fw->ref('foo'));

        $ref = &$this->fw->ref('SESSION.foo');
        $ref = 'foo';
        $this->assertEquals('foo', $this->fw->ref('SESSION.foo'));
        $this->assertEquals('foo', $_SESSION['foo']);

        $hive = array();
        $ref = &$this->fw->ref('foo', true, $found, $hive);
        $ref = 'foo';

        $ref = &$this->fw->ref('bar', true, $found, $hive);
        $ref = new \stdClass();
        $ref->foo = 'foo';
        $ref->bar = 'bar';

        $ref = &$this->fw->ref('baz.qux.quux', true, $found, $hive);
        $ref = 'quuz';

        $this->assertCount(3, $hive);
        $this->assertEquals('foo', $this->fw->ref('foo', false, $found, $hive));
        $this->assertInstanceOf('stdClass', $this->fw->ref('bar', false, $found, $hive));
        $this->assertEquals('foo', $this->fw->ref('bar.foo', false, $found, $hive));
        $this->assertEquals('bar', $this->fw->ref('bar.bar', false, $found, $hive));
        $this->assertEquals(array('qux' => array('quux' => 'quuz')), $this->fw->ref('baz', false, $found, $hive));
    }

    public function testUnref()
    {
        $this->fw->unref('PATH');
        $this->assertFalse($this->fw->has('PATH'));

        $hive = array('foo' => array('bar' => array('qux' => 'quux')), 'bar' => 'baz', 'baz' => new \stdClass());
        $hive['baz']->foo = 'foo';
        $this->fw->unref('foo.bar.qux', $hive);
        $this->fw->unref('bar', $hive);
        $this->fw->unref('baz.foo', $hive);
        $this->assertEquals(array('foo' => array('bar' => array()), 'baz' => new \stdClass()), $hive);
    }

    public function testHas()
    {
        $this->assertTrue($this->fw->has('PATH'));
        $this->assertFalse($this->fw->has('foo'));
    }

    public function testGet()
    {
        $this->assertEquals('/', $this->fw->get('PATH'));

        $this->assertNull($ref = &$this->fw->get('foo.bar'));
        $ref = 'baz';
        $this->assertEquals(array('bar' => 'baz'), $this->fw->get('foo'));

        // services
        $this->fw->set('SERVICES.s1', new \stdClass());
        $this->fw->set('SERVICES.s2', 'stdClass');
        $this->fw->set('SERVICES.s3', function () {
            $std = new \stdClass();
            $std->foo = 'bar';

            return $std;
        });
        $this->fw->set('SERVICES.s4', 'Fal\\Stick\\TestSuite\\Provider\\FwProvider->createStd');

        $this->assertInstanceOf('stdClass', $this->fw->get('s1'));
        $this->assertInstanceOf('stdClass', $this->fw->get('s2'));
        $this->assertInstanceOf('stdClass', $s3 = $this->fw->get('s3'));
        $this->assertInstanceOf('stdClass', $this->fw->get('s4'));
        $this->assertSame($s3, $this->fw->get('s3'));
        $this->assertInstanceOf('stdClass', $this->fw->get('stdClass'));
        $this->assertSame($this->fw, $this->fw->get('FW'));
        $this->assertSame($this->fw, $this->fw->get('Fal\\Stick\\Fw'));
    }

    public function testSet()
    {
        // cookie set
        $this->assertEquals(array('foo' => 'bar'), $this->fw->set('COOKIE', array('foo' => 'bar'))->get('COOKIE'));

        // cookies set
        $this->assertEquals('foo', $this->fw->set('COOKIE.bar', 'foo')->get('COOKIE.bar'));

        // header set
        $this->assertEquals(array('foo'), $this->fw->set('RESPONSE.foo', 'foo')->get('RESPONSE.foo'));

        // normal set
        $this->assertEquals('/foo', $this->fw->set('PATH', '/foo')->get('PATH'));

        // language load
        $this->assertEquals('id', $this->fw->set('LANGUAGE', 'id')->get('LANGUAGE'));

        // autoload
        $this->assertEquals(array(
            'Fal\\Stick\\' => array($this->src()),
            'Foo\\' => array(__DIR__),
        ), $this->fw->set('AUTOLOAD', array('Foo\\' => __DIR__.'/'))->get('AUTOLOAD'));
        $this->assertEquals(array(
            __DIR__,
        ), $this->fw->set('AUTOLOAD_FALLBACK', __DIR__)->get('AUTOLOAD_FALLBACK'));

        // temp dir
        $this->assertEquals('foo', $this->fw->set('TEMP', 'foo')->get('TEMP'));
        $this->assertNull($this->fw->get('CACHE_REF'));

        // response always array
        $this->assertCount(0, $this->fw->set('RESPONSE', null)->get('RESPONSE'));
    }

    public function testRem()
    {
        $this->assertNull($this->fw->set('COOKIE.foo', 'bar')->rem('COOKIE.foo')->get('COOKIE.foo'));
        $this->assertEquals(array(), $this->fw->set('COOKIE', array('foo' => 'bar'))->rem('COOKIE')->get('COOKIE'));
        $this->assertNull($this->fw->set('foo', array('foo' => 'bar'))->rem('foo')->get('foo'));
        $this->assertEquals('/', $this->fw->set('PATH', '/foo')->rem('PATH')->get('PATH'));

        // removing session
        $this->assertEquals(array(), $this->fw->set('SESSION.foo', 'bar')->rem('SESSION.foo')->get('SESSION'));
        $this->assertNull($this->fw->rem('SESSION')->get('SESSION'));
    }

    public function testMhas()
    {
        $this->assertTrue($this->fw->mhas('PATH,VERB'));
        $this->assertTrue($this->fw->mhas(array('PATH', 'VERB')));
        $this->assertFalse($this->fw->mhas('PATH,VERB,foo'));
    }

    public function testMget()
    {
        $this->assertEquals(array(
            'PATH' => '/',
            'myPath' => '/',
        ), $this->fw->mget(array(
            'PATH',
            'myPath' => 'PATH',
        )));
    }

    public function testMset()
    {
        $this->assertEquals(array(
            'foo' => 'bar',
            'bar' => 'baz',
        ), $this->fw->mset(array(
            'foo' => 'bar',
            'bar' => 'baz',
        ), 'baz.')->get('baz'));
    }

    public function testMrem()
    {
        $this->assertEquals('/', $this->fw->mrem('PATH')->get('PATH'));
    }

    public function testFlash()
    {
        $this->assertEquals('foo', $this->fw->set('foo', 'foo')->flash('foo'));
        $this->assertNull($this->fw->get('foo'));
    }

    public function testCopy()
    {
        $this->fw->set('foo', 'foo');

        $this->assertEquals('foo', $this->fw->copy('foo', 'bar')->get('bar'));
        $this->assertEquals('foo', $this->fw->get('foo'));
    }

    public function testCut()
    {
        $this->fw->set('foo', 'foo');

        $this->assertEquals('foo', $this->fw->cut('foo', 'bar')->get('bar'));
        $this->assertNull($this->fw->get('foo'));
    }

    public function testPrepend()
    {
        $this->assertEquals('foo', $this->fw->prepend('foo', 'foo')->get('foo'));
        $this->assertEquals('foobar', $this->fw->set('bar', 'bar')->prepend('bar', 'foo')->get('bar'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->set('baz', array('bar'))->prepend('baz', 'foo')->get('baz'));
        $this->assertEquals(array('foo'), $this->fw->prepend('qux', 'foo', true)->get('qux'));
    }

    public function testAppend()
    {
        $this->assertEquals('foo', $this->fw->append('foo', 'foo')->get('foo'));
        $this->assertEquals('foobar', $this->fw->set('bar', 'foo')->append('bar', 'bar')->get('bar'));
        $this->assertEquals(array('foo', 'bar'), $this->fw->set('baz', array('foo'))->append('baz', 'bar')->get('baz'));
        $this->assertEquals(array('foo'), $this->fw->append('qux', 'foo', true)->get('qux'));
    }

    public function testParse()
    {
        $this->fw->set('config', array(
            'foo' => 'foo',
            'bar' => 'bar',
        ));
        $this->assertEquals('/', $this->fw->get('PATH'));
        $this->assertEquals('/foo', $this->fw->parse('${PATH}foo'));
        $this->assertEquals('${foo}', $this->fw->parse('${foo}'));
        $this->assertEquals('foobarbaz', $this->fw->parse('${config.foo}${config.bar}baz'));
    }

    public function testConfig()
    {
        $this->fw->set('config_dir', $this->fixture('/config'));
        $this->fw->config($this->fixture('/config/config.ini'), true);

        $aliases = array(
            'home' => '/',
            'cfoo' => '/controller/foo',
            'rest' => '/rest',
            'rest_item' => '/rest/@item',
            'rest2' => '/rest2',
            'rest2_item' => '/rest2/@item',
        );
        $routes = array(
            '/' => array(
                'all' => array(
                    'GET' => array('home', 'home', 10),
                ),
            ),
            '/foo' => array(
                'all' => array(
                    'GET' => array('foo', null, 0),
                ),
            ),
            '/target' => array(
                'all' => array(
                    'GET' => array('redirect', null, 0),
                ),
            ),
            '/controller/foo' => array(
                'all' => array(
                    'GET' => array('Foo->foo', 'cfoo', 0),
                ),
            ),
            '/controller/bar' => array(
                'all' => array(
                    'GET' => array('Foo->bar', null, 0),
                ),
            ),
            '/rest' => array(
                'all' => array(
                    'GET' => array('Rest->all', 'rest', 0),
                    'POST' => array('Rest->create', 'rest', 0),
                ),
            ),
            '/rest/@item' => array(
                'all' => array(
                    'GET' => array('Rest->one', 'rest_item', 0),
                    'PUT' => array('Rest->update', 'rest_item', 0),
                    'PATCH' => array('Rest->update', 'rest_item', 0),
                    'DELETE' => array('Rest->delete', 'rest_item', 0),
                ),
            ),
            '/rest2' => array(
                'all' => array(
                    'GET' => array('Rest2->all', 'rest2', 0),
                    'POST' => array('Rest2->create', 'rest2', 0),
                ),
            ),
            '/rest2/@item' => array(
                'all' => array(
                    'GET' => array('Rest2->one', 'rest2_item', 0),
                    'PUT' => array('Rest2->update', 'rest2_item', 0),
                    'PATCH' => array('Rest2->update', 'rest2_item', 0),
                    'DELETE' => array('Rest2->delete', 'rest2_item', 0),
                ),
            ),
        );
        $actualRoutes = $this->fw->get('ROUTES');
        // modify redirect handler
        $actualRoutes['/target']['all']['GET'][0] = 'redirect';
        $longText = array(
            'foo' => 'bar',
            'text' => "foo bar baz\ndkskdl\nfoo",
            'after_line' => 'foo',
        );

        $this->assertEquals('foo', $this->fw->get('foo'));
        $this->assertEquals('bar', $this->fw->get('bar'));
        $this->assertEquals("a 'quoted text'.", $this->fw->get('quoted_text'));
        $this->assertEquals($longText, $this->fw->get('long_text'));
        $this->assertSame(1, $this->fw->get('one'));
        $this->assertInstanceOf('stdClass', $std = $this->fw->get('std'));
        $this->assertSame($std, $this->fw->get('std'));
        $this->assertEquals($aliases, $this->fw->get('ALIASES'));
        $this->assertEquals($routes, $actualRoutes);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The command need first parameter: controller.');
        $this->fw->config($this->fixture('/config/invalid-controller.ini'));
    }

    public function testRoute()
    {
        $this->fw->route('GET / 11', 'foo');
        $this->fw->route('GET foo /foo', 'foo');
        $this->fw->route('POST foo', 'foo');
        $this->fw->route('get|put /bar sync', 'foo');

        $this->assertEquals(array(
            'foo' => '/foo',
        ), $this->fw->get('ALIASES'));
        $this->assertEquals(array(
            '/' => array(
                'all' => array(
                    'GET' => array('foo', null, 11),
                ),
            ),
            '/foo' => array(
                'all' => array(
                    'GET' => array('foo', 'foo', 0),
                    'POST' => array('foo', 'foo', 0),
                ),
            ),
            '/bar' => array(
                'sync' => array(
                    'GET' => array('foo', null, 0),
                    'PUT' => array('foo', null, 0),
                ),
            ),
        ), $this->fw->get('ROUTES'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::routeException
     */
    public function testRouteException($expected, $route)
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage($expected);
        $this->fw->route($route, 'foo');
    }

    public function testController()
    {
        $this->fw->controller('Foo', array(
            'GET /' => 'bar',
        ));
        $expected = array(
            '/' => array(
                'all' => array(
                    'GET' => array('Foo->bar', null, 0),
                ),
            ),
        );

        $this->assertEquals($expected, $this->fw->get('ROUTES'));
    }

    public function testRest()
    {
        $this->fw->rest('foo / 11', 'Foo');
        $this->fw->rest('bar /bar', 'Bar');
        $this->fw->rest('/baz', 'Baz');

        $aliases = array(
            'foo' => '/',
            'foo_item' => '/@item',
            'bar' => '/bar',
            'bar_item' => '/bar/@item',
        );
        $expected = array(
            '/' => array(
                'all' => array(
                    'GET' => array('Foo->all', 'foo', 11),
                    'POST' => array('Foo->create', 'foo', 11),
                ),
            ),
            '/@item' => array(
                'all' => array(
                    'GET' => array('Foo->one', 'foo_item', 11),
                    'PUT' => array('Foo->update', 'foo_item', 11),
                    'PATCH' => array('Foo->update', 'foo_item', 11),
                    'DELETE' => array('Foo->delete', 'foo_item', 11),
                ),
            ),
            '/bar' => array(
                'all' => array(
                    'GET' => array('Bar->all', 'bar', 0),
                    'POST' => array('Bar->create', 'bar', 0),
                ),
            ),
            '/bar/@item' => array(
                'all' => array(
                    'GET' => array('Bar->one', 'bar_item', 0),
                    'PUT' => array('Bar->update', 'bar_item', 0),
                    'PATCH' => array('Bar->update', 'bar_item', 0),
                    'DELETE' => array('Bar->delete', 'bar_item', 0),
                ),
            ),
            '/baz' => array(
                'all' => array(
                    'GET' => array('Baz->all', null, 0),
                    'POST' => array('Baz->create', null, 0),
                ),
            ),
            '/baz/@item' => array(
                'all' => array(
                    'GET' => array('Baz->one', null, 0),
                    'PUT' => array('Baz->update', null, 0),
                    'PATCH' => array('Baz->update', null, 0),
                    'DELETE' => array('Baz->delete', null, 0),
                ),
            ),
        );

        $this->assertEquals($aliases, $this->fw->get('ALIASES'));
        $this->assertEquals($expected, $this->fw->get('ROUTES'));
    }

    public function testRedirect()
    {
        $this->fw->redirect('GET foo /', '/foo');
        $this->fw->route('GET /foo', function () {
            return 'foo';
        });
        $this->fw->set('QUIET', true);

        $expected = array(
            'foo' => '/',
        );

        $this->assertEquals($expected, $this->fw->get('ALIASES'));
        $this->assertEquals('foo', $this->fw->run()->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::alias
     */
    public function testAlias($expected, $pattern, $parameters = null, $exception = null, $alias = 'foo')
    {
        $this->fw->set('ALIASES.foo', $pattern);

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->fw->alias($alias, $parameters);

            return;
        }

        $this->assertEquals($expected, $this->fw->alias($alias, $parameters));
    }

    public function testPath()
    {
        $this->fw->set('ALIASES.foo', '/');

        $this->assertEquals('/', $this->fw->path('foo'));
        $this->assertEquals('/foo', $this->fw->path('/foo'));
        $this->assertEquals('http://localhost/foo?foo=bar', $this->fw->path('/foo', array('foo' => 'bar'), true));
    }

    public function testGrab()
    {
        $this->assertEquals(array(new \stdClass(), 'foo'), $this->fw->grab('stdClass->foo'));
        $this->assertEquals(array('stdClass', 'foo'), $this->fw->grab('stdClass::foo'));
        $this->assertEquals('stdClass', $this->fw->grab('stdClass'));
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->fw->call('trim', 'foo '));
        $this->assertEquals(PHP_VERSION, $this->fw->call('phpversion'));
    }

    public function testChain()
    {
        $this->assertEquals('foo', $this->fw->chain(function ($fw) {
            $fw->set('foo', 'foo');
        })->get('foo'));
    }

    public function testSessionActive()
    {
        $this->assertFalse($this->fw->sessionActive());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cookie
     */
    public function testCookie($expected, $name, $value = null, $options = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->fw->cookie($name, $value, $options);

            return;
        }

        $this->fw->set('CLI', false);
        $this->fw->cookie($name, $value, $options, $cookie);

        $this->assertRegexp($expected, $cookie);
    }

    public function testCookies()
    {
        $this->assertEquals(null, $this->fw->cookies());
        $this->assertEquals(array('foo' => 'bar'), $this->fw->cookies(array('foo' => 'bar'))->cookies());
    }

    public function testStatus()
    {
        $this->assertEquals('Not Found', $this->fw->status(404)->get('TEXT'));
        $this->assertEquals(404, $this->fw->get('STATUS'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Unsupported HTTP code: 900');
        $this->fw->status(900);
    }

    public function testExpire()
    {
        $this->fw->set('CLI', false);

        $this->assertSame($this->fw, $this->fw->expire(1));
        $this->assertSame($this->fw, $this->fw->expire(0));
    }

    public function testTrace()
    {
        $this->fw->set('DEBUG', 2);

        $expected = '['.str_replace(dirname(TEST_ROOT).'/', '', __FILE__).':';

        $this->assertEquals('', $this->fw->trace(array()));
        $this->assertEquals('', $this->fw->trace(array(array())));
        $this->assertStringStartsWith($expected, $this->fw->trace());
    }

    public function testLog()
    {
        $this->fw->set('LOG', $dir = $this->tmp('/log/'));
        $file = $dir.'log_'.date('Y-m-d').'.log';

        $this->assertFileNotExists($file);
        $this->fw->log('emergency', 'emergency_foo_bar');
        $this->assertFileExists($file);
        $log = file_get_contents($file);
        $this->assertRegexp('/emergency_foo_bar/', $log);

        // add log info
        $this->fw->log('info', 'info_foo_bar');
        $this->assertNotRegexp('/info_foo_bar/', $log);
    }

    public function testLogFiles()
    {
        $this->assertCount(0, $this->fw->logFiles());

        $this->fw->set('LOG', $dir = $this->tmp('/log/'));
        $this->fw->log('emergency', 'foo');

        $this->assertCount(1, $this->fw->logFiles());
        $this->assertCount(1, $this->fw->logFiles(date('Y-m-d')));
        $this->assertCount(0, $this->fw->logFiles('foo'));
    }

    public function testLogLevelHttpCode()
    {
        $this->fw->set('LOG_CONVERT', array(
            500 => 'ok',
        ));

        $this->assertEquals('ok', $this->fw->logLevelHttpCode(500));
        $this->assertEquals('error', $this->fw->logLevelHttpCode(200));
    }

    public function testTrans()
    {
        $this->fw->set('LANGUAGE', 'id-ID');
        $this->fw->set('LOCALES', $this->fixture('/dict/'));

        $this->assertEquals('Halo Kawan', $this->fw->trans('greeting'));
        $this->assertEquals('Anda akan membeli pakaian', $this->fw->trans('buy_item', array('%item%' => 'pakaian')));
        $this->assertEquals('Selamat Pagi', $this->fw->trans('Good Morning'));
        $this->assertEquals('Saya seorang siswa', $this->fw->trans('i.am.student'));
        $this->assertEquals('Saya seorang tukang post', $this->fw->trans('i.am.postman'));
        $this->assertEquals('Saya seorang tukang ketik', $this->fw->trans('i.am.programmer'));
        $this->assertEquals('I am a police', $this->fw->trans('i.am.police'));
        $this->assertEquals('Foo', $this->fw->trans('foo', null, true));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The message reference is not a string: i.');
        $this->fw->trans('i');
    }

    public function testChoice()
    {
        $this->fw->set('LOCALES', $this->fixture('/dict/'));

        $this->assertEquals('There is no green apple', $this->fw->choice('there.is.apple', -1, array('%color%' => 'green')));
        $this->assertEquals('There is no green apple', $this->fw->choice('there.is.apple', 0, array('%color%' => 'green')));
        $this->assertEquals('There is an green apple', $this->fw->choice('there.is.apple', 1, array('%color%' => 'green')));
        $this->assertEquals('There is two green apples', $this->fw->choice('there.is.apple', 2, array('%color%' => 'green')));
        $this->assertEquals('There is 3 green apples', $this->fw->choice('there.is.apple', 3, array('%color%' => 'green')));
        $this->assertEquals('There is 4 green apples', $this->fw->choice('there.is.apple', 4, array('%color%' => 'green')));
        $this->assertEquals('foo', $this->fw->choice('foo', 0));
    }

    public function testTransAlt()
    {
        $this->fw->set('LEXICON.foo', 'bar');
        $this->fw->set('LEXICON.bar', 'bar %bar%');

        $this->assertEquals('bar', $this->fw->transAlt(array('foo')));
        $this->assertEquals('bar baz', $this->fw->transAlt(array('bar'), array('%bar%' => 'baz')));
        $this->assertEquals('qux', $this->fw->transAlt(array('qux')));
    }

    public function testBlacklisted()
    {
        $this->fw->set('WHITELIST', '127.0.0.5');
        $this->fw->set('BLACKLIST', '127.0.0.1..127.0.0.10');

        $this->assertFalse($this->fw->blacklisted('127.0.0.5'));
        $this->assertFalse($this->fw->blacklisted('127.0.0.11'));
        $this->assertTrue($this->fw->blacklisted('127.0.0.2'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::error
     */
    public function testError($expected, $code, $message = null, $hive = null)
    {
        $this->fw->set('QUIET', true);

        if ($hive) {
            $this->fw->mset($hive);
        }

        $this->fw->error($code, $message);

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::run
     */
    public function testRun($expected, $routes = null, $hive = null)
    {
        $this->fw->set('QUIET', true);

        if ($hive) {
            $this->fw->mset($hive);
        }

        foreach ($routes ?? array() as $route => $handler) {
            $this->fw->route($route, $handler);
        }

        $this->fw->run();

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    public function testRunCache()
    {
        $this->fw->mset(array(
            'QUIET' => true,
            'TEMP' => $this->tmp('/'),
            'CACHE' => 'fallback',
        ));
        $this->fw->route('GET / 5', function () {
            return 'foo';
        });

        // first call
        $this->assertEquals('foo', $this->fw->run()->get('OUTPUT'));
        $this->fw->rem('OUTPUT');

        // second call
        $this->assertEquals('foo', $this->fw->run()->get('OUTPUT'));
        $this->fw->rem('OUTPUT');

        // third call, not modified
        $this->fw->set('REQUEST.If-Modified-Since', '+1 day');
        $this->assertEquals('304', $this->fw->run()->get('STATUS'));
        $this->assertNull($this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::mock
     */
    public function testMock($expected, $routes, $route, $arguments = null, $server = null, $body = null, $hive = null, $exception = null)
    {
        $this->fw->set('QUIET', true);

        if ($hive) {
            $this->fw->mset($exception);
        }

        foreach ($routes as $pattern => $handler) {
            $this->fw->route($pattern, $handler);
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->fw->mock($route, $arguments, $server, $body);

            return;
        }

        $this->fw->mock($route, $arguments, $server, $body);

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::reroute
     */
    public function testReroute($expected, $routes, $target, $hive = null)
    {
        $this->fw->set('QUIET', true);

        if ($hive) {
            $this->fw->mset($hive);
        }

        foreach ($routes as $route => $handler) {
            $this->fw->route($route, $handler);
        }

        $this->fw->reroute($target, false, true);

        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cache
     */
    public function testChas($engine)
    {
        $tmp = $this->tmp();
        $this->fw->set('TEMP', $tmp.'/');
        $this->fw->set('CACHE', str_replace('{tmp}', $tmp, $engine));

        $this->assertFalse($this->fw->chas('foo'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cache
     */
    public function testCget($engine)
    {
        $tmp = $this->tmp();
        $this->fw->set('TEMP', $tmp.'/');
        $this->fw->set('CACHE', str_replace('{tmp}', $tmp, $engine));

        $this->assertNull($this->fw->cget('foo'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cache
     */
    public function testCset($engine)
    {
        $tmp = $this->tmp();
        $this->fw->set('TEMP', $tmp.'/');
        $this->fw->set('CACHE', str_replace('{tmp}', $tmp, $engine));

        $this->assertFalse($this->fw->chas('foo'));

        if ($engine) {
            $this->assertTrue($this->fw->cset('foo', 'foo'));
            $this->assertTrue($this->fw->chas('foo'));
            $this->assertEquals('foo', $this->fw->cget('foo'));
            $this->assertTrue($this->fw->crem('foo'));
            $this->assertFalse($this->fw->chas('foo'));
        } else {
            $this->assertFalse($this->fw->cset('foo', 'foo'));
        }
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cache
     */
    public function testCrem($engine)
    {
        $tmp = $this->tmp();
        $this->fw->set('TEMP', $tmp.'/');
        $this->fw->set('CACHE', str_replace('{tmp}', $tmp, $engine));

        $this->assertFalse($this->fw->chas('foo'));

        if ($engine) {
            $this->assertTrue($this->fw->cset('foo', 'foo'));
            $this->assertTrue($this->fw->chas('foo'));
            $this->assertTrue($this->fw->crem('foo'));
            $this->assertFalse($this->fw->chas('foo'));
        } else {
            $this->assertFalse($this->fw->crem('foo'));
        }
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\FwProvider::cache
     */
    public function testCreset($engine)
    {
        $tmp = $this->tmp();
        $this->fw->set('TEMP', $tmp.'/');
        $this->fw->set('CACHE', str_replace('{tmp}', $tmp, $engine));

        if ($engine && false === strpos($engine, 'memcache')) {
            $this->assertTrue($this->fw->cset('foo.one', 'foo'));
            $this->assertTrue($this->fw->cset('bar.one', 'bar'));
            $this->assertTrue($this->fw->cset('bar', 'bar'));

            $this->assertEquals(2, $this->fw->creset('.one'));
            $this->assertEquals(1, $this->fw->creset());
        } else {
            $this->assertEquals(0, $this->fw->creset());
        }
    }

    public function testCgetExpired()
    {
        $this->fw->set('CACHE', 'auto');
        $this->fw->set('TEMP', $this->tmp('/'));

        $this->fw->cset('foo', 'foo', -1000);
        $this->assertNull($this->fw->cget('foo'));
    }

    public function testMagicCall()
    {
        $this->fw->set('foo', function () {
            return 'foo';
        });

        $this->assertEquals('foo', $this->fw->foo());

        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('Call to undefined method Fal\\Stick\\Fw::bar.');
        $this->fw->bar();
    }

    public function testIsMethod()
    {
        $this->assertTrue($this->fw->isMethod('get'));
        $this->assertTrue($this->fw->isMethod('GET', 'post'));
        $this->assertTrue($this->fw->isMethod('post', 'get'));
        $this->assertFalse($this->fw->isMethod('post', 'put'));
    }

    public function testCsrfRegister()
    {
        $this->fw->csrfRegister();

        $this->assertEquals($this->fw->get('SESSION.csrf'), $this->fw->get('CSRF'));
        $this->assertNull($this->fw->get('CSRF_PREV'));
    }

    public function testIsCsrfValid()
    {
        $this->assertFalse($this->fw->isCsrfValid('foo'));

        $this->fw->csrfRegister();
        $prev = $this->fw->get('CSRF');

        // register again
        $this->fw->csrfRegister();
        $this->assertTrue($this->fw->isCsrfValid($prev));
    }

    public function testUnload()
    {
        // skipped, assume all correct
        $this->assertTrue(true);
    }

    public function testLoadClass()
    {
        $this->fw->set('AUTOLOAD', array(
            'LoadOnce\\' => $this->fixture('/Classes/LoadOnce'),
        ));

        $this->assertFalse(class_exists('LoadOnce\\ClassA'));
        $this->assertTrue($this->fw->loadClass('LoadOnce\\ClassA'));
        $this->assertTrue(class_exists('LoadOnce\\ClassA'));

        // invalid class
        $this->assertNull($this->fw->loadClass('LoadOnce\\ClassB'));
    }

    public function testOn()
    {
        $this->assertCount(1, $this->fw->on('foo', 'bar')->get('EVENTS'));
    }

    public function testOne()
    {
        $this->assertCount(1, $this->fw->one('foo', 'bar')->get('EVENTS'));
        $this->assertCount(1, $this->fw->get('EVENTS_ONCE'));
    }

    public function testOff()
    {
        $this->assertCount(1, $this->fw->one('foo', 'bar')->get('EVENTS'));
        $this->assertCount(1, $this->fw->get('EVENTS_ONCE'));
        $this->assertCount(0, $this->fw->off('foo')->get('EVENTS'));
        $this->assertCount(0, $this->fw->get('EVENTS_ONCE'));
    }

    public function testDispatch()
    {
        $this->fw->on('foo', function () {
            return 'foo';
        });

        $this->assertEquals(array('foo'), $this->fw->dispatch('foo'));
        $this->assertCount(1, $this->fw->get('EVENTS'));
    }

    public function testDispatchOnce()
    {
        $this->fw->on('foo', function () {
            return 'foo';
        });

        $this->assertEquals(array('foo'), $this->fw->dispatchOnce('foo'));
        $this->assertCount(0, $this->fw->get('EVENTS'));
    }

    public function testHkey()
    {
        $this->assertNull($this->fw->hkey('foo'));
    }

    public function testHhas()
    {
        $this->assertFalse($this->fw->hhas('foo'));
    }

    public function testHget()
    {
        $this->assertCount(0, $this->fw->hget('foo'));
    }

    public function testHadd()
    {
        $this->assertEquals(array('foo'), $this->fw->hadd('Foo', 'foo')->hget('foo'));
        $this->assertEquals(array('foo'), $this->fw->hget('Foo'));
    }

    public function testHset()
    {
        $this->assertEquals(array('foo'), $this->fw->hset('Foo', 'foo')->hget('foo'));
    }

    public function testHrem()
    {
        $this->assertCount(0, $this->fw->hset('Foo', 'foo')->hrem('foo')->hget('Foo'));
    }

    public function testSendHeaders()
    {
        $this->fw->hadd('Foo', 'bar');

        $this->assertSame($this->fw, $this->fw->sendHeaders());
    }

    public function testSendContent()
    {
        $this->expectOutputString('foo');
        $this->fw->set('OUTPUT', 'foo');

        $this->fw->sendContent();
    }

    public function testSend()
    {
        $this->expectOutputString('foo');
        $this->fw->set('OUTPUT', 'foo');

        $this->fw->send();
    }
}
