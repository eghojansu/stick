<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider;

use Fal\Stick\TestSuite\MyTestCase;

class FwProvider
{
    public function hash()
    {
        return array(
            array('1xnmsgr3l2f5f', 'foo'),
            array('z98tcrk4v1vx', 'bar'),
            array('3vrllw03cko4s', 'foobar'),
            array('3vrllw03cko4s.bar', 'foobar', '.bar'),
            array('307huk143akgw', MyTestCase::read('/files/long.txt')),
        );
    }

    public function split()
    {
        return array(
            array(array('foo'), 'foo'),
            array(array('foo', 'bar'), 'foo,bar'),
            array(array('foo', 'bar'), 'foo;bar'),
            array(array('foo', 'bar'), 'foo|bar'),
            array(array('foo', 'bar'), array('foo', 'bar')),
            array(array(), null),
            array(array('foo', 'bar'), 'foo.bar', '.'),
            array(array('1'), 1),
        );
    }

    public function join()
    {
        return array(
            array('foo', 'foo'),
            array('foo,bar', array('foo', 'bar')),
            array('foo:bar', array('foo', 'bar'), ':'),
            array('', null),
        );
    }

    public function cast()
    {
        return array(
            array(10, '10'),
            array(-10, '-10'),
            array(83, '0123'),
            array(26, '0x1A'),
            array(255, '0b11111111'),
            array('foo ', 'foo '),
            array(true, 'true '),
            array(null, 'null'),
        );
    }

    public function stringify()
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->bar = array('foo');
        $obj->obj = $obj;

        return array(
            array('true', true),
            array('NULL', null),
            array("['foo']", array('foo')),
            array("['foo'=>'bar']", array('foo' => 'bar')),
            array('stdClass::__set_state()', new \stdClass()),
            array("stdClass::__set_state(['foo'=>'bar','bar'=>['foo'],'obj'=>*RECURSION*])", $obj),
        );
    }

    public function csv()
    {
        return array(
            array('true,false,NULL', array(true, false, null)),
            array("'foo',0,0.5", array('foo', 0, 0.5)),
        );
    }

    public function resolveRequestHeaders()
    {
        return array(
            array(array(), null),
            array(array(
                'Content-Type' => 'foo',
                'Content-Length' => 0,
                'Foo-Bar' => 'baz',
            ), array(
                'CONTENT_TYPE' => 'foo',
                'CONTENT_LENGTH' => 0,
                'HTTP_FOO_BAR' => 'baz',
            )),
        );
    }

    public function resolveUploadFiles()
    {
        return array(
            array(array(), null),
            array(array(), array(
                'foo' => array(
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Foo',
                    'size' => 0,
                    'tmp_name' => 'foo_temp',
                ),
            )),
            array(array(
                'foo' => array(
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Foo',
                    'size' => 0,
                    'tmp_name' => 'foo_temp',
                    'type' => 'foo/type',
                ),
                'bar' => array(
                    1 => array(
                        'error' => UPLOAD_ERR_OK,
                        'name' => 'Bar 2',
                        'size' => 2,
                        'tmp_name' => 'bar_temp2',
                        'type' => 'bar/type',
                    ),
                ),
            ), array(
                'foo' => array(
                    'type' => 'foo/type',
                    'name' => 'Foo',
                    'size' => 0,
                    'tmp_name' => 'foo_temp',
                    'error' => UPLOAD_ERR_OK,
                ),
                'bar' => array(
                    'type' => array('bar/type', 'bar/type'),
                    'name' => array('Bar 1', 'Bar 2'),
                    'size' => array(1, 2),
                    'tmp_name' => array('bar_temp', 'bar_temp2'),
                    'error' => array(UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK),
                ),
            )),
        );
    }

    public function routeException()
    {
        return array(
            array('Invalid routing pattern: get.', 'get'),
            array('Route not exists: foo.', 'get foo'),
        );
    }

    public function alias()
    {
        return array(
            array('/', '/'),
            array('/foo', '/@foo', 'foo=foo'),
            array('/foo?bar=baz', '/@foo', 'foo=foo&bar=baz'),
            array('/foo/bar/baz', '/foo/@bar/baz', 'bar=bar'),
            array('/foo/1', '/foo/@foo', array('foo' => 1)),
            array('/foo/bar/baz/qux', '/foo/@bar*', array('bar' => array('bar', 'baz', 'qux'))),
            array('Parameter should be provided (foo@foo).', '/@foo', null, 'LogicException'),
            array('Parameter is not valid, given: foo (foo@foo).', '/@foo(\d+)', 'foo=foo', 'LogicException'),
            array('Route not exists: bar.', '/', null, 'LogicException', 'bar'),
            'with default parameter' => array(
                '/foo/bar',
                '/foo/@bar:bar',
            ),
            'with default parameter - overriden' => array(
                '/foo/baz',
                '/foo/@bar:bar',
                'bar=baz',
            ),
            'with default parameter (parameter eater)' => array(
                '/foo/bar',
                '/foo/@bar:bar*',
            ),
            'with default parameter (parameter eater) - overriden' => array(
                '/foo/baz',
                '/foo/@bar:bar*',
                'bar=baz',
            ),
        );
    }

    public function cookie()
    {
        return array(
            array('/^foo=deleted; Expires=[^;]+; Max-Age=0; Httponly$/', 'foo'),
            array('/^foo=bar; Httponly$/', 'foo', 'bar'),
            array('/^foo=bar; Path=\/foo; Domain=foo; Secure; Httponly; Samesite=Lax$/', 'foo', 'bar', array(
                'path' => '/foo',
                'domain' => 'foo',
                'secure' => true,
                'samesite' => 'Lax',
            )),
            array('/^foo=bar; Httponly$/', 'foo', 'bar', array(
                'expires' => -1,
            )),
            array('/^foo=bar; Expires=[^;]+; Max-Age=0; Httponly$/', 'foo', 'bar', array(
                'expires' => 3600,
            )),
            array('/^foo=bar; Expires=[^;]+; Max-Age=3\d{3}; Httponly$/', 'foo', 'bar', array(
                'expires' => new \DateTime('1 hour'),
            )),
            array('/^foo=bar; Expires=[^;]+; Max-Age=3\d{3}; Httponly$/', 'foo', 'bar', array(
                'expires' => '1 hour',
            )),
            array('/^foo=bar; Expires=[^;]+; Max-Age=3\d{3}; Httponly$/', 'foo', 'bar', array(
                'expires' => strtotime('1 hour'),
            )),
            array('Cookie name empty!', '', null, null, 'LogicException'),
            array('Cookie expiration time is not valid: foo.', 'foo', 'bar', array(
                'expires' => 'foo',
            ), 'LogicException'),
            array('Samesite parameter value is not valid: foo.', 'foo', 'bar', array(
                'samesite' => 'foo',
            ), 'LogicException'),
        );
    }

    public function error()
    {
        return array(
            array(MyTestCase::response('error.json', array(
                '%code%' => 404,
                '%status%' => 'Not Found',
                '%text%' => 'HTTP 404 (GET \\/)',
            )), 404, null, array(
                'AJAX' => true,
            )),
            array(MyTestCase::response('error.txt', array(
                '%code%' => 404,
                '%verb%' => 'GET',
                '%path%' => '/',
                '%text%' => 'Not Found',
            )), 404),
            array(MyTestCase::response('error.txt', array(
                '%code%' => 404,
                '%verb%' => 'GET',
                '%path%' => '/',
                '%text%' => 'My Custom Message',
            )), 404, 'My Custom Message'),
            array(MyTestCase::response('error.html', array(
                '%code%' => 404,
                '%status%' => 'Not Found',
                '%text%' => 'HTTP 404 (GET /)',
                '%debug%' => '',
            )), 404, null, array(
                'CLI' => false,
                'ERROR' => array(
                    'code' => 404,
                    'status' => 'Not Found',
                    'text' => 'Not Found',
                    'debug' => '',
                    'trace' => array(),
                ),
            )),
            'listening only' => array(MyTestCase::response('error.txt', array(
                '%code%' => 404,
                '%verb%' => 'GET',
                '%path%' => '/',
                '%text%' => 'Not Found',
            )), 404, null, array(
                'EVENTS.fw.error' => function () {
                    return false;
                },
            )),
            'intercepted ' => array('foo 404', 404, null, array(
                'EVENTS.fw.error' => function ($fw) {
                    $fw->set('OUTPUT', 'foo '.$fw['ERROR.code']);
                },
            )),
            'handler throw exception ' => array(MyTestCase::response('error.txt', array(
                '%code%' => 500,
                '%verb%' => 'GET',
                '%path%' => '/',
                '%text%' => 'i am an exception',
            )), 500, null, array(
                'EVENTS.fw.error' => function ($fw) {
                    throw new \Exception('i am an exception');
                },
            )),
        );
    }

    public function run()
    {
        return array(
            'no routes' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 500,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'No routes defined.',
                )),
            ),
            'boot intercepted' => array(
                '',
                array('GET /' => null),
                array(
                    'EVENTS.fw.boot' => function () {
                        return false;
                    },
                ),
            ),
            'blacklisted' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 403,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Forbidden',
                )),
                array('GET /' => 'foo'),
                array(
                    'IP' => '127.0.0.1',
                    'BLACKLIST' => '127.0.0.1',
                ),
            ),
            'route not found' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/foo',
                    '%text%' => 'Not Found',
                )),
                array('GET /' => 'foo'),
                array('PATH' => '/foo'),
            ),
            'invalid handler' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 500,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Call to undefined function foo()',
                )),
                array('GET /' => 'foo'),
            ),
            'with parameter' => array(
                'foo',
                array('GET /@foo' => function ($fw, $params) { return $params['foo']; }),
                array('PATH' => '/foo'),
            ),
            'with parameter eater' => array(
                'foobarbaz',
                array('GET /@foo*' => function ($fw, $params) { return implode('', $params['foo']); }),
                array('PATH' => '/foo/bar/baz'),
            ),
            'with parameter pattern' => array(
                '1',
                array('GET /@foo(\d)' => function ($fw, $params) { return $params['foo']; }),
                array('PATH' => '/1'),
            ),
            'with parameter pattern but not match' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/foo',
                    '%text%' => 'Not Found',
                )),
                array('GET /@foo(\d)' => 'foo'),
                array('PATH' => '/foo'),
            ),
            'ajax mode' => array(
                'ajax',
                array('GET / ajax' => function () { return 'ajax'; }),
                array('AJAX' => true),
            ),
            'sync mode' => array(
                'sync',
                array('GET / sync' => function () { return 'sync'; }),
                array('CLI' => false),
            ),
            'sync mode only' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 400,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Bad Request',
                )),
                array('GET / sync' => 'foo'),
            ),
            'cors' => array(
                '',
                array('GET /' => 'foo'),
                array(
                    'VERB' => 'OPTIONS',
                    'REQUEST.Origin' => 'foo',
                    'CORS.origin' => 'foo',
                    'CORS.credentials' => true,
                ),
            ),
            'cors preflight' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 405,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Method Not Allowed',
                )),
                array('GET /' => 'foo'),
                array(
                    'REQUEST.Origin' => 'foo',
                    'REQUEST.Access-Control-Request-Method' => 'foo',
                    'CORS.origin' => 'foo',
                    'CORS.credentials' => true,
                ),
            ),
            'cors expose' => array(
                'exposed',
                array('GET /' => function () { return 'exposed'; }),
                array(
                    'REQUEST.Origin' => 'foo',
                    'CORS.origin' => 'foo',
                    'CORS.expose' => 'foo',
                ),
            ),
            'handler throw exception' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 500,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'i am an exception',
                )),
                array('GET /' => function () { throw new \Exception('i am an exception'); }),
            ),
            'controller intercepted' => array(
                'intercepted',
                array('GET /' => function () { return 'foo'; }),
                array(
                    'EVENTS.fw.beforeroute' => function ($fw) {
                        $fw->set('OUTPUT', 'intercepted');

                        return false;
                    },
                ),
            ),
            'controller modified' => array(
                'modified',
                array('GET /' => function () { return 'foo'; }),
                array(
                    'EVENTS.fw.afterroute' => function ($fw) {
                        $fw->set('OUTPUT', 'modified');

                        return false;
                    },
                ),
            ),
            'string result' => array(
                'string',
                array('GET /' => function () { return 'string'; }),
            ),
            'array result' => array(
                '{"foo":"bar"}',
                array('GET /' => function () { return array('foo' => 'bar'); }),
            ),
            'callable' => array(
                'callable',
                array('GET /' => function () { return function ($fw) { $fw->set('OUTPUT', 'callable'); }; }),
            ),
            'throw http exception' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'http exception',
                )),
                array('GET /' => function () { throw new \LogicException('http:404 http exception'); }),
            ),
        );
    }

    public function mock()
    {
        return array(
            'default' => array(
                'foo',
                array('GET /' => function () { return 'foo'; }),
                'GET /',
            ),
            'with route alias' => array(
                'foo',
                array('GET foo /' => function () { return 'foo'; }),
                'GET foo',
            ),
            'with route alias parameters and query' => array(
                'foobarbaz',
                array('GET foo /@foo' => function ($fw, $params) {
                    return $params['foo'].implode('', $fw['GET']);
                }),
                'GET foo(foo=foo)?bar=bar&baz=baz',
            ),
            'ajax' => array(
                'ajax',
                array('GET / ajax' => function () { return 'ajax'; }),
                'GET / ajax',
            ),
            'ajax only but request in sync' => array(
                MyTestCase::response('error.html', array(
                    '%code%' => 400,
                    '%status%' => 'Bad Request',
                    '%text%' => 'HTTP 400 (GET /)',
                    '%debug%' => '',
                )),
                array('GET / ajax' => 'foo'),
                'GET / sync',
            ),
            'ajax with route alias' => array(
                'foo',
                array('GET foo / ajax' => function () { return 'foo'; }),
                'GET foo ajax',
            ),
            'with query' => array(
                'foobarbaz',
                array('GET /' => function ($fw) {
                    return implode('', $fw['GET']);
                }),
                'GET /',
                array('foo' => 'foo', 'bar' => 'bar', 'baz' => 'baz'),
            ),
            'post request' => array(
                'foo',
                array('POST /' => function ($fw) {
                    return $fw['POST.foo'];
                }),
                'POST /',
                array('foo' => 'foo'),
            ),
            'body by arguments' => array(
                'foo=foo',
                array('PUT /' => function ($fw) {
                    return $fw['BODY'];
                }),
                'PUT /',
                array('foo' => 'foo'),
            ),
            'body and server set' => array(
                'foobar',
                array('GET /' => function ($fw) {
                    return $fw['SERVER.foo'].$fw['BODY'];
                }),
                'GET /',
                null,
                array('foo' => 'foo'),
                'bar',
            ),
            'invalid pattern' => array(
                'Invalid mocking pattern: GET.',
                array('GET /' => 'foo'),
                'GET',
                null,
                array('foo' => 'foo'),
                'bar',
                null,
                'LogicException',
            ),
        );
    }

    public function reroute()
    {
        return array(
            'refresh (no path)' => array(
                'foo',
                array('GET /' => function () { return 'foo'; }),
                '',
            ),
            'same as refresh' => array(
                'foo',
                array('GET /' => function () { return 'foo'; }),
                '/',
            ),
            'named route' => array(
                'foo',
                array('GET foo /' => function () { return 'foo'; }),
                'foo',
            ),
            'named route with argument' => array(
                'foo',
                array('GET foo /@foo' => function ($fw, $params) { return $params['foo']; }),
                array('foo', 'foo=foo'),
            ),
            'named route with argument (array)' => array(
                'foo',
                array('GET foo /@foo' => function ($fw, $params) { return $params['foo']; }),
                array('foo', array('foo' => 'foo')),
            ),
            'with route expression' => array(
                'foobarbaz',
                array('GET foo /@foo/@bar' => function ($fw, $params) {
                    return $params['foo'].$params['bar'].implode('', $fw['GET']);
                }),
                'foo(foo=foo,bar=bar)?baz=baz',
            ),
            'not cli request, redirect to none (could be a loop in web request)' => array(
                '',
                array('GET /' => 'foo'),
                '/',
                array('CLI' => false),
            ),
            'intercepted' => array(
                'intercepted',
                array('GET /' => function () { return 'foo'; }),
                '/',
                array('EVENTS.fw.reroute' => function ($fw) {
                    $fw->set('OUTPUT', 'intercepted');
                }),
            ),
            'listening only' => array(
                'foointercepted',
                array('GET /' => function ($fw) { return 'foo'.$fw->get('intercepted'); }),
                '/',
                array('EVENTS.fw.reroute' => function ($fw) {
                    $fw->set('intercepted', 'intercepted');

                    return false;
                }),
            ),
        );
    }

    public function emulateCliRequest()
    {
        return array(
            'modify nothing' => array(
                '/',
                null,
            ),
            'no argument' => array(
                '/',
                array(),
                array(null),
            ),
            'with path argument' => array(
                '/foo',
                array(),
                array(null, '/foo'),
            ),
            'with path argument and query' => array(
                '/foo',
                array('foo' => 'foo'),
                array(null, '/foo?foo=foo'),
            ),
            'with last option' => array(
                '/foo',
                array('h' => ''),
                array(null, 'foo', '-h'),
            ),
            'complete arguments resolving' => array(
                '/foo/bar/baz',
                array(
                    'qux' => 'qux',
                    'quux' => 'quux',
                    'a' => '',
                    'b' => '',
                    'c' => 'value',
                    'd' => 'value',
                    'e' => 'value',
                    'f' => array('v1', 'v2'),
                    '_arguments' => array('arg1', 'arg2'),
                ),
                array(
                    null,
                    'foo',
                    'bar',
                    'baz',
                    '--qux=qux',
                    '--quux',
                    '-', // challenge
                    'quux',
                    '-abc=value',
                    '-d=value',
                    '-', // challenge
                    '-e',
                    'value',
                    '-', // challenge
                    '-f',
                    'v1',
                    'v2',
                    '--',
                    'arg1',
                    'arg2',
                ),
            ),
        );
    }

    public function cache()
    {
        return array(
            'apc' => array(
                'apc',
            ),
            'apcu' => array(
                'apcu',
            ),
            'redis' => array(
                'redis=127.0.0.1',
            ),
            'redis invalid port' => array(
                'redis=127.0.0.1:6000',
            ),
            'memcache' => array(
                'memcache=127.0.0.1',
            ),
            'memcached' => array(
                'memcached=127.0.0.1',
            ),
            'filesystem' => array(
                'filesystem={tmp}/fcache',
            ),
            'fallback' => array(
                'anytext',
            ),
            'disabled' => array(
                null,
            ),
        );
    }

    public function createStd()
    {
        return new \stdClass();
    }
}
