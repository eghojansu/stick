<?php

/**
 * This file is part of the eghojansu/stick-test library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Sql;

use Fal\Stick\Fw;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\QueryBuilder;
use Fixture\Mapper\TUser;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private $qb;
    private $mapper;

    public function setUp()
    {
        $fw = new Fw('phpunit-test');
        $db = new Connection($fw, 'sqlite::memory:', null, null, array(
            file_get_contents(TEST_FIXTURE.'files/schema.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ));
        $this->mapper = new TUser($fw, $db);
        $this->qb = new QueryBuilder($this->mapper);
    }

    public function testFields()
    {
        $expected = '`user`.`id`, `user`.`username`, `user`.`password`, `user`.`active`, (1 + 1) AS `foo`';

        $this->assertEquals($expected, $this->qb->fields());
    }

    public function testResolveCriteria()
    {
        $filter = array('id' => 1);
        $options = array(
            'limit' => 1,
            'offset' => 1,
            'group' => 'id',
            'having' => 'id is not null',
            'order' => 'id DESC',
            'join' => 'JOIN profile on profile.user_id = user.id',
        );
        $expected = array(
            ' JOIN profile on profile.user_id = user.id WHERE `id` = :id GROUP BY id HAVING id is not null ORDER BY id DESC LIMIT 1 OFFSET 1',
            array(':id' => 1),
        );

        $this->assertEquals($expected, $this->qb->resolveCriteria($filter, $options));
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter($expected, $filter)
    {
        $this->assertEquals($expected, $this->qb->filter($filter));
    }

    public function testFilterEmpty()
    {
        $this->assertEquals(array(), $this->qb->filter(null));
    }

    public function testFilterException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('BETWEEN operator needs an array operand, string given.');

        $this->qb->filter(array('foo ><' => 'str'));
    }

    public function testSelect()
    {
        $actual = $this->qb->select('foo');
        $expected = array(
            'SELECT foo FROM `user`',
            array(),
        );

        $this->assertEquals($expected, $actual);
    }

    public function testCount()
    {
        $expected = array(
            'SELECT COUNT(*) AS `_rows` FROM (SELECT * FROM `user`) AS `_temp`',
            array(),
        );

        $this->assertEquals($expected, $this->qb->count());
    }

    public function testDeleteBatch()
    {
        $expected = array(
            'DELETE FROM `user`',
            array(),
        );

        $this->assertEquals($expected, $this->qb->deleteBatch());
    }

    public function testDelete()
    {
        $this->mapper->load('id = 1');

        $expected = array(
            'DELETE FROM `user` WHERE `id`=?',
            array(1 => array(1, 1)),
        );

        $this->assertEquals($expected, $this->qb->delete());
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($id, $updates, $expectedSql, $expectedArgs)
    {
        if ($id) {
            $this->mapper->load('id = '.$id);
        }

        $this->mapper->fromArray((array) $updates);

        list($sql, $args) = $this->qb->update();

        $this->assertEquals($expectedSql, $sql);
        $this->assertEquals($expectedArgs, $args);
    }

    /**
     * @dataProvider insertProvider
     */
    public function testInsert($values, $expectedSql, $expectedArgs)
    {
        $this->mapper->fromArray((array) $values);

        list($sql, $args) = $this->qb->insert();

        $this->assertEquals($expectedSql, $sql);
        $this->assertEquals($expectedArgs, $args);
    }

    public function filterProvider()
    {
        $filter = array();

        $filter[] = array(
            array('`foo` = :foo', ':foo' => 'bar'),
            array('foo' => 'bar'),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', array('bar' => 'baz')),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`bar` = :bar) OR baz = 2', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', array('bar' => 'baz'), '|' => 'baz = 2'),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`bar` = :bar) OR baz = 2 OR qux = 3', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', array('bar' => 'baz'), '|' => 'baz = 2', '| #qux' => 'qux = 3'),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', '&' => array('bar' => 'baz')),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`bar` = :bar OR `baz` = :baz)', ':foo' => 'bar', ':bar' => 'baz', ':baz' => 'qux'),
            array('foo' => 'bar', '&' => array('bar' => 'baz', '| baz' => 'qux')),
        );
        $filter[] = array(
            array('foo = 1 OR `bar` != :bar', ':bar' => 'baz'),
            array('foo = 1', '| bar !=' => 'baz'),
        );
        $filter[] = array(
            array('foo = 1 OR `bar` != :bar AND (baz = 2)', ':bar' => 'baz'),
            array('foo = 1', '| bar !=' => 'baz', array('baz = 2')),
        );
        $filter[] = array(
            array('`foo` = :foo AND `bar` = :bar', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', 'bar' => 'baz'),
        );
        $filter[] = array(
            array('`foo` = :foo AND `bar` <> :bar', ':foo' => 'bar', ':bar' => 'baz'),
            array('foo' => 'bar', 'bar <>' => 'baz'),
        );
        $filter[] = array(
            array('`foo` LIKE :foo', ':foo' => 'bar'),
            array('foo ~' => 'bar'),
        );
        $filter[] = array(
            array('`foo` NOT LIKE :foo', ':foo' => 'bar'),
            array('foo !~' => 'bar'),
        );
        $filter[] = array(
            array('`foo` SOUNDS LIKE :foo', ':foo' => 'bar'),
            array('foo @' => 'bar'),
        );
        $filter[] = array(
            array('`foo` BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3),
            array('foo ><' => array(1, 3)),
        );
        $filter[] = array(
            array('`foo` NOT BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3),
            array('foo !><' => array(1, 3)),
        );
        $filter[] = array(
            array('`foo` IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'),
            array('foo []' => array('foo', 'bar', 'baz')),
        );
        $filter[] = array(
            array('`foo` NOT IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'),
            array('foo ![]' => array('foo', 'bar', 'baz')),
        );
        $filter[] = array(
            array('`foo` = bar + 1'),
            array('foo' => '```bar + 1'),
        );
        $filter[] = array(
            array('`foo` = :foo AND `foo` <> :foo__2', ':foo' => 1, ':foo__2' => 2),
            array('foo' => 1, 'foo <>' => 2),
        );
        $filter[] = array(
            array('`foo` = :foo AND (`foo` <> :foo__2 AND `foo` > :foo__3)', ':foo' => 1, ':foo__2' => 2, ':foo__3' => 0),
            array('foo' => 1, array('foo <>' => 2, 'foo >' => 0)),
        );

        return $filter;
    }

    public function updateProvider()
    {
        return array(
            array(
                null,
                null,
                '',
                array(),
            ),
            array(
                1,
                array(
                    'id' => 4,
                    'username' => 'xfoo',
                    'password' => 'xbar',
                    'active' => 0,
                ),
                'UPDATE `user` SET `id`=?,`username`=?,`password`=?,`active`=? WHERE `id`=?',
                array(
                    1 => array(4, 1),
                    array('xfoo', 2),
                    array('xbar', 2),
                    array(0, 1),
                    array(1, 1),
                ),
            ),
        );
    }

    public function insertProvider()
    {
        return array(
            array(
                array(),
                '',
                array(),
            ),
            array(
                array('username' => 'xfoo', 'password' => 'xbar', 'active' => 0),
                'INSERT INTO `user` (`username`,`password`,`active`) VALUES (?,?,?)',
                array(
                    1 => array('xfoo', 2),
                    array('xbar', 2),
                    array(0, 1),
                ),
            ),
        );
    }
}
