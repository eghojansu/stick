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

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\JsonResponse;
use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Response;
use Fixture\ResponseSendException;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    private $kernel;

    public function setup()
    {
        $this->kernel = new Kernel();
    }

    public function testGetEnvironment()
    {
        $this->assertEquals('prod', $this->kernel->getEnvironment());
    }

    public function testIsDebug()
    {
        $this->assertFalse($this->kernel->isDebug());
    }

    public function testGetContainer()
    {
        $this->assertInstanceOf('Fal\\Stick\\Container\\ContainerInterface', $this->kernel->getContainer());
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($expected, $request)
    {
        $this->kernel->getContainer()->get('router')->routes(array(
            'GET /foo' => function () {
                return Response::create('foo');
            },
            'GET /split/@foo' => 'str_split',
            'GET /intercept-request' => 'foo',
            'GET /invalid-controller' => 'invalid-controller',
            'GET /exception-make-error' => 'invalid-controller',
        ));

        switch ($request->getPath()) {
            case '/intercept-request':
                $this->kernel->getContainer()->get('eventDispatcher')
                    ->on('kernel.request', function ($event) {
                        $event->setResponse(Response::create('request intercepted'));
                    });
                break;
            case '/split/abcde':
                $this->kernel->getContainer()->get('eventDispatcher')
                    ->on('kernel.view', function ($event) {
                        $event->setResponse(JsonResponse::create($event->getResult()));
                    });
                break;
            case '/exception-make-error':
                $this->kernel->getContainer()->get('eventDispatcher')
                    ->on('kernel.exception', function ($event) {
                        throw new \LogicException('I am throwing another exception.');
                    });
                break;
        }

        $response = $this->kernel->handle($request);
        $actual = array($response->getContent(), $response->getStatusCode());

        $this->assertEquals($expected, $actual);
    }

    public function testRun()
    {
        $this->expectOutputString('run ok');
        $this->kernel->getContainer()->get('router')->route('GET /', function () {
            return Response::create('run ok');
        });
        $response = $this->kernel->run();

        $this->assertEquals('run ok', $response->getContent());
    }

    public function testRunException()
    {
        $this->expectOutputRegex('/I am an exception./');
        $this->kernel->getContainer()->get('router')->route('GET /', function () {
            return new ResponseSendException();
        });
        $response = $this->kernel->run();

        $this->assertContains('I am an exception.', $response->getContent());
    }

    public function testConfig()
    {
        $container = $this->kernel->getContainer();
        $container->setParameter('CONFIG', TEST_FIXTURE.'config/');

        $this->kernel->config(TEST_FIXTURE.'config/config.ini', true);
        $this->kernel->config(TEST_FIXTURE.'config/not-exists.ini');

        // parameters
        $this->assertEquals('foo', $container->getParameter('config'));
        $this->assertEquals('baz', $container->getParameter('bar'));
        $this->assertEquals('bar', $container->getParameter('my_section.sub.foo'));
        $this->assertEquals('foo', $container->getParameter('subconfig'));
        $this->assertEquals(array('arr1', 'arr2'), $container->getParameter('arr'));

        // router
        $router = $container->get('router');
        $expectedRoutes = array(
            '/' => array(
                'all' => array(
                    'GET' => 'foo',
                    'POST' => 'bar',
                ),
            ),
            '/rest' => array(
                'all' => array(
                    'GET' => 'foo->all',
                    'POST' => 'foo->create',
                ),
            ),
            '/rest/@item' => array(
                'all' => array(
                    'GET' => 'foo->get',
                    'PUT' => 'foo->update',
                    'PATCH' => 'foo->update',
                    'DELETE' => 'foo->delete',
                ),
            ),
            '/controller-home' => array(
                'all' => array(
                    'GET' => 'foo->home',
                ),
            ),
        );
        $expectedAliases = array(
            'home' => '/',
            'rest' => '/rest',
            'rest_item' => '/rest/@item',
        );

        $this->assertEquals($expectedRoutes, $router->getRoutes());
        $this->assertEquals($expectedAliases, $router->getAliases());

        // events
        $this->assertCount(2, $container->get('eventDispatcher')->getEvents());

        // services
        $this->assertInstanceOf('stdClass', $container->get('foo'));
        $this->assertInstanceOf('Datetime', $container->get('bar'));

        // exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('controller need first parameter.');

        $this->kernel->config(TEST_FIXTURE.'config/invalid-controller.ini');
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\Web\\Kernel', Kernel::create());
    }

    public function handleProvider()
    {
        $error = function ($code, $message, $trace = null) {
            $texts = array(
                403 => 'Forbidden',
                404 => 'Not Found',
            );

            return array(
                str_replace(array(
                    '{code}',
                    '{text}',
                    '{message}',
                    '{trace}',
                ), array(
                    $code,
                    $texts[$code] ?? 'Internal Server Error',
                    $message,
                    $trace,
                ), file_get_contents(TEST_FIXTURE.'files/error.html')),
                $code,
            );
        };

        return array(
            array(array('foo', 200), Request::create('/foo')),
            array(array('["a","b","c","d","e"]', 200), Request::create('/split/abcde')),
            array(array('request intercepted', 200), Request::create('/intercept-request')),
            array($error(500, "Controller should returns Fal\\Stick\\Web\\Response object, given array of ['a', 'b', 'c']."), Request::create('/split/abc')),
            array(array('{"code":404,"status":"Not Found","message":"GET \\/not-found (404 Not Found)","trace":null}', 404), Request::create('/not-found', null, null, null, null, array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'))),
            array($error(404, 'GET /not-found (404 Not Found)'), Request::create('/not-found')),
            array($error(403, 'POST /foo (403 Forbidden)'), Request::create('/foo', 'POST')),
            array($error(500, 'Unable to call route controller: \'invalid-controller\'.'), Request::create('/invalid-controller')),
            array($error(500, 'I am throwing another exception.'), Request::create('/exception-make-error')),
        );
    }
}
