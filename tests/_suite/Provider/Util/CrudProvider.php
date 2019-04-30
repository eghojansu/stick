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

namespace Fal\Stick\TestSuite\Provider\Util;

use Fal\Stick\TestSuite\MyTestCase;

class CrudProvider
{
    public function render()
    {
        return array(
            array(
                MyTestCase::read('/crud/listing.html'),
                'index',
                array(
                    'GET' => array('keyword' => 'foo'),
                ),
            ),
            array(
                MyTestCase::read('/crud/create.html'),
                'create',
            ),
            array(
                '/bar/create?page=1',
                'create',
                array(
                    'VERB' => 'POST',
                    'POST' => array(
                        'username' => 'qux',
                        '_form' => 'form',
                        'create_new' => 'on',
                    ),
                ),
            ),
            array(
                '/bar/index?page=1',
                'create',
                array(
                    'VERB' => 'POST',
                    'POST' => array(
                        'username' => 'qux',
                        '_form' => 'form',
                    ),
                ),
            ),
            array(
                MyTestCase::read('/crud/update.html'),
                'update/1',
            ),
            array(
                '/bar/index?page=1',
                '/update/1',
                array(
                    'VERB' => 'POST',
                    'POST' => array(
                        'username' => 'qux',
                        '_form' => 'form',
                    ),
                ),
            ),
            array(
                MyTestCase::read('/crud/delete.html'),
                'delete/1',
            ),
            array(
                '/bar/index?page=1',
                'delete/1',
                array(
                    'VERB' => 'POST',
                ),
            ),
            array(
                MyTestCase::read('/crud/forbidden.html'),
                'foo',
            ),
            array(
                MyTestCase::read('/crud/view.html'),
                'view/1',
            ),
            'view not found' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Not Found',
                )),
                'view/404',
            ),
            'update not found' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Not Found',
                )),
                'update/404',
            ),
            'delete not found' => array(
                MyTestCase::response('error.txt', array(
                    '%code%' => 404,
                    '%verb%' => 'GET',
                    '%path%' => '/',
                    '%text%' => 'Not Found',
                )),
                'delete/404',
            ),
        );
    }

    public function crudExceptions()
    {
        return array(
            array(
                'Mapper is not provided.',
                array(),
            ),
            array(
                'Form is not provided.',
                array(
                    'mapper' => true,
                ),
            ),
            array(
                'Route is not defined.',
                array(
                    'mapper' => true,
                    'form' => true,
                ),
            ),
            array(
                'Route parameter name is not provided.',
                array(
                    'mapper' => true,
                    'form' => true,
                    'routeName' => 'foo',
                ),
            ),
            array(
                'No view for state: listing.',
                array(
                    'mapper' => true,
                    'form' => true,
                    'routeName' => 'foo',
                    'routeParamName' => 'foo',
                ),
            ),
            array(
                'Insufficient primary keys!',
                array(
                    'mapper' => true,
                    'form' => true,
                    'routeName' => 'foo',
                    'routeParamName' => 'foo',
                    'views' => array('view' => 'view.shtml'),
                    'segments' => 'view',
                ),
            ),
        );
    }
}
