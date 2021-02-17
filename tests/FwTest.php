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
use Fixtures\Simple;
use Ekok\Stick\Event;
use Ekok\Stick\Event\RequestControllerArgumentsEvent;
use Ekok\Stick\Event\RequestControllerEvent;
use Ekok\Stick\Event\RequestErrorEvent;
use Ekok\Stick\Event\RequestEvent;
use Ekok\Stick\Event\RequestFinishEvent;
use Fixtures\CyclicA;
use Fixtures\CyclicB;
use Fixtures\Invokable;
use Fixtures\FwConsumer;
use Fixtures\ArgumentsConsumer;
use PHPUnit\Framework\TestCase;
use Fixtures\StdDateTimeConsumer;
use Ekok\Stick\Event\RequestRerouteEvent;
use Ekok\Stick\Event\RequestRouteEvent;
use Ekok\Stick\Event\ResponseEvent;
use Ekok\Stick\Event\ResponseFinishEvent;
use Ekok\Stick\Event\ResponseSendEvent;

class FwTest extends TestCase
{
    /** @var Fw */
    private $fw;
    private $now;

    public function setUp(): void
    {
        $this->fw = new Fw();
        $this->now = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
    }

    public function tearDown(): void
    {
        $this->fw = null;
    }

    public function testCreateFromGlobals()
    {
        $fw = Fw::createFromGlobals();

        $this->assertEquals('/', $fw['PATH']);
        $this->assertTrue($fw['CLI']);
    }

    /** @dataProvider getCookies */
    public function testCreateCookie(string $expected, string $exception = null, ...$arguments)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            Fw::cookieCreate(...$arguments);
        } else {
            $this->assertMatchesRegularExpression($expected, Fw::cookieCreate(...$arguments));
        }
    }

    public function getCookies()
    {
        yield 'minimal' => array(
            '~^foo=deleted; expires=.+; max-age=\d+; path=/; httponly$~',
            null,
            'foo',
        );

        yield 'with value' => array(
            '~^foo=bar; path=/; httponly$~',
            null,
            'foo',
            'bar',
        );

        yield 'complete' => array(
            '~^foo=bar; expires=.+; max-age=\d+; path=/foo; domain=example.com; secure; httponly; samesite=lax$~',
            null,
            'foo',
            'bar',
            array('lifetime' => new \DateTime('tomorrow'), 'secure' => true, 'samesite' => 'lax', 'domain' => 'example.com', 'path' => '/foo'),
        );

        yield 'encode' => array(
            '~^foo%20bar=baz%20qux; path=/; httponly$~',
            null,
            'foo bar',
            'baz qux',
            null,
            false,
        );

        yield 'with timestamp' => array(
            '~^foo=bar; expires=.+; max-age=\d+; path=/; httponly$~',
            null,
            'foo',
            'bar',
            array('lifetime' => time()),
        );

        yield 'with time expression' => array(
            '~^foo=bar; expires=.+; max-age=\d+; path=/; httponly$~',
            null,
            'foo',
            'bar',
            array('lifetime' => 'tomorrow'),
        );

        yield 'empty name' => array(
            'The cookie name cannot be empty.',
            'InvalidArgumentException',
            '',
        );

        yield 'name contains invalid char' => array(
            "The cookie name contains invalid characters.",
            'InvalidArgumentException',
            'foo bar',
        );

        yield 'invalid lifetime expression' => array(
            "The cookie expiration time is not valid: 'foo'.",
            'InvalidArgumentException',
            'foo',
            'bar',
            array('lifetime' => 'foo'),
        );

        yield 'invalid samesite' => array(
            "The cookie samesite is not valid: 'foo'.",
            'InvalidArgumentException',
            'foo',
            'bar',
            array('samesite' => 'foo'),
        );
    }

    public function testNormFiles()
    {
        $files = array(
            'single' => array(
                'name' => 'single',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'single_tmp',
                'error' => 0,
            ),
            'multiple' => array(
                'name' => array('multiple_1', 'multiple_2'),
                'type' => array('text/plain', 'text/html'),
                'size' => array(3, 4),
                'tmp_name' => array('multiple_1_tmp', 'multiple_2_tmp'),
                'error' => array(0, 0),
            ),
        );
        $expected = array(
            'single' => array(
                'name' => 'single',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'single_tmp',
                'error' => 0,
            ),
            'multiple' => array(
                array(
                    'error' => 0,
                    'name' => 'multiple_1',
                    'size' => 3,
                    'tmp_name' => 'multiple_1_tmp',
                    'type' => 'text/plain',
                ),
                array(
                    'error' => 0,
                    'name' => 'multiple_2',
                    'size' => 4,
                    'tmp_name' => 'multiple_2_tmp',
                    'type' => 'text/html',
                ),
            ),
        );

        $this->assertEquals($expected, Fw::normFiles($files));
    }

    public function testNormSlash()
    {
        $this->assertEquals('/foo/bar', Fw::normSlash('\\foo\\bar'));
        $this->assertEquals('/foo/bar', Fw::normSlash('\\foo\\bar\\'));
        $this->assertEquals('/foo/bar/', Fw::normSlash('\\foo\\bar', true));
    }

    public function testCast()
    {
        $this->assertEquals(null, Fw::cast('null'));
        $this->assertEquals(1, Fw::cast('0b0001'));
        $this->assertEquals(31, Fw::cast('0x1f'));
        $this->assertEquals(20, Fw::cast('20'));
        $this->assertEquals(20.00, Fw::cast('20.00'));
        $this->assertEquals('foo', Fw::cast(' foo '));
    }

    public function testArrIndexed()
    {
        $this->assertTrue(Fw::arrIndexed(array(1, 2)));
        $this->assertFalse(Fw::arrIndexed(array('foo' => 'bar')));
    }

    public function testStringify()
    {
        $std = new \stdClass();
        $std->foo = 'bar';
        $std->recursive = $std;

        $this->assertEquals("'data'", Fw::stringify('data'));
        $this->assertEquals('NULL', Fw::stringify(null));
        $this->assertEquals('true', Fw::stringify(true));
        $this->assertEquals("['foo','bar',1,NULL,true]", Fw::stringify(array('foo', 'bar', 1, null, true)));
        $this->assertEquals("['foo'=>'bar']", Fw::stringify(array('foo' => 'bar')));
        $this->assertEquals("stdClass::__set_state([])", Fw::stringify($std));
        $this->assertEquals("stdClass::__set_state(['foo'=>'bar','recursive'=>*RECURSION*])", Fw::stringify($std, true));
    }

    public function testParseExpression()
    {
        $expression = 'foo:bar,true,10|bar:qux,20.32|qux|';
        $expected = array(
            'foo' => array('bar', true, 10),
            'bar' => array('qux', 20.32),
            'qux' => array(),
        );

        $this->assertEquals($expected, Fw::parseExpression($expression));
    }

    public function testRefCreate()
    {
        $data = array();
        $expected = array(
            'foo' => array(
                'bar' => 'baz',
            ),
        );

        $ref = &Fw::refCreate($data, 'foo.bar');
        $ref = 'baz';

        $this->assertEquals($expected, $data);
    }

    public function testRefValue()
    {
        $data = array(
            'foo' => array(
                'bar' => 'qux',
            ),
            'bar' => 'baz',
        );

        $this->assertEquals('qux', Fw::refValue($data, 'foo.bar', $exists));
        $this->assertTrue($exists);

        $this->assertEquals('baz', Fw::refValue($data, 'bar', $exists));
        $this->assertTrue($exists);

        $this->assertEquals(null, Fw::refValue($data, 'unknown', $exists));
        $this->assertFalse($exists);

        $this->assertEquals(null, Fw::refValue($data, 'foo.bar.baz', $exists));
        $this->assertFalse($exists);
    }

    public function testHash()
    {
        $this->assertEquals('1xnmsgr3l2f5f', Fw::hash('foo'));
        $this->assertEquals('30fcfkjs498g4', Fw::hash('12345'));
    }

    public function testLoadFile()
    {
        $this->assertEquals(array('foo' => 'bar'), Fw::loadFile(TEST_FIXTURE . '/data.php'));
    }

    public function testLoadFilePreventAccessingThis()
    {
        $this->expectException('Error');
        $this->expectExceptionMessage('Using $this when not in object context');

        Fw::loadFile(TEST_FIXTURE . '/access_this.php');
    }

    /** @dataProvider getConstructors */
    public function testConstructor(array $expected, ...$arguments)
    {
        $fw = new Fw(...$arguments);
        $fetch = array_keys($expected);
        $actual = array_combine($fetch, array_map(static function(string $key) use ($fw) {
            return $fw[$key];
        }, $fetch));

        $this->assertEquals($expected, $actual);
    }

    public function getConstructors()
    {
        yield 'default' => array(
            array(
                'CLI' => true,
                'METHOD' => 'GET',
                'PATH' => '/',
            ),
        );

        yield 'custom environment' => array(
            array(
                'POST' => array('post' => 'foo'),
                'GET' => array('get' => 'foo'),
                'COOKIE' => array('cookie' => 'foo'),
                'SERVER' => array(
                    'HTTP_CLIENT_IP' => '192.168.1.31',
                    'SERVER_PORT' => '8000',
                    'SCRIPT_NAME' => '/foo/bar/baz.php',
                ),
                'IP' => '192.168.1.31',
                'PATH' => '/',
                'PORT' => 8000,
                'BASE_PATH' => '/foo/bar',
            ),
            array('post' => 'foo'),
            array('get' => 'foo'),
            null,
            array('cookie' => 'foo'),
            array(
                'HTTP_CLIENT_IP' => '192.168.1.31',
                'SERVER_PORT' => '8000',
                'SCRIPT_NAME' => '/foo/bar/baz.php',
            ),
        );

        yield 'use PHP_INFO as first path resolving option and can resolve http forwarded ip' => array(
            array(
                'PATH' => '/foo/bar',
                'IP' => '192.168.1.31',
            ),
            null,
            null,
            null,
            null,
            array(
                'HTTP_X_FORWARDED_FOR' => '192.168.1.31,192.168.1.32',
                'PATH_INFO' => '/foo/bar',
            ),
        );

        yield 'can resolve request uri' => array(
            array(
                'PATH' => '/',
            ),
            null,
            null,
            null,
            null,
            array(
                'REQUEST_URI' => '/~han/test.php',
                'SCRIPT_NAME' => '/~han/test.php',
            ),
        );

        yield 'can resolve request uri #2' => array(
            array(
                'PATH' => '/',
            ),
            null,
            null,
            null,
            null,
            array(
                'REQUEST_URI' => '/~han/',
                'SCRIPT_NAME' => '/~han/index.php',
            ),
        );

        yield 'can resolve request uri #3' => array(
            array(
                'PATH' => '/foo/bar',
            ),
            null,
            null,
            null,
            null,
            array(
                'REQUEST_URI' => '/~han/index.php/foo/bar',
                'SCRIPT_NAME' => '/~han/index.php',
            ),
        );
    }

    public function testArrayAccess()
    {
        // obviously not exists
        $this->assertFalse(isset($this->fw['foo']));

        // automatic assignment
        $this->assertNull($this->fw['foo']);
        $this->assertTrue(isset($this->fw['foo']));

        // change value
        $this->fw['foo'] = 'bar';
        $this->assertEquals('bar', $this->fw['foo']);

        // remove
        unset($this->fw['foo']);
        $this->assertFalse(isset($this->fw['foo']));
    }

    public function testArrayAccessForMethodOverriding()
    {
        $this->fw['METHOD'] = 'any';

        $this->assertEquals('ANY', $this->fw['METHOD']);

        $this->fw['POST'] = array('_method' => 'custom');
        $this->fw['METHOD_OVERRIDE'] = true;

        $this->assertEquals('CUSTOM', $this->fw['METHOD']);

        // change key to override
        $this->fw['POST'] = array('_custom' => 'method');
        $this->fw['METHOD_OVERRIDE_KEY'] = '_custom';

        $this->assertEquals('METHOD', $this->fw['METHOD']);
    }

    public function testArrayAccessForChangingTimezone()
    {
        $previous = date_default_timezone_get();
        $this->fw['TZ'] = 'Asia/Jakarta';

        $this->assertEquals($this->fw['TZ'], date_default_timezone_get());

        date_default_timezone_set($previous);
    }

    public function testArrayAccessForAccessSession()
    {
        $this->assertFalse(isset($this->fw['SESSION']['foo']));
        $this->fw['SESSION']['foo'] = 'bar';
        $this->assertTrue(isset($this->fw['SESSION']['foo']));
        $this->assertEquals('bar', $this->fw['SESSION']['foo']);
        $this->assertEquals(array('foo'=> 'bar'), $this->fw['SESSION']);

        // replace session
        $this->fw['SESSION'] = array('new' => 'session');
        $this->assertFalse(isset($this->fw['SESSION']['foo']));
        $this->assertTrue(isset($this->fw['SESSION']['new']));

        // remove session
        unset($this->fw['SESSION']);
        $this->assertNull($this->fw['SESSION']);

        header_remove();
    }

    public function testDependencyInjection()
    {
        $this->fw->addRule('date', 'DateTime');
        $this->fw->addRule('DateTimeZone', array(
            'arguments' => array('Asia/Jakarta'),
        ));
        $this->fw->addRule('std', array(
            'class' => 'stdClass',
            'shared' => true,
        ));
        $this->fw->addRule('std2', static function(\stdClass $std) {
            $std2 = new \stdClass();
            $std2->std = $std;

            return $std2;
        });
        $this->fw->addRule('std3', StdDateTimeConsumer::class . ':createStd');
        $this->fw->addRule(Simple::class, array(
            'calls' => array(
                'setName' => array('simple'),
                'setStd',
            ),
            'extend' => static function(Simple $simple) {
                static $ctr = 0;

                $simple->setName($simple->getName() . ' - ' . ++$ctr);
            },
        ));

        /** @var DateTime */
        $date1 = $this->fw->create('date');

        /** @var DateTime */
        $date12 = $this->fw->create('DateTime');

        /** @var stdClass */
        $std1 = $this->fw->create('std');

        /** @var stdClass */
        $std12 = $this->fw->create('stdClass');

        /** @var stdClass */
        $std13 = $this->fw->create('std');

        /** @var stdClass */
        $std2 = $this->fw->create('std2');

        /** @var stdClass */
        $std22 = $this->fw->create('std2');

        /** @var stdClass */
        $std3 = $this->fw->create('std3');

        /** @var Simple */
        $simple1 = $this->fw->create(Simple::class);

        /** @var Simple */
        $simple12 = $this->fw->create(Simple::class);

        /** @var FwConsumer */
        $consumer = $this->fw->create(FwConsumer::class);

        /** @var ArgumentsConsumer */
        $arguments = $this->fw->create(ArgumentsConsumer::class, array(
            'name' => 'argument',
            'rest' => array(1, 2, 3),
        ));

        /** @var ArgumentsConsumer */
        $arguments2 = $this->fw->create(ArgumentsConsumer::class, array(
            'name' => 'argument2',
            $std2,
            0,
            1,
            'rest',
            'of',
            'parameters',
        ));

        $this->assertInstanceOf('DateTime', $date1);
        $this->assertInstanceOf('DateTime', $date12);
        $this->assertNotSame($date1, $date12);
        $this->assertEquals($date1->getTimezone()->getName(), $date12->getTimezone()->getName());

        $this->assertInstanceOf('stdClass', $std1);
        $this->assertInstanceOf('stdClass', $std12);
        $this->assertInstanceOf('stdClass', $std13);
        $this->assertSame($std1, $std12);
        $this->assertSame($std1, $std13);

        $this->assertInstanceOf('stdClass', $std2);
        $this->assertInstanceOf('stdClass', $std22);
        $this->assertNotSame($std2, $std1);
        $this->assertNotSame($std2, $std22);
        $this->assertSame($std2->std, $std1);

        $this->assertInstanceOf('stdClass', $std3);
        $this->assertNotSame($std3, $std1);
        $this->assertNotSame($std3, $std2);

        $this->assertInstanceOf(Simple::class, $simple1);
        $this->assertInstanceOf(Simple::class, $simple12);
        $this->assertNotSame($simple1, $simple12);
        $this->assertEquals('simple - 1', $simple1->getName());
        $this->assertEquals('simple - 2', $simple12->getName());

        $this->assertInstanceOf(FwConsumer::class, $consumer);
        $this->assertSame($this->fw, $consumer->fw);

        $this->assertInstanceOf(ArgumentsConsumer::class, $arguments);
        $this->assertInstanceOf(ArgumentsConsumer::class, $arguments2);
        $this->assertSame(array('std' => $std1, 'name' => 'argument', 'name2' => null, 'name3' => null, 'rest' => array(1, 2, 3)), $arguments->collected);
        $this->assertSame(array('std' => $std2, 'name' => 'argument2', 'name2' => null, 'name3' => null, 'rest' => array(0, 1, 'rest', 'of', 'parameters')), $arguments2->collected);
    }

    public function testDependencyInjectionResolvingCycle()
    {
        $this->fw->addRule('*', array(
            'shared' => true,
        ));

        /** @var CyclicA */
        $cyclicA = $this->fw->create(CyclicA::class);
        $cyclicB = $this->fw->create(CyclicB::class);

        $this->assertInstanceOf(CyclicA::class, $cyclicA);
        $this->assertInstanceOf(CyclicB::class, $cyclicB);
        $this->assertSame($cyclicA->cyclicB, $cyclicB);
        $this->assertSame($cyclicB->cyclicA, $cyclicA);
    }

    public function testDependencyInjectionRequiredArgumentsIsNotSupplied()
    {
        $this->expectException('ArgumentCountError');
        $this->expectExceptionMessage('DateTimeZone::__construct() expect at least 1 parameters, 0 resolved.');

        $this->fw->create('DateTime');
    }

    public function testGrabCallable()
    {
        $this->fw->addRule('DateTimeZone', array(
            'arguments' => array('Asia/Jakarta'),
        ));

        $format = $this->fw->grabCallable('DateTime@format');
        $createDate = $this->fw->grabCallable('DateTime:createFromFormat');
        $createDate2 = $this->fw->grabCallable('DateTime::createFromFormat');
        $trim = $this->fw->grabCallable('trim');
        $invoke = $this->fw->grabCallable(Invokable::class);
        $today = $this->now->format('Y-m-d');

        $this->assertEquals('foo', $trim(' foo '));
        $this->assertEquals($today, $format('Y-m-d'));
        $this->assertEquals(array('foo', 'bar'), $invoke('foo', 'bar'));
        $this->assertInstanceOf('DateTime', $createDate('Y-m-d', $today));
        $this->assertInstanceOf('DateTime', $createDate2('Y-m-d', $today));
    }

    /** @dataProvider getCallableException */
    public function testGrabCallableException(string $exception, string $message, ...$arguments)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->fw->grabCallable(...$arguments);
    }

    public function getCallableException()
    {
        yield 'invalid function' => array(
            'BadFunctionCallException',
            'Call to undefined function foo.',
            'foo',
        );

        yield 'invalid method' => array(
            'BadMethodCallException',
            'Call to undefined method DateTime::foo().',
            'DateTime:foo',
        );

        yield 'no grab' => array(
            'LogicException',
            'Unable to grab callable: foo.',
            'foo',
            false,
        );
    }

    public function testCall()
    {
        $this->fw->addRule('DateTimeZone', array(
            'arguments' => array('Asia/Jakarta'),
        ));
        $today = $this->now->format('Y-m-d');

        $this->assertEquals('foo', $this->fw->call('trim', array(' foo ')));
        $this->assertEquals($today, $this->fw->call('DateTime@format', array('Y-m-d')));
        $this->assertEquals(array('foo', 'bar'), $this->fw->call(Invokable::class, array('foo', 'bar')));
        $this->assertInstanceOf('DateTime', $this->fw->call('DateTime:createFromFormat', array('Y-m-d', $today)));
        $this->assertInstanceOf('DateTime', $this->fw->call('DateTime::createFromFormat', array('Y-m-d', $today)));
    }

    public function testCallWithResolvedArguments()
    {
        $this->fw->addRule('DateTimeZone', array(
            'arguments' => array('Asia/Jakarta'),
        ));
        $today = $this->now->format('Y-m-d');

        $this->assertEquals('foo', $this->fw->callWithResolvedArguments('trim', array(' foo ')));
        $this->assertEquals($today, $this->fw->callWithResolvedArguments('DateTime@format', array('Y-m-d')));
        $this->assertEquals(array('foo', 'bar'), $this->fw->callWithResolvedArguments(Invokable::class, array('foo', 'bar')));

        // using named arguments
        $this->assertInstanceOf('DateTime', $this->fw->callWithResolvedArguments('DateTime:createFromFormat', array($today, 'format' => 'Y-m-d')));
        $this->assertInstanceOf('DateTime', $this->fw->callWithResolvedArguments('DateTime::createFromFormat', array($today, 'format' => 'Y-m-d')));
    }

    public function testKeys()
    {
        $this->assertCount(40, $this->fw->keys());
    }

    public function testHive()
    {
        $this->assertCount(40, $this->fw->hive());
    }

    public function testLoadConfiguration()
    {
        $this->fw->loadConfiguration(TEST_FIXTURE . '/data.php');

        $expected = array('foo' => 'bar');

        $this->assertEquals($expected['foo'], $this->fw['foo']);
    }

    public function testLoadConfigurations()
    {
        $this->fw->loadConfigurations(array(
            'foo_root' => TEST_FIXTURE . '/data.php',
            TEST_FIXTURE . '/data.php',
        ));

        $expected = array('foo' => 'bar');

        $this->assertEquals($expected['foo'], $this->fw['foo']);
        $this->assertEquals($expected, $this->fw['foo_root']);
    }

    public function testMerge()
    {
        $this->fw['baz'] = array();
        $this->fw->merge(array(
            'on.foo' => function (Event $event) {
                $event->stopPropagation();
            },
            'on.foo#comment' => function (Event $event) {
                // not executed
                $event->data = 'foo';
            },
            'bar' => 'baz',
            'baz' => array('qux' => 'quux'),
        ));

        $this->assertEquals('baz', $this->fw['bar']);
        $this->assertEquals(array('qux' => 'quux'), $this->fw['baz']);

        $event = new Event();
        $this->fw->dispatch('foo', $event);

        $this->assertFalse(isset($event->data));
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testEventDispatcher()
    {
        $this->fw->on('foo', function (Event $event) {
            $event->data = 1;
        });
        $this->fw->one('foo', function (Event $event) {
            $event->data += 1;
        });

        $events = $this->fw->events();

        $this->assertCount(1, $events);
        $this->assertCount(2, $events['foo']);

        $event = new Event();
        $this->fw->dispatch('foo', $event);
        $this->assertEquals(2, $event->data);

        $event = new Event();
        $this->fw->dispatch('foo', $event, true);
        $this->assertEquals(1, $event->data);

        $event = new Event();
        $this->fw->dispatch('foo', $event);
        $this->assertFalse(isset($event->data));
    }

    public function testEventDispatcherWithPriority()
    {
        $this->fw->on('foo', function (Event $event) {
            $event->data = 'first';
        }, -10);
        $this->fw->on('foo', function (Event $event) {
            $event->data = 'second';
        }, 0);
        $this->fw->on('foo', function (Event $event) {
            $event->data = 'third';
        }, 10);

        $event = new Event();
        $this->fw->dispatch('foo', $event);

        $this->assertEquals('first', $event->data);
    }

    public function testAliases()
    {
        $this->assertCount(0, $this->fw->aliases());
    }

    public function testRoutes()
    {
        $this->assertCount(0, $this->fw->routes());
    }

    /** @dataProvider getBuilds */
    public function testBuild(string $expected, string $exception = null, ...$arguments)
    {
        $this->fw->route('GET home /', 'home');
        $this->fw->route('GET show /show/@id', 'show');
        $this->fw->route('GET eat /eat/@parameters*', 'eat');

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->build(...$arguments);
        } else {
            $this->assertEquals($expected, $this->fw->build(...$arguments));
        }
    }

    public function getBuilds()
    {
        yield 'simple' => array(
            '/',
            null,
            'home',
        );

        yield 'with parameters' => array(
            '/show/1',
            null,
            'show',
            array(
                'id' => 1,
            ),
        );

        yield 'rest parameters as query' => array(
            '/show/1?foo=bar&baz=qux',
            null,
            'show',
            array(
                'id' => 1,
                'foo' => 'bar',
                'baz' => 'qux',
            ),
        );

        yield 'parameters eater' => array(
            '/eat/burgers/chicken/geprek/pizza',
            null,
            'eat',
            array(
                'parameters' => array(
                    'burgers',
                    'chicken',
                    'geprek',
                    'pizza',
                ),
            ),
        );

        yield 'parameters eater without parameters' => array(
            '/eat?food=nothing',
            null,
            'eat',
            array(
                'food' => 'nothing',
            ),
        );

        yield 'invalid route' => array(
            'Route not found: unknown.',
            'InvalidArgumentException',
            'unknown',
        );

        yield 'required parameter not given' => array(
            'Route parameter is required: id@show.',
            'InvalidArgumentException',
            'show',
        );
    }

    public function testBaseUrl()
    {
        $this->assertEquals('http://localhost/foo/bar', $this->fw->baseUrl('foo/bar'));

        $this->fw['BASE_PATH'] = 'base-path/';

        $this->assertEquals('http://localhost/base-path/foo/bar', $this->fw->baseUrl('foo/bar'));
    }

    public function testAsset()
    {
        $this->assertEquals('/foo/bar', $this->fw->asset('foo/bar'));

        $this->fw['BASE_PATH'] = 'base-path/';

        $this->assertEquals('/base-path/foo/bar', $this->fw->asset('foo/bar'));
    }

    public function testAssetWithEmptyPath()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Empty path!');

        $this->fw->asset('');
    }

    public function testPath()
    {
        $this->fw->route('GET home /home', 'home');
        $this->fw->route('GET params /params/@foo', 'params');

        $this->assertEquals('/', $this->fw->path());
        $this->assertEquals('/home', $this->fw->path('home'));
        $this->assertEquals('/params/bar', $this->fw->path('params', array('foo' => 'bar')));
        $this->assertEquals('/foo/bar?foo=bar', $this->fw->path('foo/bar', array('foo' => 'bar')));
        $this->assertEquals('/foo/bar?foo=bar', $this->fw->path('/foo/bar', array('foo' => 'bar')));

        $this->fw['BASE_PATH'] = 'base-path/';

        $this->assertEquals('/base-path/foo/bar?foo=bar', $this->fw->path('/foo/bar', array('foo' => 'bar')));

        $this->fw['ENTRY'] = 'index.php';
        $this->fw['ENTRY_SCRIPT'] = true;
        $this->assertEquals('/base-path/index.php/foo/bar', $this->fw->path('/foo/bar'));
    }

    public function testUrl()
    {
        $this->assertEquals('http://localhost/foo', $this->fw->url('foo'));
    }

    public function testRoute()
    {
        $this->fw->route('GET home /', 'home');
        $this->fw->route('GET login /login', 'login');
        $this->fw->route('POST login', 'loginCheck');
        $this->fw->route('PUT /upload', 'receiveUpload');

        $expectedAliases = array(
            'home' => '/',
            'login' => '/login',
        );
        $expectedRoutes = array(
            '/' => array(
                array('methods' => array('GET'), 'controller' => 'home', 'options' => null, 'alias' => 'home'),
            ),
            '/login' => array(
                array('methods' => array('GET'), 'controller' => 'login', 'options' => null, 'alias' => 'login'),
                array('methods' => array('POST'), 'controller' => 'loginCheck', 'options' => null, 'alias' => null),
            ),
            '/upload' => array(
                array('methods' => array('PUT'), 'controller' => 'receiveUpload', 'options' => null, 'alias' => null),
            ),
        );

        $this->assertEquals($expectedAliases, $this->fw->aliases());
        $this->assertEquals($expectedRoutes, $this->fw->routes());
    }

    public function testRouteException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Invalid route: 'GET'.");

        $this->fw->route('GET', 'foo');
    }

    /** @dataProvider getKernels */
    public function testKernel(string $expected, array $setup = null)
    {
        $this->fw->merge($setup ?? array());
        $this->fw->route('GET /foo/@any*', function (...$any) {
            return 'welcome, fooers' . ($any ? ' (your set is ' . implode(', ', $any) . ')' : null);
        });
        $this->fw->route('GET show /show/@id', function(int $id) {
            return 'showing ' . $id;
        });
        $this->fw->route('GET /new-home', function() {
            return 'welcome to new home';
        });
        $this->fw->route('GET guarded /guard', function () {
            return 'first guard';
        }, array(
            'check' => function (Fw $fw) {
                return $this->fw === $fw;
            },
        ));
        $this->fw->route('GET /guard', function () {
            return 'second guard';
        }, array(
            'priority' => 10,
        ));
        $this->fw->route('POST /eat/@food/drink/@drinks*', function (string $food, string ...$drinks) {
            return 'Eat: ' . $food . '; Drinks: ' . implode(', ', $drinks);
        });
        $this->fw->route('GET /pay/@int:digit', function (int $cash) {
            return $cash;
        });
        $this->fw->redirect('GET /', '/new-home');

        $this->expectOutputString($expected);

        $this->fw->run();
    }

    public function getKernels()
    {
        yield 'default (redirect)' => array(
            'welcome to new home',
        );

        // TODO: optional parameters
        // yield 'foo fans' => array(
        //     'welcome, fooers',
        //     array(
        //         'PATH' => '/foo',
        //     ),
        // );

        yield 'foo fans, with parameters' => array(
            'welcome, fooers (your set is a, b, c)',
            array(
                'PATH' => '/foo/a/b/c',
            ),
        );

        yield 'single parameter' => array(
            'showing 123',
            array(
                'PATH' => '/show/123',
            ),
        );

        yield 'more parameters' => array(
            'Eat: bakso; Drinks: es-teh-tawar, es-jeruk',
            array(
                'PATH' => '/eat/bakso/drink/es-teh-tawar/es-jeruk',
                'METHOD' => 'POST',
            ),
        );

        yield 'use check and priority option' => array(
            'second guard',
            array(
                'PATH' => '/guard',
            ),
        );

        yield 'not found' => array(
            "HTTP 404 (Not Found)\nHTTP 404 (GET /unknown)\n\n",
            array(
                'PATH' => '/unknown',
            ),
        );

        yield 'intercept kernel' => array(
            'kernel intercepted',
            array(
                'on.fw.request_start' => array(static function(RequestEvent $event) {
                    $event->setResponse('kernel intercepted');
                }),
            ),
        );

        yield 'intercept route' => array(
            'route intercepted',
            array(
                'on.fw.request_route' => array(static function(RequestRouteEvent $event) {
                    if (array('GET') === $event->getRoute()['methods']) {
                        $event->setResponse('route intercepted');
                    }
                }),
            ),
        );

        yield 'change controller and arguments' => array(
            'in then out',
            array(
                'PATH' => '/foo/in/then/out',
                'on.fw.request_controller' => array(static function (RequestControllerEvent $event) {
                    $event->setController(Simple::class . '::outArguments');
                }),
                'on.fw.request_controller_arguments' => array(static function (RequestControllerArgumentsEvent $event) {
                    $event->setArguments($event->getArguments()['any']);
                }),
            ),
        );

        yield 'modify response' => array(
            'response modified',
            array(
                'PATH' => '/new-home',
                'on.fw.response_handle' => array(static function (ResponseEvent $event) {
                    $event->setResponse('response modified');
                }),
            ),
        );

        yield 'prepend send response' => array(
            'prepended: welcome to new home',
            array(
                'PATH' => '/new-home',
                'on.fw.request_finish' => array(static function (RequestFinishEvent $event) {
                    if (is_array($event->getRoute()) && is_callable($event->getController()) && is_array($event->getArguments())) {
                        echo 'prepended: ';
                    }
                }),
            ),
        );

        yield 'override send response' => array(
            'other response send',
            array(
                'PATH' => '/new-home',
                'on.fw.response_send' => array(static function (ResponseSendEvent $event) {
                    $event->send();

                    echo 'other response send';
                }),
            ),
        );

        yield 'send extra output' => array(
            'welcome to new home (true)',
            array(
                'PATH' => '/new-home',
                'on.fw.response_finish' => array(static function (ResponseFinishEvent $event) {
                    echo $event instanceof RequestEvent ? ' (true)' : ' (false)';
                }),
            ),
        );

        yield 'any error on finishing response will be silenced' => array(
            'welcome to new home',
            array(
                'PATH' => '/new-home',
                'on.fw.response_finish' => array(static function () {
                    throw new \LogicException('error from finishing response');
                }),
            ),
        );
    }

    public function testEmulateCliRequest()
    {
        $this->assertEquals('GET', $this->fw['METHOD']);

        $this->fw->emulateCliRequest();

        $this->assertEquals('CLI', $this->fw['METHOD']);
        $this->assertEquals(null, $this->fw['RAW_ARGUMENTS']);
        $this->assertEquals('', $this->fw['ENTRY']);
    }

    /** @dataProvider getMocks */
    public function testMock(string $expected, string $exception = null, ...$arguments)
    {
        $this->fw->route('GET home /', function (Fw $fw) {
            return 'home: ' . $fw->stringify($fw['GET']) . ': ' . $fw->stringify($fw['AJAX']);
        });
        $this->fw->route('POST /', function (Fw $fw) {
            return 'home: ' . $fw->stringify($fw['GET']) . ': ' . $fw->stringify($fw['POST']);
        });
        $this->fw->route('PUT /', function (Fw $fw) {
            return 'home: ' . $fw->stringify($fw['GET']) . ': ' . $fw->stringify($fw['BODY']);
        });


        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->fw->mock(...$arguments);
        } else {
            $this->fw->mock(...$arguments);

            $this->assertEquals($expected, $this->fw->getOutput());
        }
    }

    public function getMocks()
    {
        yield 'mock1' => array(
            "home: ['foo'=>'bar','bar'=>'baz']: true",
            null,
            'GET home(foo=bar)',
            array('bar' => 'baz'),
            null,
            null,
            array('AJAX' => true),
        );

        yield 'mock2' => array(
            "home: ['foo'=>'bar']: ['bar'=>'baz']",
            null,
            'POST /?foo=bar',
            array('bar' => 'baz'),
        );

        yield 'mock3' => array(
            "home: ['foo'=>'bar']: 'bar=baz'",
            null,
            'PUT /?foo=bar',
            array('bar' => 'baz'),
        );

        yield 'invalid mock' => array(
            "Invalid mock pattern: 'PUT'.",
            'InvalidArgumentException',
            'PUT',
        );
    }

    /** @dataProvider getRerouting */
    public function testReroute(array $expected, ...$arguments)
    {
        $this->fw->on('fw.request_reroute', function (RequestRerouteEvent $event, Fw $fw) {
            $fw['redirected'] = array(
                $event->getPath(),
                $event->getUrl(),
                $event->isPermanent(),
                $event->getHeaders(),
            );
            $event->setResolved(false === strpos($event->getPath(), '//'));
        });

        $this->fw->reroute(...$arguments);

        $this->assertEquals($expected, $this->fw['redirected']);
    }

    public function getRerouting()
    {
        yield 'refresh' => array(
            array(
                '/',
                null,
                false,
                null,
            ),
        );

        yield 'to home' => array(
            array(
                '/home',
                array('home'),
                false,
                null,
            ),
            array('home'),
        );

        yield 'to home #2' => array(
            array(
                '/home?foo=bar#baz',
                'home(foo=bar)#baz',
                false,
                null,
            ),
            'home(foo=bar)#baz',
        );

        yield 'to home #3' => array(
            array(
                'http://localhost/foo',
                'http://localhost/foo',
                false,
                null,
            ),
            'http://localhost/foo',
        );
    }

    /** @dataProvider getErrors */
    public function testError(string $expected, array $setup = null, ...$arguments)
    {
        $this->fw->merge($setup ?? array());
        $this->fw->error(...$arguments);

        if ('~' === $expected[0]) {
            $this->assertMatchesRegularExpression('~' . preg_quote(substr($expected, 1), '~') . '~', $this->fw->getOutput());
        } else {
            $this->assertEquals($expected, $this->fw->getOutput());
        }
    }

    public function getErrors()
    {
        yield 'cli' => array(
            "HTTP 404 (Not Found)\nHTTP 404 (GET /)\n\n",
            null,
            404,
        );

        yield 'ajax' => array(
            '{"code":404,"text":"Not Found","message":"HTTP 404 (GET \/)"}',
            array(
                'AJAX' => true,
            ),
            404,
        );

        yield 'html' => array(
            '~<h1>[404] Not Found</h1>',
            array(
                'CLI' => false,
            ),
            404,
        );

        yield 'handle error' => array(
            "error handled properly: [404,'Not Found','HTTP 404 (GET /)',NULL,NULL]",
            array(
                'on.fw.request_error' => array(static function(RequestErrorEvent $event) {
                    $event->setResponse('error handled properly: ' . Fw::stringify(array(
                        $event->getCode(),
                        $event->getText(),
                        $event->getMessage(),
                        $event->getHeaders(),
                        $event->getError(),
                    )));
                }),
            ),
            404,
        );

        yield 'handle error trigger another error' => array(
            "HTTP 500 (Internal Server Error)\nhandling error make other error\n\n",
            array(
                'on.fw.request_error' => array(static function () {
                    throw new \LogicException('handling error make other error');
                }),
            ),
            404,
        );
    }

    /** @dataProvider getErrorWithLogging */
    public function testErrorWithLogging(string $expected, ...$arguments)
    {
        $this->fw->setLogs(array(
            'http_level' => array(
                404 => 'info',
                5 => 'emergency',
            ),
            'threshold' => 'debug',
            'directory' => TEST_TEMP . '/error-logs',
        ));
        $filepath = $this->fw->getLogs()['filepath'];

        $this->fw->error(...$arguments);

        $this->assertFileExists($filepath);
        $this->assertFileIsWritable($filepath);

        if ('!' === $expected[0]) {
            $this->assertStringNotContainsString(substr($expected, 1), file_get_contents($filepath));
        } else {
            $this->assertStringContainsString($expected, file_get_contents($filepath));
        }

        $this->fw = null;

        testRemoveTemp('error-logs');
    }

    public function getErrorWithLogging()
    {
        yield 'log 404' => array(
            '[info] HTTP 404 (GET /)',
            404,
        );

        yield 'log 500' => array(
            '[emergency] HTTP 500 (GET /)',
            500,
        );

        yield 'not log 301' => array(
            '!HTTP 301 (GET /)',
            301,
        );
    }

    public function testErrorWithLoggingTrace()
    {
        $this->fw->setLogs(array(
            'http_level' => array(
                404 => 'info',
            ),
            'threshold' => 'debug',
            'directory' => TEST_TEMP . '/error-logs',
        ));
        $this->fw['DEBUG'] = true;
        $filepath = $this->fw->getLogs()['filepath'];

        $this->fw->error(404, 'log with trace', null, new \LogicException());

        $this->assertFileExists($filepath);
        $this->assertFileIsWritable($filepath);

        $logContent = file_get_contents($filepath);

        $this->assertStringContainsString('[info] log with trace', $logContent);
        $this->assertStringContainsString('FwTest->testErrorWithLoggingTrace()', $logContent);

        $this->fw = null;

        testRemoveTemp('error-logs');
    }

    /** @dataProvider getResponses */
    public function testResponse(string $expected, $response = null)
    {
        $this->fw->setResponse($response ?? $expected);

        $this->expectOutputString($expected);
        $this->fw->send();
    }

    public function getResponses()
    {
        yield 'string' => array(
            'sending this output',
        );

        yield 'another scalar' => array(
            '123',
            123,
        );

        yield 'array' => array(
            '{"foo":"bar","number":123}',
            array('foo' => 'bar', 'number' => 123),
        );

        yield 'handler' => array(
            'output from handler',
            static function() {
                echo 'output from handler';
            },
        );
    }

    public function testResponseHeaderManipulation()
    {
        $this->fw->setHeaders(array(
            'foo' => 'bar',
            'Content-Type' => 'text/html',
        ));
        $this->fw->addHeaderIfNotExists('bar', 'baz');
        $this->fw->addHeaderIfNotExists('content-type', 'baz');
        $this->fw->addHeader('foo', 'baz');
        $this->fw->addHeaders('foo', array('update'));
        $this->fw->status(404);

        $expected = array(
            'foo' => array('bar', 'baz', 'update'),
            'Content-Type' => array('text/html'),
            'bar' => array('baz'),
        );

        $this->assertFalse($this->fw->wantsJson());
        $this->assertTrue($this->fw->hasHeader('foo'));
        $this->assertTrue($this->fw->hasHeader('Content-Type'));
        $this->assertTrue($this->fw->hasHeader('content-type'));
        $this->assertTrue($this->fw->hasHeader('Content-type'));
        $this->assertEquals($expected['foo'], $this->fw->getHeader('foo'));
        $this->assertEquals($expected['Content-Type'], $this->fw->getHeader('Content-Type'));
        $this->assertEquals($expected['Content-Type'], $this->fw->getHeader('content-type'));
        $this->assertEquals($expected['Content-Type'], $this->fw->getHeader('Content-type'));
        $this->assertEquals($expected, $this->fw->getHeaders());
        $this->assertEquals(404, $this->fw->getCode());
        $this->assertEquals('Not Found', $this->fw->getText());
        $this->assertNull($this->fw->getOutput());
        $this->assertNull($this->fw->getHandler());

        // cookie usage
        $this->fw->addCookie('foo', 'bar');
        $this->fw->removeCookie('bar');

        $this->assertEquals(array('foo' => 'bar', 'bar' => null), $this->fw['COOKIE']);
        $this->assertCount(2, $this->fw->getHeader('Set-Cookie'));

        // removing header
        $this->fw->removeHeader('content-type');
        $this->assertFalse($this->fw->hasHeader('Content-type'));

        // remove headers
        $this->fw->removeHeaders();
        $this->assertNull($this->fw->getHeaders());
    }

    public function testResponseHeaderManipulationSendingInvalidStatusCode()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported http code: 2000.');

        $this->fw->status(2000);
    }

    public function testLogger()
    {
        $this->fw->setLogs(array(
            'directory' => TEST_TEMP . '/error-logs',
            'flush_frequency' => 1,
            'log_format' => '[{level}] custom {message}',
        ));

        $logs = $this->fw->getLogs();

        $this->assertCount(20, $logs);
        $this->assertFileExists($logs['filepath']);

        $this->fw->log('emergency', 'emergency test log on file', array('context' => 'bar'));

        $content = file_get_contents($logs['filepath']);

        $this->assertStringContainsString('[EMERGENCY] custom emergency test log on file', $content);
        $this->assertStringContainsString("context: 'bar'", $content);

        $this->fw = null;

        testRemoveTemp('error-logs');
    }

    public function testLoggerIntoPHPStream()
    {
        $this->fw->setLogs(array(
            'directory' => 'php://temp',
            'flush_frequency' => 1,
        ));

        $logs = $this->fw->getLogs();

        $this->fw->log('emergency', 'emergency test log on temporary file', array('context' => 'bar'));

        rewind($logs['handle']);
        $content = fread($logs['handle'], 1024);

        $this->assertStringContainsString('[emergency] emergency test log on temporary file', $content);
        $this->assertStringContainsString("context: 'bar'", $content);

        $this->fw = null;
    }

    public function testLoggerWithoutDirectory()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('File log mode require directory to be provided.');

        $this->fw->setLogs(array());
    }

    public function testLoggerSqlite()
    {
        $this->fw->setLogs(array(
            'filepath' => ':memory:',
            'mode' => 'sqlite',
        ));

        $logs = $this->fw->getLogs();

        $this->fw->log('emergency', 'emergency test log on sqlite', array('context' => 'bar'));

        /** @var PDO */
        $sqlite = $logs['sqlite'];
        $record = $sqlite->query('select * from stick_logs order by id desc limit 1')->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('emergency test log on sqlite', $record['content']);
        $this->assertEquals('{"context":"bar"}', $record['context']);
    }

    public function testLoggerSqliteAutoresolveFilepath()
    {
        $this->fw->setLogs(array(
            'directory' => TEST_TEMP . '/error-logs',
            'filename' => 'log.db',
            'mode' => 'sqlite',
        ));

        $logs = $this->fw->getLogs();

        $this->assertFileExists($logs['filepath']);

        $this->fw->log('emergency', 'emergency test log on sqlite in disk', array('context' => 'bar'));

        /** @var PDO */
        $sqlite = $logs['sqlite'];
        $record = $sqlite->query('select * from stick_logs order by id desc limit 1')->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('emergency test log on sqlite in disk', $record['content']);
        $this->assertEquals('{"context":"bar"}', $record['context']);

        $this->fw = null;

        testRemoveTemp('error-logs');
    }

    public function testLoggerSqliteWithoutDirectory()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Sqlite log mode require filepath or directory and filename to be provided.');

        $this->fw->setLogs(array('mode' => 'sqlite'));
    }

    public function testLoggerSqliteWithoutTable()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Sqlite lite mode require table name to be defined.');

        $this->fw->setLogs(array('mode' => 'sqlite', 'table' => null, 'filepath' => ':memory:'));
    }
}
