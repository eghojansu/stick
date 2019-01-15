<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Database\Sql;

use Fal\Stick\Core;
use Fal\Stick\Database\Sql\QueryBuilder;
use Fal\Stick\Database\Sql\Sql;
use Fixture\Mapper\TUser;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private $qb;
    private $mapper;

    public function setUp()
    {
        $fw = new Core();
        $db = new Sql($fw, 'sqlite::memory:', null, null, [
            file_get_contents(TEST_FIXTURE.'/files/schema.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ]);
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
        $filter = ['id' => 1];
        $options = [
            'limit'  => 1,
            'offset' => 1,
            'group'  => 'id',
            'having' => 'id is not null',
            'order'  => 'id DESC',
            'join'   => 'JOIN profile on profile.user_id = user.id',
        ];
        $expected = [
            ' JOIN profile on profile.user_id = user.id WHERE `id` = :id GROUP BY id HAVING id is not null ORDER BY id DESC LIMIT 1 OFFSET 1',
            [':id' => 1],
        ];

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
        $this->assertEquals([], $this->qb->filter(null));
    }

    public function testFilterException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('BETWEEN operator needs an array operand, string given.');

        $this->qb->filter(['foo ><' => 'str']);
    }

    public function testSelect()
    {
        $actual = $this->qb->select('foo');
        $expected = [
            'SELECT foo FROM `user`',
            [],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testCount()
    {
        $expected = [
            'SELECT COUNT(*) AS `_rows` FROM (SELECT * FROM `user`) AS `_temp`',
            [],
        ];

        $this->assertEquals($expected, $this->qb->count());
    }

    public function testDeleteBatch()
    {
        $expected = [
            'DELETE FROM `user`',
            [],
        ];

        $this->assertEquals($expected, $this->qb->deleteBatch());
    }

    public function testDelete()
    {
        $this->mapper->load('id = 1');

        $expected = [
            'DELETE FROM `user` WHERE `id`=?',
            [1 => [1, 1]],
        ];

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
        $filter = [];

        $filter[] = [
            ['`foo` = :foo', ':foo' => 'bar'],
            ['foo' => 'bar'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', ['bar' => 'baz']],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar) OR baz = 2', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', ['bar' => 'baz'], '|' => 'baz = 2'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar) OR baz = 2 OR qux = 3', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', ['bar' => 'baz'], '|' => 'baz = 2', '| #qux' => 'qux = 3'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', '&' => ['bar' => 'baz']],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar OR `baz` = :baz)', ':foo' => 'bar', ':bar' => 'baz', ':baz' => 'qux'],
            ['foo' => 'bar', '&' => ['bar' => 'baz', '| baz' => 'qux']],
        ];
        $filter[] = [
            ['foo = 1 OR `bar` != :bar', ':bar' => 'baz'],
            ['foo = 1', '| bar !=' => 'baz'],
        ];
        $filter[] = [
            ['foo = 1 OR `bar` != :bar AND (baz = 2)', ':bar' => 'baz'],
            ['foo = 1', '| bar !=' => 'baz', ['baz = 2']],
        ];
        $filter[] = [
            ['`foo` = :foo AND `bar` = :bar', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', 'bar' => 'baz'],
        ];
        $filter[] = [
            ['`foo` = :foo AND `bar` <> :bar', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo' => 'bar', 'bar <>' => 'baz'],
        ];
        $filter[] = [
            ['`foo` LIKE :foo', ':foo' => 'bar'],
            ['foo ~' => 'bar'],
        ];
        $filter[] = [
            ['`foo` NOT LIKE :foo', ':foo' => 'bar'],
            ['foo !~' => 'bar'],
        ];
        $filter[] = [
            ['`foo` SOUNDS LIKE :foo', ':foo' => 'bar'],
            ['foo @' => 'bar'],
        ];
        $filter[] = [
            ['`foo` BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3],
            ['foo ><' => [1, 3]],
        ];
        $filter[] = [
            ['`foo` NOT BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2' => 3],
            ['foo !><' => [1, 3]],
        ];
        $filter[] = [
            ['`foo` IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'],
            ['foo []' => ['foo', 'bar', 'baz']],
        ];
        $filter[] = [
            ['`foo` NOT IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2' => 'bar', ':foo3' => 'baz'],
            ['foo ![]' => ['foo', 'bar', 'baz']],
        ];
        $filter[] = [
            ['`foo` = bar + 1'],
            ['foo' => '```bar + 1'],
        ];
        $filter[] = [
            ['`foo` = :foo AND `foo` <> :foo__2', ':foo' => 1, ':foo__2' => 2],
            ['foo' => 1, 'foo <>' => 2],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`foo` <> :foo__2 AND `foo` > :foo__3)', ':foo' => 1, ':foo__2' => 2, ':foo__3' => 0],
            ['foo' => 1, ['foo <>' => 2, 'foo >' => 0]],
        ];

        return $filter;
    }

    public function updateProvider()
    {
        return [
            [
                null,
                null,
                '',
                [],
            ],
            [
                1,
                [
                    'id'       => 4,
                    'username' => 'xfoo',
                    'password' => 'xbar',
                    'active'   => 0,
                ],
                'UPDATE `user` SET `id`=?,`username`=?,`password`=?,`active`=? WHERE `id`=?',
                [
                    1 => [4, 1],
                    ['xfoo', 2],
                    ['xbar', 2],
                    [0, 1],
                    [1, 1],
                ],
            ],
        ];
    }

    public function insertProvider()
    {
        return [
            [
                [],
                '',
                [],
            ],
            [
                ['username' => 'xfoo', 'password' => 'xbar', 'active' => 0],
                'INSERT INTO `user` (`username`,`password`,`active`) VALUES (?,?,?)',
                [
                    1 => ['xfoo', 2],
                    ['xbar', 2],
                    [0, 1],
                ],
            ],
        ];
    }
}
