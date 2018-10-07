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

use Fal\Stick\App;
use Fal\Stick\Crud;
use Fal\Stick\Html\Form;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Template\Template;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    private $app;
    private $crud;

    public function setUp()
    {
        $this->app = new App();
        $this->app
            ->rule(Connection::class, array(
                'args' => array(
                    'options' => array(
                        'dsn' => 'sqlite::memory:',
                        'commands' => array(
                            file_get_contents(FIXTURE.'files/schema.sql'),
                            'insert into user (username) values ("foo"), ("bar"), ("baz")',
                        ),
                    ),
                ),
            ))
        ;
        $template = new Template($this->app, FIXTURE.'template/crud/');
        $this->crud = new Crud($this->app, $template);
    }

    private function resetCrud()
    {
        $ref = new \ReflectionClass($this->crud);

        foreach (array('mapper', 'form') as $name) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($this->crud, null);
        }
    }

    private function removeAllWhiteSpace($str)
    {
        return preg_replace('/\s+/', '', $str);
    }

    public function testGetOption()
    {
        $this->assertNull($this->crud->getOption('title'));
    }

    public function testGetData()
    {
        $this->assertNull($this->crud->getData('foo'));
    }

    public function testSetOption()
    {
        $this->assertEquals('foo', $this->crud->setOption('title', 'foo')->getOption('title'));
        $this->assertEquals(array('foo'), $this->crud->setOption('filters', array('foo'))->getOption('filters'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Option "filters" expect array value.
     */
    public function testOptionException()
    {
        $this->crud->setOption('filters', 'foo');
    }

    public function testData()
    {
        $this->assertEquals('bar', $this->crud->setData('foo', 'bar')->getData('foo'));
    }

    public function testOptionCall()
    {
        $this->assertEquals('foo', $this->crud->title('foo')->getOption('title'));
    }

    public function testDisabled()
    {
        $states = $this->crud->disabled('listing')->getOption('states');

        $this->assertTrue($states['create']);
        $this->assertFalse($states['listing']);
    }

    public function testGetMapper()
    {
        $uMapper = 'Fixture\\Mapper\\User';
        $this->assertNull($this->crud->getMapper());
        $this->resetCrud();

        $mapper = $this->app->instance($uMapper);
        $this->assertInstanceOf($uMapper, $this->crud->mapper($mapper)->getMapper());
        $this->resetCrud();

        $this->assertInstanceOf($uMapper, $this->crud->mapper($uMapper)->getMapper());
        $this->resetCrud();

        $this->assertInstanceOf(Mapper::class, $this->crud->mapper('user')->getMapper());
        $this->resetCrud();
    }

    public function testGetForm()
    {
        $uForm = 'Fixture\\Form\\UserForm';
        $form = $this->app->instance(Form::class);
        $this->assertSame($form, $this->crud->form($form)->getForm());
        $this->resetCrud();

        $this->assertInstanceOf($uForm, $this->crud->form($uForm)->getForm());
        $this->resetCrud();

        $this->crud->formBuild(function () {})->form(null);
        $this->assertEquals(Form::class, get_class($this->crud->getForm()));
    }

    public function renderProvider()
    {
        return array(
            array('foo', 'forbidden.html'),
            array('index', 'listing.html'),
            array('view/1', 'view.html'),
            array('delete/1', 'delete.html'),
            array('create', 'create.html'),
            array('update/1', 'update.html'),
        );
    }

    /**
     * @dataProvider renderProvider
     */
    public function testRender($path, $output)
    {
        $this->crud
            ->segments(explode('/', $path))
            ->views(array(
                'listing' => 'listing.php',
                'view' => 'view.php',
                'create' => 'create.php',
                'update' => 'update.php',
                'delete' => 'delete.php',
                'forbidden' => 'forbidden.php',
            ))
            ->fieldOrders(array('id', 'username', 'password', 'active'))
            ->mapper('user')
            ->form('Fixture\\Form\\UserForm')
        ;
        $expected = $this->removeAllWhiteSpace(file_get_contents(FIXTURE.'template/crud/'.$output));
        $actual = $this->removeAllWhiteSpace($this->crud->render());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No segments provided.
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
        $this->crud->segments(array('foo'))->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No view for state: "foo".
     */
    public function testRenderException3()
    {
        $this->crud->segments(array('foo'))->mapper('user')->render();
    }

    public function testRenderListingSearch()
    {
        $this->app->set('QUERY.keyword', 'foo');
        $this->crud
            ->segments(array('index'))
            ->searchable('username')
            ->views(array(
                'listing' => 'listing.php',
            ))
            ->mapper('user')
        ;
        $expected = $this->removeAllWhiteSpace(file_get_contents(FIXTURE.'template/crud/listing_search.html'));
        $actual = $this->removeAllWhiteSpace($this->crud->render());

        $this->assertEquals($expected, $actual);
    }

    public function renderPostProvider()
    {
        return array(
            array('/create', 'crud_created', 'SESSION.alerts.success'),
            array('/update/1', 'crud_updated', 'SESSION.alerts.info'),
            array('/delete/1', 'crud_deleted', 'SESSION.alerts.warning'),
        );
    }

    /**
     * @dataProvider renderPostProvider
     */
    public function testRenderPost($path, $message, $messageKey)
    {
        $this->app
            ->on('app_reroute', function (App $app, $url) {
                $app->set('rerouted', $url);
            })
            ->route('GET|POST foo /foo/*', function (App $app, ...$segments) {
                return $this->crud
                    ->segments($segments)
                    ->views(array(
                        'create' => 'create.php',
                        'update' => 'update.php',
                        'delete' => 'delete.php',
                    ))
                    ->mapper('user')
                    ->form('Fixture\\Form\\UserForm')
                    ->render()
                ;
            })
            ->set('QUIET', true)
        ;

        $this->app->mock('POST /foo'.$path, array('user_form' => array(
            'username' => 'xfoo',
            '_form' => 'user_form',
        )));

        $expected = $this->app->get('BASEURL').'/foo/index?page=1';
        $this->assertNull($this->app->get('OUTPUT'));
        $this->assertEquals($expected, $this->app->get('rerouted'));
        $this->assertEquals($message, $this->app->get($messageKey));
    }

    /**
     * @expectedException \Fal\Stick\HttpException
     */
    public function testRenderViewException()
    {
        $this->crud
            ->segments(array('view', 100))
            ->views(array(
                'view' => 'view.php',
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
            ->segments(array('view'))
            ->views(array(
                'view' => 'view.php',
            ))
            ->mapper('user')
            ->render()
        ;
    }
}
