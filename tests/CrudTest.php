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

use Fal\Stick\Crud;
use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\BcryptPasswordEncoder;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Template\Template;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    private $fw;
    private $crud;

    public function setUp()
    {
        $this->fw = new Core('phpunit-test');
        $this->fw
            ->rule(Connection::class, array(
                'arguments' => array(
                    'fw' => '%fw%',
                    'dsn' => 'sqlite::memory:',
                    'username' => null,
                    'password' => null,
                    'commands' => array(
                        file_get_contents(TEST_FIXTURE.'files/schema.sql'),
                        'insert into user (username) values ("foo"), ("bar"), ("baz")',
                    ),
                    'options' => null,
                ),
            ))
        ;
        $this->crud = new Crud($this->fw, new Template($this->fw, TEST_FIXTURE.'crud/'));
    }

    public function tearDown()
    {
        $this->crud->clear('SESSION');
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

    public function testGetAuthException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Fal\Stick\Security\Auth is not registered.');

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
        $this->fw->route('GET home /foo/@segments*', 'foo');
        $this->crud->set('route', 'home');

        $this->assertEquals('/foo/index', $this->crud->path());
    }

    public function testPathException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No route defined.');

        $this->crud->path();
    }

    public function testIsGranted()
    {
        $this->assertTrue($this->crud->isGranted('view'));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->crud->foo);
    }

    public function testCallMagic()
    {
        $this->assertEquals('foo', $this->crud->route('foo')->option('route'));
        $this->assertEquals(array('foo'), $this->crud->filters(array('foo'))->option('filters'));
    }

    public function testCallMagicException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Option "filters" expect array value.');

        $this->crud->filters(null);
    }

    /**
     * @dataProvider renderProvider
     */
    public function testRender($path, $expected)
    {
        $this->fw->route('GET foo /foo/@segments*', 'foo');
        $this->crud
            ->route('foo')
            ->segments($path)
            ->views(array(
                'listing' => 'listing',
                'view' => 'view',
                'create' => 'form',
                'update' => 'form',
                'delete' => 'delete',
            ))
            ->fieldOrders(array('id', 'username', 'password', 'active'))
            ->mapper('user')
            ->form('Fixture\\Form\\FUserForm')
        ;

        $this->assertEquals($expected, $this->crud->render());
    }

    public function testRenderException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No route defined.');

        $this->crud->render();
    }

    public function testRenderException2()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No mapper provided.');

        $this->crud->route('foo')->render();
    }

    public function testRenderException3()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No view for state: "foo".');

        $this->crud->route('foo')->segments('foo')->mapper('user')->render();
    }

    public function testRenderListingSearch()
    {
        $this->fw->set('GET.keyword', 'foo');
        $this->crud
            ->route('foo')
            ->searchable('username')
            ->views(array(
                'listing' => 'listing',
            ))
            ->mapper('user')
        ;

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'crud/listing_search.html'), $this->crud->render());
    }

    /**
     * @dataProvider renderPostProvider
     */
    public function testRenderPost($path, $message, $messageKey)
    {
        $this->fw->set('QUIET', true);
        $this->fw
            ->on('fw_reroute', function (Core $fw, $url) {
                $fw->set('rerouted', $url);
            })
            ->route('GET|POST foo /foo/@segments*', function ($segments) {
                return $this->crud
                    ->segments($segments)
                    ->views(array(
                        'create' => 'form',
                        'update' => 'form',
                        'delete' => 'delete',
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

        $this->assertNull($this->fw->get('OUTPUT'));
        $this->assertStringEndsWith('/foo/index?page=1', $this->fw->get('rerouted'));
        $this->assertEquals($message, $this->fw->get($messageKey));
    }

    public function testRenderViewException()
    {
        $this->expectException('Fal\\Stick\\HttpException');

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

    public function testPrepareFilter()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Insufficient primary keys!');

        $this->crud
            ->route('foo')
            ->segments(array('view'))
            ->views(array(
                'view' => 'view',
            ))
            ->mapper('user')
            ->render()
        ;
    }

    public function testPrepareFields()
    {
        $this->fw->route('GET foo /foo/@segments*', 'foo');
        $this->crud
            ->route('foo')
            ->segments('view/1')
            ->views(array(
                'view' => 'view',
            ))
            ->field('view', 'id,username')
            ->mapper('user')
        ;

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'crud/view_id_username.html'), $this->crud->render());
    }

    public function testLoadMapper()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'listing')
            ->mapper('user')
            ->render()
        ;

        $this->assertInstanceOf('Fal\\Stick\\Sql\\Mapper', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper('Fixture\\Mapper\\TUser')->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper($this->fw->createInstance('Fixture\\Mapper\\TUser'))->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();
    }

    public function testLoadForm()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'listing')
            ->mapper('user')
            ->render()
        ;

        $this->assertInstanceOf('Fal\\Stick\\Form\\Form', $this->crud->get('form'));
        $this->resetCrud();

        $this->crud->form($this->fw->createInstance('Fixture\\Form\\FUserForm'))->render();
        $this->assertInstanceOf('Fixture\\Form\\FUserForm', $this->crud->get('form'));
        $this->resetCrud();
    }

    public function testRenderRefreshAfterCreate()
    {
        $this->fw->set('QUIET', true);
        $this->fw
            ->on('fw_reroute', function (Core $fw, $url) {
                $fw->set('rerouted', $url);
            })
            ->route('GET|POST foo /foo/@segments*', function ($segments) {
                return $this->crud
                    ->segments($segments)
                    ->views(array(
                        'create' => 'create',
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

        $this->assertNull($this->fw->get('OUTPUT'));
        $this->assertEquals($this->fw->get('URL'), $this->fw->get('rerouted'));
    }

    public function renderProvider()
    {
        return array(
            array('index', file_get_contents(TEST_FIXTURE.'crud/listing.html')),
            array('view/1', file_get_contents(TEST_FIXTURE.'crud/view.html')),
            array('create', file_get_contents(TEST_FIXTURE.'crud/create.html')),
            array('update/1', file_get_contents(TEST_FIXTURE.'crud/update.html')),
            array('delete/1', file_get_contents(TEST_FIXTURE.'crud/delete.html')),
        );
    }

    public function renderPostProvider()
    {
        return array(
            array('/create', 'Data has been created.', 'SESSION.alerts.success'),
            array('/update/1', 'Data has been updated.', 'SESSION.alerts.info'),
            array('/delete/1', 'Data has been deleted.', 'SESSION.alerts.warning'),
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
