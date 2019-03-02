<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 04, 2019 19:29
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database\Driver;

use Fal\Stick\Cache\NoCache;
use Fal\Stick\Database\Adhoc;
use Fal\Stick\Database\Driver\AbstractPDOSqlDriver;
use Fal\Stick\Database\Field;
use Fal\Stick\Database\Row;
use Fal\Stick\TestSuite\TestCase;

class AbstractPDOSqlDriverTest extends TestCase
{
    public function setup()
    {
        $this->prepare()->connect();
    }

    public function testOptions()
    {
        $expected = array(
            'username' => 'foo',
            'password' => 'bar',
            'options' => null,
            'commands' => null,
            'schema_ttl' => 3,
        );
        $actual = $this->driver->options(array('username' => 'foo', 'password' => 'bar', 'schema_ttl' => 3))->options();

        $this->assertEquals($expected, $actual);
    }

    public function testDsn()
    {
        $this->assertEquals('sqlite::memory:', $this->driver->dsn());
    }

    public function testPdo()
    {
        $this->assertInstanceOf('PDO', $pdo = $this->driver->pdo());

        // second call
        $this->assertSame($pdo, $this->driver->pdo());
    }

    public function testPdoException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Database connection failed!');

        $this->driver->options(array('commands' => 'foo'));
        $this->driver->pdo();
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter($expected, $filter)
    {
        $this->assertEquals($expected, $this->driver->filter($filter));
    }

    public function testFilterEmpty()
    {
        $this->assertEquals(array(), $this->driver->filter(null));
    }

    public function testFilterException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('BETWEEN operator needs an array operand, string given.');

        $this->driver->filter(array('foo ><' => 'str'));
    }

    public function testSchemaDefaultValue()
    {
        $this->assertEquals(true, $this->driver->schemaDefaultValue('true'));
        $this->assertEquals('foo', $this->driver->schemaDefaultValue('foo'));
        $this->assertEquals(1, $this->driver->schemaDefaultValue(1));
    }

    public function testAdhocsExpression()
    {
        $this->assertEquals('', $this->driver->adhocsExpression(array()));
        $this->assertEquals(',(foo) AS `bar`', $this->driver->adhocsExpression(array(new Adhoc('bar', 'foo'))));
    }

    public function testOrderExpression()
    {
        $this->assertEquals('foo', $this->driver->orderExpression('foo'));
        $this->assertEquals('`foo` bar, `baz`', $this->driver->orderExpression(array('foo' => 'bar', 'baz')));
    }

    public function testStringify()
    {
        $schema = new Row('bar');
        $schema->alias = 'baz';
        $fields = 'foo';
        $clause = array('qux' => 'foo');
        $options = array(
            'group' => 'quux',
            'having' => array('quuz' => 'bar'),
            'order' => 'corge',
            'limit' => 1,
            'offset' => 2,
            'comment' => 'grault',
        );

        $actual = $this->driver->stringify($schema, $fields, $clause, $options);
        $expected = array(
            'SELECT foo FROM `bar` AS `baz` WHERE `qux` = :qux GROUP BY quux HAVING `quuz` = :quuz ORDER BY corge LIMIT 1 OFFSET 2'.
            PHP_EOL.' /* grault */',
            array(
                ':qux' => 'foo',
                ':quuz' => 'bar',
            ),
        );

        $this->assertEquals($expected, $actual);

        $actual = $this->driver->stringify($schema, '');
        $expected = array('SELECT * FROM `bar` AS `baz`', array());

        $this->assertEquals($expected, $actual);
    }

    public function testPdoType()
    {
        $fp = fopen(TEST_FIXTURE.'files/foo.txt', 'rb');

        $this->assertEquals(\PDO::PARAM_LOB, $this->driver->pdoType($fp));
        $this->assertEquals(\PDO::PARAM_NULL, $this->driver->pdoType(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->driver->pdoType(true));
        $this->assertEquals(\PDO::PARAM_INT, $this->driver->pdoType(1));
        $this->assertEquals(\PDO::PARAM_STR, $this->driver->pdoType('foo'));
        $this->assertEquals(AbstractPDOSqlDriver::PARAM_FLOAT, $this->driver->pdoType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->driver->pdoType(0.0, null, true));
    }

    public function testPhpValue()
    {
        $fp = fopen(TEST_FIXTURE.'files/foo.txt', 'rb');

        $this->assertEquals((string) $fp, $this->driver->phpValue($fp, \PDO::PARAM_LOB));
        $this->assertEquals(null, $this->driver->phpValue(null));
        $this->assertEquals(null, $this->driver->phpValue('foo', \PDO::PARAM_NULL));
        $this->assertEquals(true, $this->driver->phpValue(1, \PDO::PARAM_BOOL));
        $this->assertEquals(1, $this->driver->phpValue('1', \PDO::PARAM_INT));
        $this->assertEquals('1', $this->driver->phpValue('1', \PDO::PARAM_STR));
        $this->assertEquals('1', $this->driver->phpValue('1', -3));
        $this->assertEquals('0', $this->driver->phpValue(0.0, AbstractPDOSqlDriver::PARAM_FLOAT));
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->driver->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->driver->quotekey('foo.bar'));
    }

    public function testIsQueryEmpty()
    {
        $this->assertTrue($this->driver->isQueryEmpty(''));
        $this->assertTrue($this->driver->isQueryEmpty(' '));
        $this->assertFalse($this->driver->isQueryEmpty('select * table'));
    }

    public function testIsSelectQuery()
    {
        $this->assertTrue($this->driver->isSelectQuery('select * from table'));
        $this->assertTrue($this->driver->isSelectQuery('explain select * from table'));
        $this->assertFalse($this->driver->isSelectQuery('insert into'));
    }

    public function testIsCallQuery()
    {
        $this->assertTrue($this->driver->isCallQuery('call foo()'));
        $this->assertFalse($this->driver->isCallQuery('select * from table'));
    }

    public function testBindQuery()
    {
        $this->buildSchema();

        $query = $this->driver->pdo()->prepare('insert into user (id, username) values (?, ?)');
        $arguments = array(array('1', 'int'), 'foo');

        $this->driver->bindQuery($query, $arguments);

        $this->assertTrue($query->execute());
        $this->assertEquals(array('id' => '1', 'username' => 'foo'), $this->fetch('select id, username from user'));
    }

    public function testPrepareQuery()
    {
        $this->buildSchema();

        $query = $this->driver->prepareQuery('insert into user (id, username) values (?, ?)', true, array(1, 'foo'));

        $this->assertInstanceOf('PDOStatement', $query);
        $this->assertTrue($query->execute());
        $this->assertEquals(array('id' => '1', 'username' => 'foo'), $this->fetch('select id, username from user'));
    }

    /**
     * @dataProvider prepareQueryExceptionProvider
     */
    public function testPrepareQueryException($expected, $query, $trans = false)
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage($expected);

        if ($trans) {
            $this->driver->begin();
        }

        $this->driver->prepareQuery($query);
    }

    public function testAffectedRows()
    {
        $this->assertEquals(0, $this->driver->affectedRows());
    }

    public function testExec()
    {
        $this->connectWithCache()->buildSchema();

        $result = $this->driver->exec('insert into user (id, username) values (?, ?)', array(1, 'foo'));

        $this->assertEquals($result, $this->driver->affectedRows());

        $result = $this->driver->exec('select id,username from user', null, 1);

        $this->assertEquals(array(array('id' => '1', 'username' => 'foo')), $result);

        // second call
        $this->assertEquals($result, $this->driver->exec('select id,username from user', null, 1));

        // exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Query: [23000 - 19] NOT NULL constraint failed: user.username.');

        $this->buildSchema();
        $this->driver->begin();
        $this->driver->exec('insert into user (id, username) values (null, null)');
    }

    public function testExecAll()
    {
        $this->buildSchema();

        $commandsArguments = array(
            'insert into user (id, username) values (?, ?)' => array(1, 'foo'),
            'insert into user (id,username) values (?, ?)' => array(2, 'bar'),
            'select id, username from user',
        );
        $expected = array(
            1,
            1,
            array(
                array('id' => '1', 'username' => 'foo'),
                array('id' => '2', 'username' => 'bar'),
            ),
        );
        $actual = $this->driver->execAll($commandsArguments);

        $this->assertEquals($expected, $actual);
        $this->assertEquals(2, $this->driver->affectedRows());
    }

    public function testExecBatch()
    {
        $this->buildSchema();

        $actual = $this->driver->execBatch('insert into user (id, username) values (?, ?)', array(
            array(1, 'foo'),
            array(2, 'bar'),
        ));
        $expected = array(1, 1);
        $this->assertEquals($expected, $actual);

        $actual = $this->driver->execBatch('select id, username from user where id = ?', array(
            array(1),
            array(2),
        ));
        $expected = array(
            array(array('id' => 1, 'username' => 'foo')),
            array(array('id' => 2, 'username' => 'bar')),
        );
        $this->assertEquals($expected, $actual);

        // exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Query (0): [23000 - 19] NOT NULL constraint failed: user.username.');

        $this->driver->execBatch('insert into user (id, username) values (?, ?)', array(
            array(null, null),
        ));
    }

    public function testSchema()
    {
        $this->connectWithCache()->buildSchema();

        // first call
        $schema = $this->driver->schema('user', array('fields' => 'id,username'), 1);

        $id = new Field('id', null);
        $id->nullable = false;
        $id->pkey = true;
        $id->extras = array(
            'type' => 'INTEGER',
            'pdo_type' => \PDO::PARAM_INT,
        );

        $username = new Field('username', null);
        $username->nullable = false;
        $username->extras = array(
            'type' => 'TEXT',
            'pdo_type' => \PDO::PARAM_STR,
        );

        $expected = new Row('user');
        $expected->setField($id);
        $expected->setField($username);

        $this->assertEquals($expected, $schema);

        // second call, hit cache
        $schema = $this->driver->schema('user', array('fields' => 'id,username'), 1);
        $this->assertEquals($expected, $schema);

        // expect exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Unknown table: foo.');
        $this->driver->schema('foo');
    }

    public function testFind()
    {
        $this->buildSchema()->initUser();

        $schema = $this->driver->schema('user');
        $result = $this->driver->find($schema);

        $this->assertCount(3, $result);
        $this->assertEquals(array(
            'id' => 1,
            'username' => 'foo',
            'password' => '',
            'active' => 1,
        ), $result[0]->toArray());
        $this->assertEquals(array(
            'id' => 2,
            'username' => 'bar',
            'password' => '',
            'active' => 1,
        ), $result[1]->toArray());
        $this->assertEquals(array(
            'id' => 3,
            'username' => 'baz',
            'password' => '',
            'active' => 1,
        ), $result[2]->toArray());
    }

    public function testFirst()
    {
        $this->buildSchema()->initUser();

        $schema = $this->driver->schema('user')->set('foo', 'id + 1');
        $result = $this->driver->first($schema, array('id' => 1));

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('foo', $result['username']);
        $this->assertEquals(2, $result['foo']);

        $this->assertNull($this->driver->first($schema, array('id' => 4)));
    }

    public function testExists()
    {
        $this->buildSchema();

        $this->assertTrue($this->driver->exists('user'));
        $this->assertTrue($this->driver->exists('profile'));
        $this->assertFalse($this->driver->exists('foo'));
    }

    public function testCount()
    {
        $this->buildSchema()->initUser();

        $schema = $this->driver->schema('user');

        $this->assertEquals(3, $this->driver->count($schema));
        $this->assertEquals(3, $this->driver->count($schema, null, array('group' => 'id')));
        $this->assertEquals(2, $this->driver->count($schema, array('id <=' => 2)));
        $this->assertEquals(1, $this->driver->count($schema, array('id <' => 2), array('limit' => 1)));
    }

    /**
     * @dataProvider insertProvider
     */
    public function testInsert($expected, $table, $record, $exception = null)
    {
        $this->buildSchema();

        $schema = $this->driver->schema($table);
        $schema->fromArray($record);

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->driver->insert($schema);

            return;
        }

        $result = $this->driver->insert($schema);

        if (null === $expected) {
            $this->assertNull($result);
        } else {
            $this->assertNotNull($result);

            $actual = array_intersect_key($result->toArray(), array_flip($result->getKeys()));
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($expected, $table, $record, $initial, $exception = null)
    {
        $this->buildSchema()->initUser();

        // fetch only id and username
        $schema = $this->driver->schema($table);
        $schema->fromArray($initial)->commit()->fromArray($record);

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->driver->update($schema);

            return;
        }

        $this->assertEquals($expected, $this->driver->update($schema));
    }

    /**
     * @dataProvider deleteProvider
     */
    public function testDelete($expected, $table, $record)
    {
        $this->buildSchema()->initUser();

        $schema = $this->driver->schema($table);
        $schema->fromArray($record)->commit();

        $this->assertEquals($expected, $this->driver->delete($schema));
    }

    /**
     * @dataProvider deleteByClauseProvider
     */
    public function testDeleteByClause($expected, $table, $clause)
    {
        $this->buildSchema()->initUser();

        $schema = $this->driver->schema($table);

        $this->assertEquals($expected, $this->driver->deleteByClause($schema, $clause));
    }

    public function testIsSupportTransaction()
    {
        $this->assertTrue($this->driver->isSupportTransaction());
    }

    public function testInTransaction()
    {
        $this->assertFalse($this->driver->inTransaction());
    }

    public function testBegin()
    {
        $this->assertTrue($this->driver->begin());
        $this->assertTrue($this->driver->inTransaction());
        $this->assertTrue($this->driver->rollback());
        $this->assertFalse($this->driver->inTransaction());
    }

    public function testCommit()
    {
        $this->assertTrue($this->driver->begin());
        $this->assertTrue($this->driver->inTransaction());
        $this->assertTrue($this->driver->commit());
        $this->assertFalse($this->driver->inTransaction());
    }

    public function testRollback()
    {
        $this->assertTrue($this->driver->begin());
        $this->assertTrue($this->driver->inTransaction());
        $this->assertTrue($this->driver->rollback());
        $this->assertFalse($this->driver->inTransaction());
    }

    /**
     * @dataProvider paginateProvider
     */
    public function testPaginate($expected, $page, $limit = 10, $clause = null)
    {
        $this->buildSchema()->initUser();

        $row = $this->driver->schema('user');
        $actual = $this->driver->paginate($row, $page, $limit, $clause);
        $actualExpected = array_intersect_key($actual, $expected);
        $actualExpected['subset'] = count($actual['subset']);

        $this->assertEquals($expected, $actualExpected);
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

    public function prepareQueryExceptionProvider()
    {
        return array(
            array('Cannot prepare an empty query.', ''),
            array('PDO: [HY000 - 1] incomplete input.', 'create', true),
        );
    }

    public function insertProvider()
    {
        return array(
            array(array('id' => '1'), 'user', array('username' => 'foo')),
            array(array(), 'nokey', array('name' => 'foo', 'info' => 'bar')),
            array(null, 'user', array()),
            array('Field cannot be null: active.', 'user', array('active' => null), 'LogicException'),
        );
    }

    public function updateProvider()
    {
        return array(
            array(true, 'user', array('username' => 'foobar'), array('id' => 1, 'username' => 'foo')),
            array(false, 'user', array('username' => 'foo'), array('id' => 1, 'username' => 'foo')),
            array(false, 'user', array(), array('id' => 1, 'username' => 'foo')),
            array('Field cannot be null: username.', 'user', array('username' => null), array('id' => 1, 'username' => 'foo'), 'LogicException'),
        );
    }

    public function deleteProvider()
    {
        return array(
            array(true, 'user', array('id' => 1)),
            array(true, 'user', array('id' => 3)),
            array(false, 'user', array()),
        );
    }

    public function deleteByClauseProvider()
    {
        return array(
            array(1, 'user', array('id' => 1)),
            array(3, 'user', null),
        );
    }

    public function paginateProvider()
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
            ), 1, 10, array('id' => 1)),
            array(array(
                'subset' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, 1),
            array(array(
                'subset' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 2,
                'start' => 2,
                'end' => 2,
            ), 2, 1),
        );
    }

    protected function connect($cache = null)
    {
        if (!$cache) {
            $cache = new NoCache();
        }

        $this->cache = $cache;
        $this->driver = $this->getMockForAbstractClass('Fal\\Stick\\Database\\Driver\\AbstractPDOSqlDriver', array(
            $this->cache,
            $this->logger,
        ));

        $this->driver
            ->expects($this->any())
            ->method('createDsn')
            ->will($this->returnValue('sqlite::memory:'))
        ;

        $this->driver
            ->expects($this->any())
            ->method('createSchema')
            ->will($this->returnCallback(function ($table, $fields) {
                $schema = new Row($table);
                $command = 'PRAGMA table_info('.$table.')';
                $query = $this->driver->pdo()->query($command);

                foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $field) {
                    $name = $field['name'];

                    if ($fields && !in_array($name, $fields)) {
                        continue;
                    }

                    $item = new Field($name, null, $this->driver->schemaDefaultValue($field['dflt_value']));
                    $item->nullable = 0 == $field['notnull'];
                    $item->pkey = 1 == $field['pk'];
                    $item->extras = array(
                        'type' => $field['type'],
                        'pdo_type' => $this->driver->pdoType(null, $field['type']),
                    );

                    $schema->setField($item);
                }

                return $schema;
            }))
        ;

        return $this;
    }
}
