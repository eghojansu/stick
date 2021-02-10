<?php

use Ekok\Stick\Fw;
use Fixtures\Simple;
use Ekok\Stick\Event;
use Ekok\Stick\Event\ControllerArgumentsEvent;
use Ekok\Stick\Event\ControllerEvent;
use Ekok\Stick\Event\FinishRequestEvent;
use Ekok\Stick\Event\RequestErrorEvent;
use Ekok\Stick\Event\RequestEvent;
use Ekok\Stick\Event\RerouteEvent;
use Ekok\Stick\Event\ResponseEvent;
use Ekok\Stick\Event\RouteEvent;
use Ekok\Stick\Event\SendResponseEvent;
use Ekok\Stick\HttpException;
use Fixtures\CyclicA;
use Fixtures\CyclicB;
use Fixtures\FwConsumer;
use Fixtures\Invokable;
use Fixtures\StdDateTimeConsumer;

describe('Fw Usage', function() {
    it('can fix slashes', function () {
        expect(Fw::normSlash('\\foo\\bar\\'))->to->be->equal('/foo/bar');
        expect(Fw::normSlash('\\foo\\bar', true))->to->be->equal('/foo/bar/');
    });

    it('should be able to cast value', function () {
        expect(Fw::cast('null'))->to->be->equal(null);
        expect(Fw::cast('0b0001'))->to->be->equal(1);
        expect(Fw::cast('0x1f'))->to->be->equal(31);
        expect(Fw::cast('20'))->to->be->equal(20);
        expect(Fw::cast('20.00'))->to->be->equal(20.00);
        expect(Fw::cast(' foo '))->to->be->equal('foo');
    });

    it('should be able to stringify value', function () {
        $std = new stdClass();
        $std->foo = 'bar';
        $std->recursive = $std;

        expect(Fw::stringify('data'))->to->be->equal("'data'");
        expect(Fw::stringify(null))->to->be->equal('NULL');
        expect(Fw::stringify(true))->to->be->equal('true');
        expect(Fw::stringify(array('foo', 'bar', 1, null, true)))->to->be->equal("['foo','bar',1,NULL,true]");
        expect(Fw::stringify(array('foo' => 'bar')))->to->be->equal("['foo'=>'bar']");
        expect(Fw::stringify($std))->to->be->equal("stdClass::__set_state([])");
        expect(Fw::stringify($std, true))->to->be->equal("stdClass::__set_state(['foo'=>'bar','recursive'=>*RECURSION*])");
    });

    it('can check if array is indexed', function() {
        expect(Fw::arrIndexed(array(1,2)))->to->be->true;
        expect(Fw::arrIndexed(array('foo' => 'bar')))->to->be->false;
    });

    it('can parse expression', function () {
        $expression = 'foo:bar,true,10|bar:qux,20.32|qux|';
        $expected = array(
            'foo' => array('bar', true, 10),
            'bar' => array('qux', 20.32),
            'qux' => array(),
        );

        expect(Fw::parseExpression($expression))->to->be->equal($expected);
    });

    it('should be able to get reference', function () {
        $data = array();
        $expected = array(
            'foo' => array(
                'bar' => 'baz',
            ),
        );

        $ref = &Fw::refCreate($data, 'foo.bar');
        $ref = 'baz';

        expect($data)->to->be->equal($expected);
    });

    it('should be able to get data value', function () {
        $data = array(
            'foo' => array(
                'bar' => 'baz',
            ),
            'bar' => 'baz',
        );

        expect(Fw::refValue($data, 'foo.bar', $exists))->to->be->equal('baz');
        expect($exists)->to->be->true;
        expect(Fw::refValue($data, 'bar'))->to->be->equal('baz');
        expect(Fw::refValue($data, 'unknown', $exists))->to->be->equal(null);
        expect($exists)->to->be->false;
        expect(Fw::refValue($data, 'foo.bar.baz'))->to->be->equal(null);
    });

    it('can hash any text', function() {
        expect(Fw::hash('foo'))->to->be->equal('1xnmsgr3l2f5f');
        expect(Fw::hash('12345'))->to->be->equal('30fcfkjs498g4');
    });

    it('can load file without *$this* reference', function () {
        expect(Fw::loadFile(__DIR__ . '/fixtures/data.php'))->to->be->equal(array('foo' => 'bar'));

        try {
            $message = null;
            $exception = null;

            Fw::loadFile(__DIR__ . '/fixtures/access_this.php');
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $exception = get_class($e);
        }

        expect($exception)->to->be->equal('Error');
        expect($message)->to->be->equal('Using $this when not in object context');
    });

    it('can create cookie header content', function() {
        expect(Fw::cookieCreate('foo'))->to->be->match('~^foo=deleted;~');
        expect(Fw::cookieCreate('foo', 'bar'))->to->be->match('~^foo=bar;~');
        expect(Fw::cookieCreate('foo', 'bar', array(
            'domain' => 'localhost',
            'lifetime' => 10,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'lax',
        )))->to->be->match('~^foo=bar; expires=[^;]+; max-age=0; path=/; domain=localhost; secure; httponly; samesite=lax$~');
        expect(Fw::cookieCreate('foo', 'bar', array(
            'domain' => 'localhost',
            'lifetime' => new DateTime('tomorrow'),
            'secure' => true,
            'httponly' => true,
            'samesite' => 'lax',
        ), false))->to->be->match('~^foo=bar; expires=[^;]+; max-age=[\d]+; path=/; domain=localhost; secure; httponly; samesite=lax$~');

        expect(function() {
            Fw::cookieCreate('');
        })->to->throw('InvalidArgumentException', 'The cookie name cannot be empty.');
        expect(function () {
            Fw::cookieCreate('foo=');
        })->to->throw('InvalidArgumentException', "The cookie name contains invalid characters: 'foo='.");
        expect(function () {
            Fw::cookieCreate('foo', 'bar', array('lifetime' => 'unknown'));
        })->to->throw('InvalidArgumentException', "The cookie expiration time is not valid.");
        expect(function () {
            Fw::cookieCreate('foo', 'bar', array('samesite' => 'unknown'));
        })->to->throw('InvalidArgumentException', "The cookie samesite is not valid: 'unknown'.");
    });

    it('can normalize uploaded files', function() {
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

        expect(Fw::normFiles($files))->to->be->equal($expected);
    });

    describe('constructor', function() {
        it('can create from globals', function() {
            $fw = Fw::createFromGlobals();

            expect($fw['PATH'])->to->be->equal('/');
            expect($fw['CLI'])->to->be->true;
        });

        it('can prepare its environment', function() {
            $fw = new Fw(
                $post = array('post' => 'foo'),
                $get = array('get' => 'foo'),
                null,
                $cookie = array('cookie' => 'foo'),
                $server = array(
                    'HTTP_CLIENT_IP' => '192.168.1.31',
                    'SERVER_PORT' => '8000',
                    'SCRIPT_NAME' => '/foo/bar/baz.php',
                ),
            );

            expect($fw['POST'])->to->be->equal($post);
            expect($fw['GET'])->to->be->equal($get);
            expect($fw['COOKIE'])->to->be->equal($cookie);
            expect($fw['SERVER'])->to->be->equal($server);
            expect($fw['IP'])->to->be->equal($server['HTTP_CLIENT_IP']);
            expect($fw['PORT'])->to->be->equal(8000);
            expect($fw['BASE_PATH'])->to->be->equal('/foo/bar');
        });

        it('use PHP_INFO as first path resolving option and can resolve http forwarded ip', function () {
            $fw = new Fw(
                null,
                null,
                null,
                null,
                array(
                    'HTTP_X_FORWARDED_FOR' => '192.168.1.31,192.168.1.32',
                    'PATH_INFO' => '/foo/bar',
                ),
            );

            expect($fw['IP'])->to->be->equal('192.168.1.31');
            expect($fw['PATH'])->to->be->equal('/foo/bar');
        });

        it('can resolve request uri', function () {
            $fw = new Fw(
                null,
                null,
                null,
                null,
                array(
                    'REQUEST_URI' => '/~han/test.php',
                    'SCRIPT_NAME' => '/~han/test.php',
                ),
            );

            expect($fw['PATH'])->to->be->equal('/');
        });

        it('can resolve request uri #2', function () {
            $fw = new Fw(
                null,
                null,
                null,
                null,
                array(
                    'REQUEST_URI' => '/~han/',
                    'SCRIPT_NAME' => '/~han/index.php',
                ),
            );

            expect($fw['PATH'])->to->be->equal('/');
        });
    });

    describe('as container', function() {
        it('can be used as container', function() {
            $fw = new Fw();

            expect(isset($fw['foo']))->to->be->false;
            $fw['foo'] = 'bar';
            expect($fw['foo'])->to->be->equal('bar');
            unset($fw['foo']);
            expect(isset($fw['foo']))->to->be->false;
            expect($fw['foo'])->to->be->null;
            expect(isset($fw['foo']))->to->be->true;
        });

        it('can trigger value changes or retrieval', function() {
            $fw = new Fw();

            expect($fw['METHOD'])->to->be->equal('GET');
            expect($fw['METHOD_OVERRIDE'])->to->be->false;

            $fw['POST']['foo'] = 'PUT';
            $fw['METHOD_OVERRIDE'] = true;
            expect($fw['METHOD'])->to->be->equal('GET');

            $fw['METHOD_OVERRIDE_KEY'] = 'foo';
            expect($fw['METHOD'])->to->be->equal('PUT');

            $fw['SESSION'] = array('foo' => 'bar');
            expect($fw['SESSION'])->to->be->equal($_SESSION[$fw['SESSION_KEY']]);

            unset($fw['SESSION']);
            expect($fw['SESSION'])->to->be->null;
        });

        it('can load configurations', function() {
            $fw = new Fw();
            $fw->loadConfigurations(array(
                'foo_root' => __DIR__ . '/fixtures/data.php',
                __DIR__ . '/fixtures/data.php',
            ));

            $expected = array('foo' => 'bar');

            expect($fw['foo'])->to->be->equal($expected['foo']);
            expect($fw['foo_root'])->to->be->equal($expected);
        });

        it('can merge with new configuration', function() {
            $fw = new Fw();
            $fw['baz'] = array();

            $fw->merge(array(
                'on.foo' => function(Event $event) {
                    $event->stopPropagation();
                },
                'on.foo#comment' => function(Event $event) {
                    // not executed
                    $event->setData('foo');
                },
                'bar' => 'baz',
                'baz' => array('qux' => 'quux'),
            ));

            expect($fw['bar'])->to->be->equal('baz');
            expect($fw['baz'])->to->be->equal(array('qux' => 'quux'));

            $event = new Event();
            $fw->dispatch('foo', $event);
            expect($event->hasData())->to->be->false;
            expect($event->isPropagationStopped())->to->be->true;
        });

        it('can be used as dependency injection for php native class', function() {
            $fw = new Fw();

            $date = $fw->create('DateTime');
            $date2 = $fw->create('DateTime');

            expect($date)->to->be->not->equal($date2);
        });

        it('can declare rule', function() {
            $fw = new Fw();

            $fw->addRule('DateTime');
            $fw->addRule('std', array(
                'shared' => true,
                'class' => 'stdClass',
            ));
            $fw->addRule('consumer', StdDateTimeConsumer::class);
            $fw->addRule('consumer2', array(
                'class' => StdDateTimeConsumer::class,
                'calls' => array(
                    'addStdProperty',
                    'addStdProperty' => array('bar', 'baz'),
                ),
            ));
            $fw->addRule('std2', StdDateTimeConsumer::class . '::createStd');
            $fw->addRule('std3', function() {
                return new stdClass();
            });
            $fw->addRule('*', array(
                'shared' => true,
            ));

            $date = $fw->create('DateTime');
            $date2 = $fw->create('DateTime');
            $std = $fw->create('std');
            $std2 = $fw->create('stdClass');
            $consumer = $fw->create('consumer');
            $consumer2 = $fw->create('consumer');
            $std3 = $fw->create('std2');
            $std4 = $fw->create('std3');
            $fwConsumer = $fw->create(FwConsumer::class);
            $fwConsumer2 = $fw->create(FwConsumer::class);
            $consumer21 = $fw->create('consumer2');

            expect($date)->to->be->not->equal($date2);
            expect($std)->to->be->equal($std2);
            expect($consumer)->to->be->not->equal($consumer2);
            expect($consumer->std)->to->be->equal($std);
            expect($std3)->to->be->instanceof('stdClass');
            expect($std3)->to->be->not->equal($std);
            expect($std4)->to->be->not->equal($std);
            expect($fwConsumer)->to->be->equal($fwConsumer2);
            expect($fwConsumer->fw)->to->be->equal($fw);
            expect($consumer21)->to->be->not->equal($consumer);
            expect($consumer21->std->foo)->to->be->equal('bar');
            expect($consumer21->std->bar)->to->be->equal('baz');
            expect(function() use ($fw) {
                $fw->addRule('foo', 0);
            })->to->throw('InvalidArgumentException', 'Rule should be null, string, array or callable, integer given for rule foo.');
        });

        it('can resolve cyclic arguments', function() {
            $fw = new Fw();
            $fw->addRule('*', array('shared' => true));

            $a = $fw->create(CyclicA::class);
            $a2 = $fw->create(CyclicA::class);
            $b = $fw->create(CyclicB::class);
            $b2 = $fw->create(CyclicB::class);

            expect($a)->to->be->equal($a2);
            expect($b)->to->be->equal($b2);
            expect($a->b)->to->be->equal($b);
            expect($b->a)->to->be->equal($a);
        });

        it('can grab callable expression', function() {
            $fw = new Fw();
            $formatDate = $fw->grabCallable('DateTime@format');
            $createDateShortcut = $fw->grabCallable('DateTime::createFromFormat');
            $createDateStandard = $fw->grabCallable('DateTime:createFromFormat');
            $trim = $fw->grabCallable('trim');
            $invoke = $fw->grabCallable(Invokable::class);

            expect($formatDate('Y-m-d'))->to->be->equal(date('Y-m-d'));
            expect($createDateShortcut)->to->be->equal($createDateStandard);
            expect($createDateStandard('Y-m-d', date('Y-m-d')))->to->be->instanceof('DateTime');
            expect($trim)->to->be->equal('trim');
            expect($invoke)->to->be->instanceof($invoke);

            expect(function() use ($fw) {
                $fw->grabCallable('Unknown:foo');
            })->to->throw('BadMethodCallException', 'Call to undefined method Unknown::foo.');
            expect(function() use ($fw) {
                $fw->grabCallable('unknown');
            })->to->throw('BadFunctionCallException', 'Call to undefined function unknown.');
            expect(function() use ($fw) {
                $fw->grabCallable('unknown', false);
            })->to->throw('LogicException', 'Unable to grab callable: unknown.');
        });

        it('can resolve arguments', function() {
            $fw = new Fw();
            $fw->addRule('DateTime', array(
                'shared' => true,
            ));

            $std = new stdClass();
            $params = $fw->callWithResolvedArguments(function(DateTime $date, $foo, $bar, ?string $baz, $qux = null) {
                return array($date, $foo, $bar, $baz, $qux);
            }, array(
                'foo' => 'bar',
                'baz',
            ));
            $params2 = $fw->callWithResolvedArguments(function(stdClass $std, ...$rest) {
                return array_merge(array($std), $rest);
            }, array($std, 'foo', 'bar'));

            expect($params)->to->be->equal(array($fw->create('DateTime'), 'bar', 'baz', null, null));
            expect($params2)->to->be->equal(array($std, 'foo', 'bar'));

            expect(function() use ($fw) {
                $fw->callWithResolvedArguments(function(string $foo) {});
            })->to->throw('ArgumentCountError', "{closure} expect at least 1 parameters, 0 resolved.");
        });

        it('can call and parse callable expression', function() {
            $fw = new Fw();

            expect($fw->call('trim', array(' foo ')))->to->be->equal('foo');
            expect($fw->call('DateTime@format', array('Y-m-d')))->to->be->equal(date('Y-m-d'));
        });

        it('can call and parse callable expression, with named arguments', function () {
            $fw = new Fw();

            expect($fw->callWithResolvedArguments('trim', array('str' => ' foo ')))->to->be->equal('foo');
            expect($fw->callWithResolvedArguments('DateTime@format', array('format' => 'Y-m-d')))->to->be->equal(date('Y-m-d'));
        });
    });

    describe('object state', function() {
        it('has defined hive', function() {
            $fw = new Fw();

            expect($fw->keys())->to->have->length(40);
            expect($fw->hive())->to->have->length(40);
        });
    });

    describe('event dispatcher', function() {
        it('can be used as event dispatcher', function() {
            $fw = new Fw();
            $fw->on('foo', function(Event $event) {
                $event->setData(1);
            });
            $fw->one('foo', function(Event $event) {
                $event->setData($event->getData() + 1);
            });

            $events = $fw->events();
            expect($events)->to->have->length(1);
            expect($events['foo'])->to->have->length(2);

            $event = new Event();
            $fw->dispatch('foo', $event);
            expect($event->getData())->to->be->equal(2);

            $event = new Event();
            $fw->dispatch('foo', $event, true);
            expect($event->getData())->to->be->equal(1);

            $event = new Event();
            $fw->dispatch('foo', $event);
            expect($event->getData())->to->be->null;
        });

        it('can stop propagation and change handler priority', function() {
            $fw = new Fw();
            $fw->on('foo', function(Event $event) {
                $event->setData('first');
            }, -10);
            $fw->on('foo', function(Event $event) {
                $event->setData('second');
            }, 0);
            $fw->on('foo', function(Event $event) {
                $event->setData('third');
            }, 10);

            $event = new Event();
            $fw->dispatch('foo', $event);
            expect($event->getData())->to->be->equal('first');
        });
    });

    describe('as router', function() {
        it('can register route', function() {
            $fw = new Fw();
            $fw->route('GET home /', 'home');
            $fw->route('GET login /login', 'login');
            $fw->route('POST login', 'loginCheck');
            $fw->route('PUT /upload', 'receiveUpload');

            $routes = $fw->routes();
            $aliases = $fw->aliases();

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

            expect($routes)->to->be->equal($expectedRoutes);
            expect($aliases)->to->be->equal($expectedAliases);

            expect(function() use ($fw) {
                $fw->route('GET', 'FOO');
            })->to->throw('InvalidArgumentException', "Invalid route: 'GET'.");
        });

        it('can build url/registered route', function() {
            $fw = new Fw();
            $fw->route('GET home /', 'home');
            $fw->route('GET param /param/@param', 'param');
            $fw->route('GET complex /complex/@foo:digit/bar/@baz*', 'complex');

            expect($fw->build('home'))->to->be->equal('/');
            expect($fw->build('param', array('param' => 'foo')))->to->be->equal('/param/foo');
            expect($fw->build('complex', array('foo' => '1', 'baz' => array('qux', 'quux'), 'q' => array('foo', 'bar'))))->to->be->equal('/complex/1/bar/qux/quux?q%5B0%5D=foo&q%5B1%5D=bar');
            expect(function() use ($fw) {
                $fw->build('none');
            })->to->throw('InvalidArgumentException', 'Route not found: none.');
            expect(function() use ($fw) {
                $fw->build('param');
            })->to->throw('InvalidArgumentException', 'Route parameter is required: param@param.');
        });

        it('can generate url prefixed by base url', function() {
            $fw = new Fw();

            expect($fw->baseUrl())->to->be->equal('http://localhost');
            expect($fw->baseUrl('/foo'))->to->be->equal('http://localhost/foo');
            expect($fw->asset('/foo'))->to->be->equal('/foo');
            expect(function() use ($fw) {
                $fw->asset('');
            })->to->throw('InvalidArgumentException', 'Empty path!');
        });

        it('can generate path and url', function () {
            $fw = new Fw();
            $fw->route('GET home /', 'home');
            $fw->route('GET param /param/@param', 'param');

            expect($fw->path())->to->be->equal('/');
            expect($fw->path('/foo'))->to->be->equal('/foo');
            expect($fw->path('unknown'))->to->be->equal('/unknown');
            expect($fw->path('home'))->to->be->equal('/');
            expect($fw->path('param', array('param' => 'foo', 'foo' => 'bar')))->to->be->equal('/param/foo?foo=bar');
            expect($fw->url('param', array('param' => 'foo', 'foo' => 'bar')))->to->be->equal('http://localhost/param/foo?foo=bar');

            // add entry
            $fw->merge(array(
                'ENTRY_SCRIPT' => true,
                'ENTRY' => 'foo.php',
            ));

            expect($fw->path('home'))->to->be->equal('/foo.php/');
            expect($fw->path('param', array('param' => 'foo', 'foo' => 'bar')))->to->be->equal('/foo.php/param/foo?foo=bar');
            expect($fw->url('param', array('param' => 'foo', 'foo' => 'bar')))->to->be->equal('http://localhost/foo.php/param/foo?foo=bar');
        });

        it('can work with response', function() {
            $fw = new Fw();
            $fw->setHeaders(array(
                'foo' => 'bar',
                'Content-Type' => 'text/html',
            ));
            $fw->addHeaderIfNotExists('bar', 'baz');
            $fw->addHeaderIfNotExists('content-type', 'baz');
            $fw->addHeader('foo', 'baz');
            $fw->addHeaders('foo', array('update'));
            $fw->status(404);

            $expected = array(
                'foo' => array('bar', 'baz', 'update'),
                'Content-Type' => array('text/html'),
                'bar' => array('baz'),
            );

            expect($fw->wantsJson())->to->be->false;
            expect($fw->hasHeader('foo'))->to->be->true;
            expect($fw->hasHeader('Content-Type'))->to->be->true;
            expect($fw->hasHeader('content-type'))->to->be->true;
            expect($fw->hasHeader('Content-type'))->to->be->true;
            expect($fw->getHeader('foo'))->to->be->equal($expected['foo']);
            expect($fw->getHeader('Content-Type'))->to->be->equal($expected['Content-Type']);
            expect($fw->getHeader('content-type'))->to->be->equal($expected['Content-Type']);
            expect($fw->getHeader('Content-type'))->to->be->equal($expected['Content-Type']);
            expect($fw->getHeaders())->to->be->equal($expected);
            expect($fw->getCode())->to->be->equal(404);
            expect($fw->getText())->to->be->equal('Not Found');
            expect($fw->getOutput())->to->be->null;
            expect($fw->getHandler())->to->be->null;

            // cookie
            $fw->addCookie('foo', 'bar');
            $fw->removeCookie('bar');

            expect($fw['COOKIE'])->to->be->equal(array('foo' => 'bar', 'bar' => null));
            expect($fw->getHeader('Set-Cookie'))->to->have->length(2);

            $fw->removeHeader('content-type');
            $fw->removeHeaders();
            expect($fw->getHeaders())->to->be->null;

            expect(function() use ($fw) {
                $fw->status(909);
            })->to->throw('InvalidArgumentException', 'Unsupported http code: 909.');

            $fw->setResponse('foo');
            expect($fw->getOutput())->to->be->equal('foo');

            $fw->setResponse(array('foo' => 'bar'));
            expect($fw->getOutput())->to->be->equal('{"foo":"bar"}');

            ob_start();
            $fw->send();
            $out = ob_get_clean();

            expect($out)->to->be->equal('{"foo":"bar"}');
            header_remove();
        });

        it('can use callable as content generator', function() {
            $fw = new Fw();
            $fw->setResponse(function() {
                echo 'bar';
            });

            ob_start();
            $fw->sendContent();
            $out = ob_get_clean();

            expect($out)->to->be->equal('bar');
        });

        it('can be used as router', function() {
            $fw = new Fw();
            $fw->route('GET home /', function() {
                return 'home';
            }, array(
                'check' => function(Fw $self) use ($fw) {
                    return $self === $fw;
                },
            ));
            $fw->route('GET /', function() {
                return 'second home';
            }, array(
                'priority' => 10,
            ));
            $fw->route('POST /param/@param/@rest*', function(Fw $self, string $param, string ...$rest) {
                return $self->stringify($param === 'foo' && array('bar', 'baz') === $rest);
            });
            $fw->route('GET /wants-integer/@int:digit', function(Fw $self, int $int) {
                return $self->stringify($int === 1);
            });

            $result = function(string $path, string $method = 'GET', string $fetch = null) use ($fw) {
                $fw['PATH'] = $path;
                $fw['METHOD'] = $method;
                $fw->execute();

                return $fetch ? $fw[$fetch] : $fw->getOutput();
            };

            expect($result('/'))->to->be->equal('second home');
            expect($result('/param/foo/bar/baz', 'POST'))->to->be->equal('true');
            expect($result('/wants-integer/1'))->to->be->equal('true');
            expect($result('/unknown'))->to->be->contain('HTTP 404 (GET /unknown)');
        });

        it('can be run', function() {
            $fw = new Fw();
            $fw->route('GET /', function() {
                return 'home';
            });

            $fw->on('fw.controller', function(ControllerEvent $event) {
                $event->setController(Simple::class . ':outArguments');
            });
            $fw->on('fw.controller_arguments', function (ControllerArgumentsEvent $event) {
                $event->setArguments(array('foo', 'bar', 'baz'));
            });
            $fw->on('fw.response', function (ResponseEvent $event) {
                $event->setResponse('response(' . $event->getResponse() . ')');
            });
            $fw->on('fw.finish_request', function(FinishRequestEvent $event, Fw $self) {
                $check = 'false';

                if (
                    is_callable($event->getController())
                    && array('foo', 'bar', 'baz') === $event->getArguments()
                    && is_array($event->getRoute())
                ) {
                    $check = 'true';
                }

                $self['check'] = $check;
            });

            ob_start();
            $fw->run();
            $out = ob_get_clean();

            expect($out)->to->be->equal('response(foo bar baz)');
            expect($fw['check'])->to->be->equal('true');
        });

        it('can be intercepted', function() {
            $fw = new Fw();
            $fw->route('GET /', function() {
                return 'home';
            });

            $fw->on('fw.request', function(RequestEvent $event) {
                $event->setResponse('intercepted');
            });

            ob_start();
            $fw->run();
            $out = ob_get_clean();

            expect($out)->to->be->equal('intercepted');
        });

        it('can intercept route too', function() {
            $fw = new Fw();
            $fw->route('GET home /', 'home');

            $fw->on('fw.route', function(RouteEvent $event) {
                $event->setResponse($event->getRoute());
            });

            ob_start();
            $fw->run();
            $out = ob_get_clean();

            expect($out)->to->be->equal('{"methods":["GET"],"controller":"home","options":null,"alias":"home"}');
        });

        it('can handle interceptor error', function () {
            $fw = new Fw();
            $fw->route('GET /', function () {
                return 'home';
            });

            $fw->on('fw.send_response', function (SendResponseEvent $event) {
                $event->send();
                $event->unsend();

                throw new \LogicException('error on finishing');
            });

            ob_start();
            $fw->run();
            $out = ob_get_clean();

            expect($out)->to->be->contain('error on finishing');
        });

        it('can emulate cli request', function() {
            $fw = new Fw();
            $fw->emulateCliRequest();

            expect($fw['METHOD'])->to->be->equal('CLI');
        });

        it('can handle error', function() {
            $fw = new Fw();
            $fw->on('fw.error', function() {
                throw new \LogicException('handling error make other error');
            });
            $fw->merge(array(
                'logs.' => array(array(
                    'http_level' => array(4 => 'emergency'),
                    'directory' => STICK_TEMP_DIR,
                )),
                'TZ' => 'Asia/Jakarta',
            ));
            $logFile = $fw->getLogs()['filepath'];

            $fw->error(404);
            expect($fw->getOutput())->to->be->contain('handling error make other error');

            $fw->merge(array(
                'AJAX' => true,
            ));
            $fw->error(404);
            expect($fw->getOutput())->to->be->contain('"message":"HTTP 404 (GET \/)"');
            expect($fw->getHeader('content-type')[0])->to->be->equal('application/json');
            expect($fw->getHeader('content-length')[0])->to->be->equal(61);

            $fw->merge(array(
                'AJAX' => false,
                'CLI' => false,
            ));
            $fw->error(404, null, null, new \LogicException('Error to log'));
            expect($fw->getOutput())->to->be->contain('HTTP 404 (GET /)');
            expect($fw->getOutput())->to->be->contain('<h1>[404] Not Found</h1>');
            expect($fw->getHeader('content-type')[0])->to->be->equal('text/html');
            expect($fw->getHeader('content-length')[0])->to->be->equal(499);

            expect(file_exists($logFile))->to->be->true;

            $fw = null;
            unlink($logFile);
        });

        it('can intercept error', function() {
            $fw = new Fw();
            $fw->on('fw.error', function(RequestErrorEvent $event) {
                $response = $event->getMessage() . $event->getCode() . $event->getText();

                if ($headers = $event->getHeaders()) {
                    $response .= 'headers:' . count($headers);
                }

                if ($error = $event->getError()) {
                    $response .= 'error:' . get_class($error);
                }

                $event->setResponse($response);
            });

            $fw->error(404);
            expect($fw->getOutput())->to->be->equal('HTTP 404 (GET /)404Not Found');
        });

        it('can handle redirection', function() {
            $fw = new Fw();
            $fw->route('GET /fallback', function() {
                return 'fallback';
            });
            $fw->redirect('GET /', '/fallback');

            $fw->execute();

            expect($fw->getOutput())->to->be->equal('fallback');
        });

        it('can handle rerouting', function() {
            $fw = new Fw();
            $fw->on('fw.reroute', function(RerouteEvent $event, Fw $self) {
                $self['redirected'] = array(
                    $event->getPath(),
                    $event->getUrl(),
                    $event->isPermanent(),
                    $event->getHeaders(),
                );
                $event->setResolved(false === strpos($event->getPath(), '//'));
            });

            $fw->reroute();
            expect($fw['redirected'])->to->be->equal(array(
                '/',
                null,
                false,
                null,
            ));

            $fw->reroute(array('home'));
            expect($fw['redirected'])->to->be->equal(array(
                '/home',
                array('home'),
                false,
                null,
            ));

            $fw->reroute('home(foo=bar)#baz');
            expect($fw['redirected'])->to->be->equal(array(
                '/home?foo=bar#baz',
                'home(foo=bar)#baz',
                false,
                null,
            ));

            $fw->reroute('http://localhost/foo');
            expect($fw['redirected'])->to->be->equal(array(
                'http://localhost/foo',
                'http://localhost/foo',
                false,
                null,
            ));
        });

        it('can handle mocking', function() {
            $fw = new Fw();
            $fw->route('GET home /', function(Fw $self) {
                return 'home: ' . $self->stringify($self['GET']) . ': ' . $self->stringify($self['AJAX']);
            });
            $fw->route('POST /', function(Fw $self) {
                return 'home: ' . $self->stringify($self['GET']) . ': ' . $self->stringify($self['POST']);
            });
            $fw->route('PUT /', function(Fw $self) {
                return 'home: ' . $self->stringify($self['GET']) . ': ' . $self->stringify($self['BODY']);
            });

            $fw->mock('GET home(foo=bar)', array('bar' => 'baz'), null, null, array('AJAX' => true));
            expect($fw->getOutput())->to->be->equal("home: ['foo'=>'bar','bar'=>'baz']: true");

            $fw->mock('POST /?foo=bar', array('bar' => 'baz'));
            expect($fw->getOutput())->to->be->equal("home: ['foo'=>'bar']: ['bar'=>'baz']");

            $fw->mock('PUT /?foo=bar', array('bar' => 'baz'));
            expect($fw->getOutput())->to->be->equal("home: ['foo'=>'bar']: 'bar=baz'");

            expect($fw['STACK'])->to->have->length(3);

            expect(function() use ($fw) {
                $fw->mock('PUT');
            })->to->throw('InvalidArgumentException', "Invalid mock pattern: 'PUT'.");
        });

        it('can be used as logger', function() {
            $fw = new Fw();
            $logFiles = array();

            // original setup
            expect($fw->getLogs())->to->have->length(19);

            // file log
            $fw->setLogs(array('directory' => STICK_TEMP_DIR, 'flush_frequency' => 1, 'log_format' => '[{level}] custom {message}'));
            $fw->log('emergency', 'emergency test log on file', array('context' => 'bar'));
            $file = $fw->getLogs()['filepath'];
            $content = file_get_contents($file);
            expect(file_exists($file))->to->be->true;
            expect($content)->to->contain('[EMERGENCY] custom emergency test log on file');
            expect($content)->to->contain("context: 'bar'");

            // save reference
            $logFiles[] = $file;

            // file log into memory
            $fw->setLogs(array('directory' => 'php://memory', 'log_format' => null));
            $fw->log('emergency', 'emergency test log on memory', array('context' => 'bar'));
            $file = $fw->getLogs()['filepath'];
            $line = $fw->getLogs()['line'];
            expect($file)->to->be->equal('php://memory');
            expect($line)->to->contain('emergency test log on memory');
            expect($line)->to->contain("context: 'bar'");

            expect(function() use ($fw) {
                $fw->setLogs(array('directory' => null));
            })->to->throw('InvalidArgumentException', 'File log mode require directory to be provided.');

            // sqlite log
            $fw->setLogs(array('mode' => 'sqlite', 'directory' => STICK_TEMP_DIR, 'filename' => 'sqlite', 'extension' => 'db', 'filepath' => null));
            $fw->log('emergency', 'emergency test log on sqlite', array('context' => 'bar'));
            $file = $fw->getLogs()['filepath'];
            $line = $fw->getLogs()['line'];
            expect(file_exists($file))->to->be->true;
            expect($line)->to->contain('emergency test log on sqlite');

            $sqlite = $fw->getLogs()['sqlite'];
            $record = $sqlite->query('select * from stick_logs order by log_id desc limit 1')->fetch(\PDO::FETCH_ASSOC);
            expect($record['log_content'])->to->contain('emergency test log on sqlite');

            expect(function () use ($fw) {
                $fw->setLogs(array('directory' => null, 'filepath' => null));
            })->to->throw('InvalidArgumentException', 'Sqlite log mode require filepath or directory and filename to be provided.');

            array_map('unlink', $logFiles);
        });
    });

    describe('http exception', function() {
        it('can be shortcut to create error', function() {
            $error = HttpException::notFound('Not found');

            expect($error)->to->be->instanceof(HttpException::class);
            expect($error->getHttpCode())->to->be->equal(404);
            expect($error->getHttpHeaders())->to->be->null;

            $error = HttpException::forbidden('Forbidden');

            expect($error->getHttpCode())->to->be->equal(403);
        });
    });
});
