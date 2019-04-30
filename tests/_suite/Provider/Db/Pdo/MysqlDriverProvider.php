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

use Fal\Stick\Db\Pdo\Schema;

class MysqlDriverProvider
{
    public function sqlSelect()
    {
        return array(
            array(
                array(
                    'SELECT foo FROM `user` AS `u`'.
                    ' WHERE id = 1'.
                    ' GROUP BY `id`'.
                    ' HAVING id > 1'.
                    ' ORDER BY `username`'.
                    ' LIMIT 10'.
                    ' OFFSET 5'.
                    ' /* foo */',
                    array(),
                ),
                'foo',
                'user',
                'u',
                'id = 1',
                array(
                    'group' => 'id',
                    'having' => 'id > 1',
                    'order' => 'username',
                    'limit' => 10,
                    'offset' => 5,
                    'comment' => 'foo',
                ),
            ),
        );
    }

    public function sqlCount()
    {
        return array(
            array(
                array(
                    'SELECT COUNT(*) AS _rows FROM `user`',
                    array(),
                ),
                null,
                'user',
            ),
            array(
                array(
                    'SELECT COUNT(*) AS _rows FROM (SELECT id, username, password FROM `user` WHERE `id` > :id'.
                    ' GROUP BY `id` desc, `username` asc, `password`) AS _temp',
                    array(':id' => 1),
                ),
                null,
                'user',
                null,
                array('id >' => 1),
                array(
                    'group' => 'id desc, username asc, password',
                ),
            ),
        );
    }

    public function filter()
    {
        return array(
            array(
                array('foo = bar'),
                'foo = bar',
            ),
            array(
                array('`foo` = :foo', ':foo' => 'bar'),
                array('foo' => 'bar'),
            ),
            array(
                array('`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', array('bar' => 'baz')),
            ),
            array(
                array('`foo` = :foo AND (`bar` = :bar) OR baz = 2', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', array('bar' => 'baz'), '|' => 'baz = 2'),
            ),
            array(
                array('`foo` = :foo AND (`bar` = :bar) OR baz = 2 OR qux = 3', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', array('bar' => 'baz'), '|' => 'baz = 2', '| #qux' => 'qux = 3'),
            ),
            array(
                array('`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', '&' => array('bar' => 'baz')),
            ),
            array(
                array('`foo` = :foo AND (`bar` = :bar OR `baz` = :baz)', ':foo' => 'bar', ':bar' => 'baz', ':baz' => 'qux'),
                array('foo' => 'bar', '&' => array('bar' => 'baz', '| baz' => 'qux')),
            ),
            array(
                array('foo = 1 OR `bar` != :bar', ':bar' => 'baz'),
                array('foo = 1', '| bar !=' => 'baz'),
            ),
            array(
                array('foo = 1 OR `bar` != :bar AND (baz = 2)', ':bar' => 'baz'),
                array('foo = 1', '| bar !=' => 'baz', array('baz = 2')),
            ),
            array(
                array('`foo` = :foo AND `bar` = :bar', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', 'bar' => 'baz'),
            ),
            array(
                array('`foo` = :foo AND `bar` <> :bar', ':foo' => 'bar', ':bar' => 'baz'),
                array('foo' => 'bar', 'bar <>' => 'baz'),
            ),
            array(
                array('`foo` LIKE :foo', ':foo' => 'bar'),
                array('foo ~' => 'bar'),
            ),
            array(
                array('`foo` NOT LIKE :foo', ':foo' => 'bar'),
                array('foo !~' => 'bar'),
            ),
            array(
                array('`foo` SOUNDS LIKE :foo', ':foo' => 'bar'),
                array('foo @' => 'bar'),
            ),
            array(
                array('`foo` BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3),
                array('foo ><' => array(1, 3)),
            ),
            array(
                array('`foo` NOT BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3),
                array('foo !><' => array(1, 3)),
            ),
            array(
                array('`foo` IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'),
                array('foo []' => array('foo', 'bar', 'baz')),
            ),
            array(
                array('`foo` NOT IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'),
                array('foo ![]' => array('foo', 'bar', 'baz')),
            ),
            array(
                array('`foo` = bar + 1'),
                array('foo' => '```bar + 1'),
            ),
            array(
                array('`foo` = :foo AND `foo` <> :foo__2', ':foo' => 1, ':foo__2' => 2),
                array('foo' => 1, 'foo <>' => 2),
            ),
            array(
                array('`foo` = :foo AND (`foo` <> :foo__2 AND `foo` > :foo__3)', ':foo' => 1, ':foo__2' => 2, ':foo__3' => 0),
                array('foo' => 1, array('foo <>' => 2, 'foo >' => 0)),
            ),
            array(
                'Operator needs an array operand, string given.',
                array('foo ><' => 'str'),
                'LogicException',
            ),
        );
    }

    public function sqlInsert()
    {
        return array(
            array(
                array(
                    'INSERT INTO `foo` (`bar`) VALUES (?)',
                    array(array('bar', 2)),
                    'foo',
                ),
                'foo',
                (new Schema())->add('foo', null, false, true, 'int', 1)->add('bar'),
                array(
                    'bar' => 'bar',
                ),
            ),
            array(
                array(
                    'INSERT INTO `foo` (`bar`) VALUES (?)',
                    array(array('bar', 2)),
                    'foo',
                ),
                'foo',
                (new Schema())->add('foo', null, false, true, 'int', 1)->add('bar'),
                array(
                    'foo' => null,
                    'bar' => 'bar',
                ),
            ),
            array(
                array(),
                'foo',
                (new Schema())->add('foo'),
                array(
                    'bar' => 'bar',
                ),
            ),
            array(
                'Field cannot be null: foo.',
                'foo',
                (new Schema())->add('foo', null, false),
                array(
                    'foo' => null,
                ),
                'LogicException',
            ),
        );
    }

    public function sqlUpdate()
    {
        return array(
            array(
                array(
                    'UPDATE `foo` SET `bar` = ? WHERE `foo` = ?',
                    array(array('bar', 2), array(1, 1)),
                ),
                'foo',
                (new Schema())->add('foo', null, false, true, 'int', 1)->add('bar'),
                array(
                    'bar' => 'bar',
                ),
                array(
                    'foo' => 1,
                ),
            ),
            array(
                array(),
                'foo',
                (new Schema())->add('foo', null, false, true, 'int', 1)->add('bar'),
                array(),
                array(
                    'foo' => 1,
                ),
            ),
            array(
                'Field cannot be null: bar.',
                'foo',
                (new Schema())->add('foo', null, false, true, 'int', 1)->add('bar', null, false),
                array(
                    'bar' => null,
                ),
                array(
                    'foo' => 1,
                ),
                'LogicException',
            ),
        );
    }

    public function sqlDelete()
    {
        return array(
            array(
                array(
                    'DELETE FROM `foo` WHERE `id` = ?',
                    array(array(1, 1)),
                ),
                'foo',
                (new Schema())->add('id', null, false, true, 'int', 1),
                array('id' => 1),
            ),
            array(
                array(),
                'foo',
                (new Schema())->add('id'),
                array('id' => 1),
            ),
        );
    }

    public function sqlDeleteBatch()
    {
        return array(
            array(
                array(
                    'DELETE FROM `foo` WHERE id = 1',
                    array(),
                ),
                'foo',
                'id = 1',
            ),
        );
    }
}
