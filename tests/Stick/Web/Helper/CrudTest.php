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

namespace Fal\Stick\Test\Helper;

use Fal\Stick\Container\Definition;
use Fal\Stick\TestSuite\TestCase;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\Response;

class CrudTest extends TestCase
{
    public function setup()
    {
        $this->prepare()->connect()->buildSchema()->initUser();

        $this->container->set('validator', new Definition('Fal\\Stick\\Validation\\Validator'));
        $this->container->set('form_builder', new Definition('Fal\\Stick\\Web\\Form\\FormBuilderInterface', 'Fal\\Stick\\Web\\Form\\FormBuilder\\DivFormBuilder'));
        $this->container->set('template', new Definition('Fal\\Stick\\Template\\TemplateInterface', array(
            'use' => 'Fal\\Stick\\Template\\Template',
            'arguments' => array(
                'directories' => TEST_FIXTURE.'crud/',
            ),
        )));
        $this->container->set('crud', new Definition('Fal\\Stick\\Web\\Helper\\Crud'));
    }

    public function testGetMagic()
    {
        $this->assertNull($this->crud->foo);
    }

    /**
     * @dataProvider callMagicProvider
     */
    public function testCallMagic($expected, $option)
    {
        $this->assertEquals($expected, $this->crud->$option($expected)->$option());
    }

    public function testCallMagicException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option "foo" is not available.');

        $this->crud->foo();
    }

    public function testCallMagicException2()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Option "searchable" expect string or array value, given object type.');

        $this->crud->searchable(new \stdClass());
    }

    public function testExists()
    {
        $this->assertFalse($this->crud->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->crud->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->crud->set('foo', 'bar')->get('foo'));
    }

    public function testClear()
    {
        $this->assertNull($this->crud->set('foo', 'bar')->clear('foo')->get('foo'));
    }

    public function testData()
    {
        $this->assertCount(15, $this->crud->data());
    }

    public function testAddFunction()
    {
        $this->assertSame($this->crud, $this->crud->addFunction('foo', function () {}));
    }

    public function testCall()
    {
        $this->crud->addFunction('foo', function () { return 'foo'; });

        $this->assertEquals('foo', $this->crud->call('foo'));
        $this->assertEquals('bar', $this->crud->call('bar', null, 'bar'));
    }

    public function testOptions()
    {
        $this->assertCount(46, $this->crud->options());
    }

    public function testEnable()
    {
        $expected = array(
            'listing' => true,
            'view' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
            'foo' => true,
            'bar' => true,
        );

        $this->assertEquals($expected, $this->crud->enable('foo,bar')->states());
    }

    public function testDisable()
    {
        $expected = array(
            'listing' => true,
            'view' => false,
            'create' => true,
            'update' => false,
            'delete' => true,
        );

        $this->assertEquals($expected, $this->crud->disable('view,update')->states());
    }

    public function testField()
    {
        $expected = array(
            'listing' => null,
            'view' => null,
            'create' => 'foo',
            'update' => null,
            'delete' => 'foo',
        );

        $this->assertEquals($expected, $this->crud->field('create,delete', 'foo')->fields());
    }

    public function testView()
    {
        $expected = array(
            'listing' => null,
            'view' => null,
            'create' => null,
            'update' => null,
            'delete' => 'foo',
        );

        $this->assertEquals($expected, $this->crud->view('delete', 'foo')->views());
    }

    public function testRole()
    {
        $expected = array(
            'listing' => null,
            'view' => null,
            'create' => null,
            'update' => null,
            'delete' => 'foo',
        );

        $this->assertEquals($expected, $this->crud->role('delete', 'foo')->roles());
    }

    public function testRoles()
    {
        $expected = array(
            'listing' => null,
            'view' => null,
            'create' => null,
            'update' => null,
            'delete' => 'foo',
        );

        $this->assertEquals($expected, $this->crud->roles(array('delete' => 'foo'))->roles());
    }

    public function testIsGranted()
    {
        $this->assertEquals(false, $this->crud->isGranted('foo'));
    }

    public function testHandle()
    {
        $this->assertSame($this->crud, $this->crud->handle(Request::create('/')));
    }

    public function testPath()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Please call render first!');

        $this->crud->path();
    }

    public function testRedirect()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Please call render first!');

        $this->crud->redirect();
    }

    /**
     * @dataProvider renderProvider
     */
    public function testRender($expected, $request, $onEvent = null, $handler = null)
    {
        $this->requestStack->push($request);
        $this->router->route('GET|POST foo /bar/@segments*', 'foo');
        $this->router->handle($request);

        if ($onEvent && $handler) {
            $this->crud->$onEvent($handler);
        }

        $response = $this->crud
            ->views(array(
                'listing' => 'listing',
                'view' => 'view',
                'create' => 'form',
                'update' => 'form',
                'delete' => 'delete',
                'forbidden' => 'forbidden',
            ))
            ->mapper('user')
            ->form('Fixture\\Form\\FUserForm')
            ->field('listing', 'id,username,active')
            ->searchable('username')
            ->onLoadForm(function ($crud, $data) {
                return $data;
            })
            ->createNew(true)
            ->render()
        ;

        $this->assertEquals($expected, $response->getContent());
    }

    public function testRenderSegmentStart()
    {
        $this->requestStack->push($request = Request::create('/bar/baz/qux/view/1'));
        $this->router->route('GET|POST foo /bar/@segments*', 'foo');
        $this->router->handle($request);

        $response = $this->crud
            ->mapper('user')
            ->view('view', 'view')
            ->field('listing', 'id,username,active')
            ->segmentStart(2)
            ->render()
        ;

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'crud/view.html'), $response->getContent());
    }

    public function testRenderException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Mapper is not provided.');

        $this->crud->render();
    }

    public function testRenderException2()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Route is not defined.');

        $this->crud->mapper('user')->render();
    }

    public function testRenderException3()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Route parameter name is not provided.');

        $this->crud->mapper('user')->routeName('foo')->segments('foo')->render();
    }

    public function testRenderException4()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Insufficient primary keys!');

        $this->crud->routeName('foo')->routeParamName('foo')->mapper('user')->view('update', 'update')->segments('update')->render();
    }

    public function testRenderException5()
    {
        $this->expectException('Fal\\Stick\\Web\\Exception\\NotFoundException');

        $this->crud->routeName('foo')->routeParamName('foo')->mapper('user')->view('update', 'update')->segments('update/4')->render();
    }

    public function testCreateResponseException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No view for state: "listing".');

        $this->crud->routeName('foo')->routeParamName('foo')->mapper('user')->render();
    }

    public function testCreateResponseException2()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Response should be instance of Fal\\Stick\\Web\\Response.');

        $this->crud->routeName('foo')->routeParamName('foo')->mapper('user')->view('listing', 'listing')->onResponse(function () {
            return null;
        })->render();
    }

    /**
     * @dataProvider loadMapperProvider
     */
    public function testLoadMapper($mapper = null)
    {
        if (!$mapper) {
            $mapper = $this->mapper('user');
        }

        $this->router->route('GET|POST foo /bar/@segments*', 'foo');
        $this->crud->routeName('foo')->routeParamName('segments')->view('listing', 'listing');
        $this->crud->mapper($mapper);
        // test form too
        $this->crud->form($this->container->get('Fixture\Form\\FUserForm'));

        $response = $this->crud->render();

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'crud/listing_complete.html'), $response->getContent());
    }

    public function renderProvider()
    {
        $redirect = function ($target) {
            return str_replace('{target}', $target, file_get_contents(TEST_FIXTURE.'files/redirect.html'));
        };
        $read = function ($file) {
            return file_get_contents(TEST_FIXTURE.'crud/'.$file.'.html');
        };

        return array(
            array(
                $read('listing'),
                Request::create('/bar/index', 'GET', array('keyword' => 'foo')),
            ),
            array(
                $read('create'),
                Request::create('/bar/create'),
            ),
            array(
                $redirect('http://localhost/bar/create?page=1'),
                Request::create('/bar/create', 'POST', array(
                    'username' => 'qux',
                    '_form' => 'f_user_form',
                    'create_new' => 'on',
                )),
            ),
            array(
                $redirect('http://localhost/bar/index?page=1'),
                Request::create('/bar/create', 'POST', array(
                    'username' => 'qux',
                    '_form' => 'f_user_form',
                )),
            ),
            array(
                'created',
                Request::create('/bar/create', 'POST', array(
                    'username' => 'qux',
                    '_form' => 'f_user_form',
                )),
                'on_after_create',
                function () {
                    return Response::create('created');
                },
            ),
            array(
                $read('update'),
                Request::create('/bar/update/1'),
            ),
            array(
                $redirect('http://localhost/bar/index?page=1'),
                Request::create('/bar/update/1', 'POST', array(
                    'username' => 'qux',
                    '_form' => 'f_user_form',
                )),
            ),
            array(
                'updated',
                Request::create('/bar/update/1', 'POST', array(
                    'username' => 'qux',
                    '_form' => 'f_user_form',
                )),
                'on_after_update',
                function () {
                    return Response::create('updated');
                },
            ),
            array(
                $read('delete'),
                Request::create('/bar/delete/1'),
            ),
            array(
                $redirect('http://localhost/bar/index?page=1'),
                Request::create('/bar/delete/1', 'POST'),
            ),
            array(
                'deleted',
                Request::create('/bar/delete/1', 'POST'),
                'on_after_delete',
                function () {
                    return Response::create('deleted');
                },
            ),
            array(
                $read('forbidden'),
                Request::create('/bar/foo'),
            ),
            array(
                $read('view'),
                Request::create('/bar/view/1'),
            ),
        );
    }

    public function loadMapperProvider()
    {
        return array(
            array(),
            array('Fixture\\Mapper\\TUser'),
        );
    }

    public function callMagicProvider()
    {
        return array(
            array(true, 'appendQuery'),
            array('foo', 'create_new_label'),
            array(new \stdClass(), 'form'),
            array(array('foo'), 'filters'),
            array(1, 'page'),
            array(function () {}, 'onInit'),
        );
    }
}
