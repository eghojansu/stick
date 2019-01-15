<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Util;

use Fal\Stick\Core;
use Fal\Stick\Database\Sql\Sql;
use Fal\Stick\Security\Auth;
use Fal\Stick\Template\Template;
use Fal\Stick\Util\Crud;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    private $core;
    private $crud;

    public function setUp()
    {
        $this->core = new Core();
        $this->core
            ->rule(Sql::class, [
                'arguments' => [
                    'core'     => '%core%',
                    'dsn'      => 'sqlite::memory:',
                    'username' => null,
                    'password' => null,
                    'commands' => [
                        file_get_contents(TEST_FIXTURE.'/files/schema.sql'),
                        'insert into user (username) values ("foo"), ("bar"), ("baz")',
                    ],
                    'options' => null,
                ],
            ])
            ->rule(Auth::class, [
                'arguments' => [
                    'core'     => '%core%',
                    'provider' => 'Fal\\Stick\\Security\\InMemoryUserProvider',
                    'encoder'  => 'Fal\\Stick\\Security\\PlainPasswordEncoder',
                ],
            ]);
        $this->crud = new Crud($this->core, new Template($this->core, TEST_FIXTURE.'/crud/'));
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
        $this->assertInstanceOf('Fal\\Stick\\Security\\Auth', $this->crud->getAuth());
    }

    public function testAddFunction()
    {
        $this->assertSame('bar', $this->crud->addFunction('foo', function () {
            return 'bar';
        })->call('foo'));
    }

    public function testOption()
    {
        $expected = [
            'listing' => true,
            'view'    => true,
            'create'  => true,
            'update'  => true,
            'delete'  => true,
        ];

        $this->assertEquals($expected, $this->crud->option('states'));
    }

    public function testEnable()
    {
        $expected = [
            'listing' => true,
            'view'    => true,
            'create'  => true,
            'update'  => true,
            'delete'  => true,
        ];

        $this->assertEquals($expected, $this->crud->enable('view,create')->option('states'));
    }

    public function testDisable()
    {
        $expected = [
            'listing' => true,
            'view'    => false,
            'create'  => false,
            'update'  => true,
            'delete'  => true,
        ];

        $this->assertEquals($expected, $this->crud->disable('view,create')->option('states'));
    }

    public function testField()
    {
        $expected = [
            'listing' => null,
            'view'    => 'id,name',
            'create'  => 'id,name',
            'update'  => null,
            'delete'  => null,
        ];

        $this->assertEquals($expected, $this->crud->field('view,create', 'id,name')->option('fields'));
    }

    public function testView()
    {
        $expected = [
            'listing' => null,
            'view'    => 'foo',
            'create'  => null,
            'update'  => null,
            'delete'  => null,
        ];

        $this->assertEquals($expected, $this->crud->view('view', 'foo')->option('views'));
    }

    public function testRole()
    {
        $expected = [
            'listing' => null,
            'view'    => 'foo',
            'create'  => null,
            'update'  => null,
            'delete'  => null,
        ];

        $this->assertEquals($expected, $this->crud->role('view', 'foo')->option('roles'));
    }

    public function testRoles()
    {
        $expected = [
            'listing' => null,
            'view'    => 'foo',
            'create'  => 'bar',
            'update'  => null,
            'delete'  => null,
        ];

        $this->assertEquals($expected, $this->crud->roles([
            'view'   => 'foo',
            'create' => 'bar',
        ])->option('roles'));
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
        $this->core->route('GET home /foo/@segments*', 'foo');
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
        $this->crud->role('update', 'foo');
        $this->assertTrue($this->crud->isGranted('view'));
        $this->assertFalse($this->crud->isGranted('update'));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->crud->foo);
    }

    public function testCallMagic()
    {
        $this->assertEquals('foo', $this->crud->route('foo')->option('route'));
        $this->assertEquals(['foo'], $this->crud->filters(['foo'])->option('filters'));
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
        $this->core->route('GET foo /foo/@segments*', 'foo');
        $this->crud
            ->route('foo')
            ->segments($path)
            ->views([
                'listing' => 'listing',
                'view'    => 'view',
                'create'  => 'form',
                'update'  => 'form',
                'delete'  => 'delete',
            ])
            ->fieldOrders(['id', 'username', 'password', 'active'])
            ->mapper('user')
            ->form('Fixture\\Form\\FUserForm');

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
        $this->core->set('GET.keyword', 'foo');
        $this->crud
            ->route('foo')
            ->searchable('username')
            ->views([
                'listing' => 'listing',
            ])
            ->mapper('user');

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'/crud/listing_search.html'), $this->crud->render());
    }

    /**
     * @dataProvider renderPostProvider
     */
    public function testRenderPost($path, $message, $messageKey)
    {
        $this->core->set('QUIET', true);
        $this->core
            ->on('core.reroute', function ($event) {
                $event->getCore()->set('rerouted', $event->getUrl());
            })
            ->route('GET|POST foo /foo/@segments*', function ($segments) {
                return $this->crud
                    ->segments($segments)
                    ->views([
                        'create' => 'form',
                        'update' => 'form',
                        'delete' => 'delete',
                    ])
                    ->beforeCreate(function () {
                    }) // trigger before create
                    ->mapper('user')
                    ->form('Fixture\\Form\\FUserForm')
                    ->formOptions(function () {
                    })
                    ->onPrepareData(function () {
                    })
                    ->render();
            });

        $this->core->mock('POST /foo'.$path, ['f_user_form' => [
            'username' => 'xfoo',
            '_form'    => 'f_user_form',
        ]]);

        $this->assertNull($this->core->get('OUTPUT'));
        $this->assertStringEndsWith('/foo/index?page=1', $this->core->get('rerouted'));
        $this->assertEquals($message, $this->core->get($messageKey));
    }

    public function testRenderViewException()
    {
        $this->core->set('QUIET', true);
        $this->crud
            ->route('foo')
            ->segments(['view', 100])
            ->views([
                'view' => 'crud/view',
            ])
            ->mapper('user')
            ->render();

        $this->assertEquals(404, $this->core->get('CODE'));
        $this->assertContains('404 - Not Found', $this->core->get('OUTPUT'));
    }

    public function testPrepareFilter()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Insufficient primary keys!');

        $this->crud
            ->route('foo')
            ->segments(['view'])
            ->views([
                'view' => 'view',
            ])
            ->mapper('user')
            ->render();
    }

    public function testPrepareFields()
    {
        $this->core->route('GET foo /foo/@segments*', 'foo');
        $this->crud
            ->route('foo')
            ->segments('view/1')
            ->views([
                'view' => 'view',
            ])
            ->field('view', 'id,username')
            ->mapper('user');

        $this->assertEquals(file_get_contents(TEST_FIXTURE.'/crud/view_id_username.html'), $this->crud->render());
    }

    public function testLoadMapper()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'listing')
            ->mapper('user')
            ->render();

        $this->assertInstanceOf('Fal\\Stick\\Database\\Sql\\Mapper', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper('Fixture\\Mapper\\TUser')->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();

        $this->crud->mapper($this->core->createInstance('Fixture\\Mapper\\TUser'))->render();
        $this->assertInstanceOf('Fixture\\Mapper\\TUser', $this->crud->get('mapper'));
        $this->resetCrud();
    }

    public function testLoadForm()
    {
        $this->crud
            ->route('foo')
            ->view('listing', 'listing')
            ->mapper('user')
            ->render();

        $this->assertInstanceOf('Fal\\Stick\\Form\\Form', $this->crud->get('form'));
        $this->resetCrud();

        $this->crud->form($this->core->createInstance('Fixture\\Form\\FUserForm'))->render();
        $this->assertInstanceOf('Fixture\\Form\\FUserForm', $this->crud->get('form'));
        $this->resetCrud();
    }

    public function testRenderRefreshAfterCreate()
    {
        $this->core->set('QUIET', true);
        $this->core
            ->on('core_reroute', function (Core $core, $url) {
                $core->set('rerouted', $url);
            })
            ->route('GET|POST foo /foo/@segments*', function ($segments) {
                return $this->crud
                    ->segments($segments)
                    ->views([
                        'create' => 'create',
                    ])
                    ->mapper('user')
                    ->form('Fixture\\Form\\FUserForm')
                    ->createNew(true)
                    ->render();
            });

        $this->core->mock('POST /foo/create', ['f_user_form' => [
            'username'   => 'xfoo',
            'create_new' => true,
            '_form'      => 'f_user_form',
        ]]);

        $this->assertNull($this->core->get('OUTPUT'));
        $this->assertEquals($this->core->get('URL'), $this->core->get('rerouted'));
    }

    public function renderProvider()
    {
        return [
            ['index', file_get_contents(TEST_FIXTURE.'/crud/listing.html')],
            ['view/1', file_get_contents(TEST_FIXTURE.'/crud/view.html')],
            ['create', file_get_contents(TEST_FIXTURE.'/crud/create.html')],
            ['update/1', file_get_contents(TEST_FIXTURE.'/crud/update.html')],
            ['delete/1', file_get_contents(TEST_FIXTURE.'/crud/delete.html')],
        ];
    }

    public function renderPostProvider()
    {
        return [
            ['/create', 'Data has been created.', 'SESSION.alerts.success'],
            ['/update/1', 'Data has been updated.', 'SESSION.alerts.info'],
            ['/delete/1', 'Data has been deleted.', 'SESSION.alerts.warning'],
        ];
    }

    private function resetCrud()
    {
        $this->crud
            ->set('mapper', null)
            ->set('form', null);
    }
}
