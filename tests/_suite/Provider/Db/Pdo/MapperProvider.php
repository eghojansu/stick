<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Db\Pdo;

class MapperProvider
{
    public function find()
    {
        return array(
            array(
                array(
                    'id' => 1,
                    'username' => 'foo',
                    'password' => null,
                    'active' => 1,
                ),
                1,
            ),
            array(
                array(
                    'id' => 2,
                    'username' => 'bar',
                    'password' => null,
                    'active' => 1,
                ),
                array('2'),
            ),
            array(
                'Mapper has no key.',
                null,
                'nokey',
                'LogicException',
            ),
            array(
                array(
                    'user_id' => 1,
                    'friend_id' => 2,
                    'level' => 3,
                ),
                '1,2',
                'friends',
            ),
            array(
                'Insufficient keys, expected exactly 2 keys, 1 given.',
                '1',
                'friends',
                'LogicException',
            ),
            array(
                'Insufficient keys, expected exactly 2 keys, 3 given.',
                array(2, 3, 4),
                'friends',
                'LogicException',
            ),
        );
    }

    public function paginate()
    {
        return array(
            array(array(
                'count' => 3,
                'end' => 3,
                'limit' => 10,
                'page' => 1,
                'pages' => 1,
                'start' => 1,
                'subset' => 3,
                'total' => 3,
            ), 1),
            array(array(
                'count' => 1,
                'end' => 1,
                'limit' => 10,
                'page' => 1,
                'pages' => 1,
                'start' => 1,
                'subset' => 1,
                'total' => 1,
            ), 1, 'id = 1', array(
                'limit' => 10,
            )),
            array(array(
                'count' => 1,
                'end' => 1,
                'limit' => 1,
                'page' => 1,
                'pages' => 3,
                'start' => 1,
                'subset' => 1,
                'total' => 3,
            ), 1, null, array(
                'limit' => 1,
            )),
            array(array(
                'count' => 1,
                'end' => 2,
                'limit' => 1,
                'page' => 2,
                'pages' => 3,
                'start' => 2,
                'subset' => 1,
                'total' => 3,
            ), 2, null, array(
                'limit' => 1,
            )),
        );
    }

    public function prepareRelation()
    {
        $expected = array(
            'id' => 1,
            'fullname' => 'fooname',
            'user_id' => 1,
        );

        return array(
            'with table name' => array(
                $expected,
                array('profile'),
            ),
            'with relation' => array(
                $expected,
                array('profile', 'user_id = id'),
            ),
            'with relation (fk only)' => array(
                $expected,
                array('profile', 'user_id'),
            ),
            'with extra filter' => array(
                $expected,
                array('profile', null, 'id = 1'),
            ),
            'with mapper class' => array(
                $expected,
                array('Fal\\Stick\\TestSuite\\Classes\\Mapper\\UserProfileSample'),
            ),
            'with instance of mapper' => array(
                $expected,
                array('Fal\\Stick\\TestSuite\\Classes\\Mapper\\UserProfileSample'),
                null,
                true,
            ),
            'not a table and not a subclass of mapper' => array(
                'Mapper should be an instance of Fal\\Stick\\Db\\Pdo\\Mapper.',
                array('stdClass'),
                'LogicException',
            ),
            'with no relation' => array(
                'No relation defined.',
                array('nokey', ''),
                'LogicException',
                false,
                'nokey',
            ),
        );
    }
}
