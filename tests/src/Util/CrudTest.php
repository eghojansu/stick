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
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\BcryptPasswordEncoder;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Template\Template;
use Fal\Stick\Util\Crud;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    private $fw;
    private $crud;

    public function setUp()
    {
        $this->fw = new Fw();
        $this->fw
            ->rule(Connection::class, array(
                'args' => array(
                    'fw' => '%fw%',
                    'dsn' => 'sqlite::memory:',
                    'username' => null,
                    'password' => null,
                    'commands' => array(
                        file_get_contents(FIXTURE.'files/schema.sql'),
                        'insert into user (username) values ("foo"), ("bar"), ("baz")',
                    ),
                    'options' => null,
                ),
            ))
        ;
        $this->crud = new Crud($this->fw, new Template($this->fw, FIXTURE.'files/template/'));
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

    public function testCall()
    {
        $this->assertEquals('bar', $this->crud->call('foo', null, 'bar'));
    }

    public function testGetAuth()
    {
        $auth = new Auth($this->fw, new InMemoryUserProvider(), new BcryptPasswordEncoder());

        $this->assertInstanceOf(Auth::class, $this->crud->setAuth($auth)->getAuth());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Fal\Stick\Security\Auth is not registered.
     */
    public function testGetAuthException()
    {
        $this->crud->getAuth();
    }

    public function testSetAuth()
    {
        $auth = new Auth($this->fw, new InMemoryUserProvider(), new BcryptPasswordEncoder());

        $this->assertInstanceOf(Auth::class, $this->crud->setAuth($auth)->getAuth());
    }

    public function testAddFunction()
    {
        $this->assertSame('bar', $this->crud->addFunction('foo', function () {
            return 'bar';
        })->call('foo'));
    }

    public function testOption()
    {
        $expected = array(
            'listing' => true,
            'view' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
        );

        $this->assertEquals($expected, $this->crud->option('states'));
    }

    public function testEnable()
    {
        $expected = array(
            'listing' => true,
            'view' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
        );

        $this->assertEquals($expected, $this->crud->enable('view,create')->option('states'));
    }

    public function testDisable()
    {
        $expected = array(
            'listing' => true,
            'view' => false,
            'create' => false,
            'update' => true,
            'delete' => true,
        );

        $this->assertEquals($expected, $this->crud->disable('view,create')->option('states'));
    }

    public function testField()
    {
        $expected = array(
            'listing' => null,
            'view' => 'id,name',
            'create' => 'id,name',
            'update' => null,
            'delete' => null,
        );

        $this->assertEquals($expected, $this->crud->field('view,create', 'id,name')->option('fields'));
    }

    public function testView()
    {
        $expected = array(
            'listing' => null,
            'view' => 'foo',
            'create' => null,
            'update' => null,
            'delete' => null,
        );

        $this->assertEquals($expected, $this->crud->view('view', 'foo')->option('views'));
    }

    public function testRole()
    {
        $expected = array(
            'listing' => null,
            'view' => 'foo',
            'create' => null,
            'update' => null,
            'delete' => null,
        );
        $auth = new Auth($this->fw, new InMemoryUserProvider(), new BcryptPasswordEncoder());
        $this->crud->setAuth($auth);

        $this->assertEquals($expected, $this->crud->role('view', 'foo')->option('roles'));
    }

    public function testRoles()
    {
        $expected = array(
            'listing' => null,
            'view' => 'foo',
            'create' => 'bar',
            'update' => null,
            'delete' => null,
        );
        $auth = new Auth($this->fw, new InMemoryUserProvider(), new BcryptPasswordEncoder());
        $this->crud->setAuth($auth);

        $this->assertEquals($expected, $this->crud->roles(array(
            'view' => 'foo',
            'create' => 'bar',
        ))->option('roles'));
    }

    public function testOptions()
    {
        $this->assertNotEmpty($this->crud->options());
    }

    public function testData()
    {
        $this->assertNotEmpty($this->crud->data());
    }

    public function testPath()
    {
        $this->fw->route('GET home /foo/*', 'foo');
        $this->crud->set('route', 'home');

        $this->assertEquals('/foo/index', $this->crud->path());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No route defined.
     */
    public function testPathException()
    {
        $this->crud->path();
    }

    public function testIsGranted()
    {
        $this->assertTrue($this->crud->isGranted('view'));
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->crud['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->crud['foo']);
    }

    public function testOffsetSet()
    {
        $this->crud['foo'] = 'bar';

        $this->assertEquals('bar', $this->crud['foo']);
    }

    public function testOffsetUnset()
    {
        $this->crud['foo'] = 'bar';
        unset($this->crud['foo']);

        $this->assertNull($this->crud['foo']);
    }

    public function testCallMagic()
    {
        $this->assertEquals('foo', $this->crud->route('foo')->option('route'));
        $this->assertEquals(array('foo'), $this->crud->filters(array('foo'))->option('filters'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Option "filters" expect array value.
     */
    public function testCallMagicException()
    {
        $this->crud->filters(null);
    }

    /**
     * @dataProvider getCruds
     */
    public function testRender($path, $expected)
    {
        $this->fw->route('GET foo /foo/*', 'foo');
        $this->crud
            ->route('foo')
            ->segments($path)
            ->views(array(
                'listing' => 'crud/listing',
                'view' => 'crud/view',
                'create' => 'crud/form',
                'update' => 'crud/form',
                'delete' => 'crud/delete',
            ))
            ->fieldOrders(array('id', 'username', 'password', 'active'))
            ->mapper('user')
            ->form('Fixture\\Form\\FUserForm')
        ;

        $this->assertEquals($expected, $this->crud->render());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No route defined.
     */
    public function testRenderException()
    {
        $this->crud->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No mapper provided.
     */
    public function testRenderException2()
    {
        $this->crud->route('foo')->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No view for state: "foo".
     */
    public function testRenderException3()
    {
        $this->crud->route('foo')->segments('foo')->mapper('user')->render();
    }

    public function testRenderListingSearch()
    {
        $this->fw['GET']['keyword'] = 'foo';
        $this->crud
            ->route('foo')
            ->searchable('username')
            ->views(array(
                'listing' => 'crud/listing',
            ))
            ->mapper('user')
        ;

        $this->assertEquals(file_get_contents(FIXTURE.'files/template/crud/listing_search.html'), $this->crud->render());
    }

    /**
     * @dataProvider getCrudsPost
     */
    public function testRenderPost($path, $message, $messageKey)
    {
        $this->fw['QUIET'] = true;
        $this->fw
            ->on('fw.reroute', function (Fw $fw, $url) {
                $fw['rerouted'] = $url;
            })
            ->route('GET|POST foo /foo/*', function (...$segments) {
                return $this->crud
                    ->segments($segments)
                    ->views(array(
                        'create' => 'crud/form',
                        'update' => 'crud/form',
                        'delete' => 'crud/delete',
                    ))
                    ->beforeCreate(function () {}) // trigger before create
                    ->mapper('user')
                    ->form('Fixture\\Form\\FUserForm')
                    ->formOptions(function () {})
                    ->onPrepareData(function () {})
                    ->render()
                ;
            })
        ;

        $this->fw->mock('POST /foo'.$path, array('f_user_form' => array(
            'username' => 'xfoo',
            '_form' => 'f_user_form',
        )));

        $this->assertNull($this->fw['OUTPUT']);
        $this->assertStringEndsWith('/foo/index?page=1', $this->fw['rerouted']);
        $this->assertEquals($message, $this->fw[$messageKey]);
    }

    /**
     * @expectedException \Fal\Stick\HttpException
     */
    public function testRenderViewException()
    {
        $this->crud
            ->route('foo')
            ->segments(array('view', 100))
            ->views(array(
                'view' => 'crud/view',
            ))
            ->mapper('user')
            ->render()
        ;
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Insufficient primary keys!
     */
    public function testPrepareFilter()
    {
        $this->crud
            ->route('foo')
            ->segments(array('view'))
            ->views(array(
                'view' => 'crud/view',
            ))
            ->mapper('user')
            ->render()
        ;
    }

    public function testPrepareFields()
    {
        $this->fw->route('GET foo /foo/*', 'foo');
        $this->crud
            ->route('foo')
            ->segments('view/1')
            ->views(array(
                'view' => 'crud/view',
            ))
            ->field('view', 'id,username')
            ->mapper('user')
        ;

        $this->assertEquals(file_get_contents(FIXTURE.'files/template/crud/view_id_username.html'), $this->crud->render());
    }

    public function testLoadMapper()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'crud/listing')
            ->mapper('user')
            ->render()
        ;

        $this->assertInstanceOf('Fal\\Stick\\Sql\\Mapper', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper('Fixture\\Mapper\\TUser')->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper($this->fw->instance('Fixture\\Mapper\\TUser'))->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();
    }

    public function testLoadForm()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'crud/listing')
            ->mapper('user')
            ->render()
        ;

        $this->assertInstanceOf('Fal\\Stick\\Html\\Form', $this->crud->get('form'));
        $this->resetCrud();

        $this->crud->form($this->fw->instance('Fixture\\Form\\FUserForm'))->render();
        $this->assertInstanceOf('Fixture\\Form\\FUserForm', $this->crud->get('form'));
        $this->resetCrud();
    }

    public function testRenderRefreshAfterCreate()
    {
        $this->fw['QUIET'] = true;
        $this->fw
            ->on('fw.reroute', function (Fw $fw, $url) {
                $fw['rerouted'] = $url;
            })
            ->route('GET|POST foo /foo/*', function (...$segments) {
                return $this->crud
                    ->segments($segments)
                    ->views(array(
                        'create' => 'crud/create',
                    ))
                    ->mapper('user')
                    ->form('Fixture\\Form\\FUserForm')
                    ->createNew(true)
                    ->render()
                ;
            })
        ;

        $this->fw->mock('POST /foo/create', array('f_user_form' => array(
            'username' => 'xfoo',
            'create_new' => true,
            '_form' => 'f_user_form',
        )));

        $this->assertNull($this->fw['OUTPUT']);
        $this->assertEquals($this->fw['URL'], $this->fw['rerouted']);
    }

    public function getCruds()
    {
        return array(
            array('index', file_get_contents(FIXTURE.'files/template/crud/listing.html')),
            array('view/1', file_get_contents(FIXTURE.'files/template/crud/view.html')),
            array('create', file_get_contents(FIXTURE.'files/template/crud/create.html')),
            array('update/1', file_get_contents(FIXTURE.'files/template/crud/update.html')),
            array('delete/1', file_get_contents(FIXTURE.'files/template/crud/delete.html')),
        );
    }

    public function getCrudsPost()
    {
        return array(
            array('/create', 'Data has been created.', 'alerts_success'),
            array('/update/1', 'Data has been updated.', 'alerts_info'),
            array('/delete/1', 'Data has been deleted.', 'alerts_warning'),
        );
    }

    private function resetCrud()
    {
        $this->crud
            ->set('mapper', null)
            ->set('form', null)
        ;
    }
}
