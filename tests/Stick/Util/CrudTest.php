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

namespace Fal\Stick\Test\Util;

use Fal\Stick\Fw;
use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\Form\Form;
use Fal\Stick\Util\Crud;
use Fal\Stick\Db\Pdo\Mapper;
use Fal\Stick\Security\Auth;
use Fal\Stick\Template\Environment;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\Validator;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\Form\FormBuilder\DivFormBuilder;

class CrudTest extends MyTestCase
{
    private $fw;
    private $env;
    private $auth;
    private $crud;
    private $db;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->fw->set('TEMP', $this->tmp('/'));
        $this->env = new Environment($this->fw, $this->fixture('/crud/'));
        $this->auth = new Auth($this->fw, new InMemoryUserProvider(), new PlainPasswordEncoder());
        $this->crud = new Crud($this->fw, $this->env, $this->auth);
        $this->db = new Db($this->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ));
    }

    public function teardown(): void
    {
        $this->clear($this->tmp());
    }

    private function mapper($table = 'user')
    {
        return new Mapper($this->db, $table);
    }

    private function form()
    {
        $form = new Form($this->fw, new Validator($this->fw), new DivFormBuilder($this->fw));
        $form->set('username');

        return $form;
    }

    public function testGetMagic()
    {
        $this->assertNull($this->crud->foo);
    }

    public function testCallMagic()
    {
        $this->assertCount(1, $this->crud->filters(array('foo'))->filters());

        $expected = array(
            'listing' => null,
            'view' => null,
            'create' => null,
            'update' => null,
            'delete' => 'foo',
        );

        $this->assertEquals($expected, $this->crud->roles(array('delete' => 'foo'))->roles());
    }

    public function testHas()
    {
        $this->assertFalse($this->crud->has('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->crud->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->crud->set('foo', 'bar')->get('foo'));
    }

    public function testRem()
    {
        $this->assertNull($this->crud->set('foo', 'bar')->rem('foo')->get('foo'));
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

    public function testIsGranted()
    {
        $this->assertEquals(false, $this->crud->isGranted('foo'));
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

    public function testPath()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => 'view/1'),
        ));

        $response = $this->crud
            ->mapper($this->mapper())
            ->form($this->form())
            ->view('view', 'view.shtml')
            ->render();

        $this->assertEquals($this->read('/crud/view.html'), $response);
        $this->assertEquals('/bar/index?page=1', $this->crud->path());
        $this->assertEquals('/bar/index?page=1', $this->crud->path('index'));
        $this->assertEquals('/bar/index?page=1&foo=bar', $this->crud->path('index', 'foo=bar'));
        $this->assertEquals('/bar/index?page=1&foo=bar', $this->crud->path('index', array('foo' => 'bar')));
        $this->assertEquals('/bar/create?page=1', $this->crud->path('create'));
        $this->assertEquals('/bar/update/1?page=1', $this->crud->path('update/1'));
        $this->assertEquals('/bar/delete/1?page=1', $this->crud->path(array('delete', 1)));
    }

    public function testPrepareRouteException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Please call render first!');

        $this->crud->path();
    }

    public function testBackPath()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => 'baz/qux/quux/view/1'),
        ));

        $response = $this->crud
            ->mapper($this->mapper())
            ->form($this->form())
            ->segmentStart(3)
            ->view('view', 'view.shtml')
            ->render();

        $this->assertEquals($this->read('/crud/view.html'), $response);
        $this->assertEquals('/bar/baz/qux/quux/index?page=1', $this->crud->backPath());
        $this->assertEquals('/bar/baz/qux/index?page=1', $this->crud->backPath(1));
        $this->assertEquals('/bar/baz/index?page=1', $this->crud->backPath(2));
        $this->assertEquals('/bar/index?page=1', $this->crud->backPath(3));
        $this->assertEquals('/bar/foo/bar/baz?page=1&foo=bar', $this->crud->backPath(3, 'foo/bar/baz', array('foo' => 'bar')));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Running out of segments.');

        $this->crud->backPath(4);
    }

    public function testRedirect()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => explode('/', 'view/1')),
            'EVENTS.fw.reroute' => function ($fw, $url) {
                $fw->set('rerouted', $url);
            },
        ));

        $response = $this->crud
            ->mapper($this->mapper())
            ->form($this->form())
            ->view('view', 'view.shtml')
            ->render();

        $this->assertEquals($this->read('/crud/view.html'), $response);

        $this->crud->redirect('create');
        $this->assertEquals('/bar/create?page=1', $this->fw->get('rerouted'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CrudProvider::render
     */
    public function testRender($expected, $segments, $hive = null)
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => $segments),
            'EVENTS.fw.reroute' => function ($fw, $url) {
                $fw->set('OUTPUT', $url);
            },
        ));

        if ($hive) {
            $this->fw->mset($hive);
        }

        $output = $this->crud
            ->views(array(
                'listing' => 'listing.shtml',
                'view' => 'view.shtml',
                'create' => 'form.shtml',
                'update' => 'form.shtml',
                'delete' => 'delete.shtml',
                'forbidden' => 'forbidden.shtml',
            ))
            ->mapper($this->mapper())
            ->form($this->form())
            ->field('listing', 'id,username,active')
            ->searchable('username')
            ->onLoadForm(function ($crud, $data) {
                return $data;
            })
            ->createNew(true)
            ->render()
        ;

        $this->assertEquals($expected, $this->fw->get('OUTPUT') ?? $output);
    }

    public function testRenderSimpleFieldLabel()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => array('index')),
            'GET' => array('keyword' => 'foo'),
        ));

        $output = $this->crud
            ->views(array(
                'listing' => 'listing.shtml',
            ))
            ->mapper($this->mapper())
            ->form($this->form())
            ->field('listing', array(
                'id' => 'Id',
                'username' => null,
                'active' => null,
            ))
            ->searchable('username')
            ->render()
        ;
        $expected = $this->read('/crud/listing.html');

        $this->assertEquals($expected, $output);
    }

    public function testRenderSegmentStart()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => 'baz/qux/view/1'),
        ));

        $response = $this->crud
            ->mapper($this->mapper())
            ->form($this->form())
            ->view('view', 'view.shtml')
            ->field('listing', 'id,username,active')
            ->segmentStart(2)
            ->render()
        ;

        $this->assertEquals($this->read('/crud/view.html'), $response);
    }

    public function testRenderNotFound()
    {
        $this->fw->mset(array(
            'ALIASES.foo' => '/bar/@segments*',
            'ALIAS' => 'foo',
            'PARAMS' => array('segments' => 'view/4'),
        ));

        $expected = $this->response('error.txt', array(
            '%code%' => 404,
            '%verb%' => 'GET',
            '%path%' => '/',
            '%text%' => 'Not Found',
        ));

        $output = $this->crud
            ->mapper($this->mapper())
            ->form($this->form())
            ->view('view', 'view.shtml')
            ->render()
        ;

        $this->assertEquals('', $output);
        $this->assertEquals($expected, $this->fw->get('OUTPUT'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\CrudProvider::crudExceptions
     */
    public function testException($expected, $options, $hive = null, $exception = 'LogicException')
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        if (isset($options['mapper'])) {
            $options['mapper'] = $this->mapper();
        }

        if (isset($options['form'])) {
            $options['form'] = $this->form();
        }

        $this->expectException($exception);
        $this->expectExceptionMessage($expected);

        foreach ($options as $option => $value) {
            $this->crud->$option($value);
        }

        $this->crud->render();
    }
}
