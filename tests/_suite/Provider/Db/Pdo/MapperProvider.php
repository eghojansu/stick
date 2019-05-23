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
    public function findKey()
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
                'Insufficient keys value, expected exactly 2 parameter, 1 given.',
                '1',
                'friends',
                'LogicException',
            ),
            array(
                'Insufficient keys value, expected exactly 2 parameter, 3 given.',
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
                'subset' => 3,
                'total' => 3,
                'count' => 3,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 3,
            ), 1),
            array(array(
                'subset' => 1,
                'total' => 1,
                'count' => 1,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, 'id = 1', array(
                'limit' => 10,
            )),
            array(array(
                'subset' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, null, array(
                'limit' => 1,
            )),
            array(array(
                'subset' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 2,
                'start' => 2,
                'end' => 2,
            ), 2, null, array(
                'limit' => 1,
            )),
        );
    }
}