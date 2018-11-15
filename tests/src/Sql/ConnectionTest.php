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
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private $conn;
    private $fw;

    public function setUp()
    {
        $this->fw = new Fw();
        $this->build();
    }

    public function testPdoType()
    {
        $fp = fopen(FIXTURE.'files/long.txt', 'rb');

        $this->assertEquals(\PDO::PARAM_LOB, $this->conn->pdoType($fp));
        $this->assertEquals(\PDO::PARAM_NULL, $this->conn->pdoType(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->conn->pdoType(true));
        $this->assertEquals(\PDO::PARAM_INT, $this->conn->pdoType(1));
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->pdoType('foo'));
        $this->assertEquals(Connection::PARAM_FLOAT, $this->conn->pdoType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->pdoType(0.0, null, true));
    }

    public function testPhpValue()
    {
        $fp = fopen(FIXTURE.'files/long.txt', 'rb');

        $this->assertEquals((binary) $fp, $this->conn->phpValue($fp, \PDO::PARAM_LOB));
        $this->assertEquals(null, $this->conn->phpValue('foo', \PDO::PARAM_NULL));
        $this->assertEquals(true, $this->conn->phpValue(1, \PDO::PARAM_BOOL));
        $this->assertEquals(1, $this->conn->phpValue('1', \PDO::PARAM_INT));
        $this->assertEquals('1', $this->conn->phpValue('1', \PDO::PARAM_STR));
        $this->assertEquals('1', $this->conn->phpValue('1', -3));
        $this->assertEquals('0', $this->conn->phpValue(0.0, Connection::PARAM_FLOAT));
    }

    public function testIsQueryEmpty()
    {
        $this->assertTrue($this->conn->isQueryEmpty(''));
        $this->assertTrue($this->conn->isQueryEmpty(' '));
        $this->assertFalse($this->conn->isQueryEmpty('select * table'));
    }

    public function testIsQueryFetchable()
    {
        $this->assertTrue($this->conn->isQueryFetchable('select * from table'));
        $this->assertTrue($this->conn->isQueryFetchable('call foo()'));
        $this->assertFalse($this->conn->isQueryFetchable('insert into'));
    }

    public function testGetDbName()
    {
        $this->build('mysql:host=localhost;dbname=test_stick');

        $this->assertEquals('test_stick', $this->conn->getDbName());
    }

    public function testGetPdo()
    {
        $this->assertInstanceOf('PDO', $this->conn->getPdo());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Database connection failed!
     */
    public function testGetPdoException()
    {
        $this->build(null, null, null, array('invalid query;'));
        $this->conn->getPdo();
    }

    public function testKey()
    {
        $this->assertEquals('`foo`', $this->conn->key('foo'));
        $this->assertEquals('`foo`.`bar`', $this->conn->key('foo.bar'));
    }

    public function testGetDriverName()
    {
        $this->assertEquals('sqlite', $this->conn->getDriverName());
    }

    public function testGetServerVersion()
    {
        $this->assertNotEmpty($this->conn->getServerVersion());
    }

    public function testGetAttribute()
    {
        $this->assertEquals('sqlite', $this->conn->getAttribute('driver_name'));
    }

    public function testIsTableExists()
    {
        $this->assertTrue($this->conn->isTableExists('user'));
        $this->assertTrue($this->conn->isTableExists('profile'));
        $this->assertFalse($this->conn->isTableExists('foo'));
    }

    public function testGetTableSchema()
    {
        $schema = $this->conn->getTableSchema('db.user');
        $expected = array(
            'id' => array(
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => null,
                'nullable' => false,
                'pkey' => true,
                'name' => 'id',
            ),
            'username' => array(
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => false,
                'pkey' => false,
                'name' => 'username',
            ),
            'password' => array(
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => true,
                'pkey' => false,
                'name' => 'password',
            ),
            'active' => array(
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => 1,
                'nullable' => false,
                'pkey' => false,
                'name' => 'active',
            ),
        );

        $this->assertEquals($expected, $schema);
    }

    public function testGetTableSchemaWithFields()
    {
        $schema = $this->conn->getTableSchema('user', 'username, password');

        $this->assertCount(2, $schema);
    }

    public function testGetTableSchemaCache()
    {
        $this->fw['CACHE'] = 'fallback';
        $this->fw->cacheReset();

        $init = $this->conn->getTableSchema('user', null, 1);
        $cached = $this->conn->getTableSchema('user', null, 1);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Driver unknown is not supported
     */
    public function testGetTableSchemaException()
    {
        $this->changeDriver('unknown');
        $this->conn->getTableSchema('user');
    }

    public function testTrans()
    {
        $this->assertFalse($this->conn->trans());
    }

    public function testBegin()
    {
        $this->assertTrue($this->conn->begin()->trans());
        $this->conn->commit();
    }

    public function testCommit()
    {
        $this->conn->begin();
        $this->assertFalse($this->conn->commit()->trans());
    }

    public function testRollback()
    {
        $this->conn->begin();
        $this->assertFalse($this->conn->rollback()->trans());
    }

    public function testPrepare()
    {
        $this->filldb();

        $stmt = $this->conn->prepare('select * from user where id = ?', array(1));
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $expected = array(
            'id' => 1,
            'username' => 'foo',
            'password' => null,
            'active' => 1,
        );

        $this->assertEquals($expected, $row);
    }

    public function testExec()
    {
        $this->filldb();
        $rows = $this->conn->exec('SELECT * FROM user');
        $inserted = $this->conn->exec('INSERT INTO user (username) values (?)', array(
            'qux',
        ));

        $this->assertCount(3, $rows);
        $this->assertEquals(1, $inserted);
    }

    public function testExecCache()
    {
        $this->fw['CACHE'] = 'fallback';
        $this->fw->cacheReset();
        $this->filldb();

        $init = $this->conn->exec('select * from user', null, 1);
        $cached = $this->conn->exec('select * from user', null, 1);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot execute an empty query!
     */
    public function testExecException()
    {
        $this->conn->exec(' ');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage PDO: near "select": syntax error.
     */
    public function testExecException2()
    {
        $this->conn->exec('select');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage PDOStatement: NOT NULL constraint failed: user.username.
     */
    public function testExecException3()
    {
        $this->conn->exec('INSERT INTO user (username) VALUES (NULL)');
    }

    public function testExecAll()
    {
        $commands = array(
            'select * from user',
            'insert into user (username) values (?)' => array('foo'),
            'select * from user where id = ?' => array(1),
        );
        $result = $this->conn->execAll($commands);

        $this->assertCount(0, $result[0]);
        $this->assertEquals(1, $result[1]);
        $this->assertCount(1, $result[2]);
    }

    private function build($dsn = null, $username = null, $password = null, $commands = null)
    {
        $this->conn = new Connection($this->fw, ...array(
            $dsn ?? 'sqlite::memory:',
            $username,
            $password,
            $commands ?? array(file_get_contents(FIXTURE.'files/schema.sql')),
        ));
    }

    private function filldb()
    {
        $this->conn->getPdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
    }

    private function enableLog()
    {
        $this->fw['LOG'] = TEMP.'log-conn/';
        $this->fw['THRESHOLD'] = 'debug';
    }

    private function changeDriver($driver)
    {
        $ref = new \ReflectionProperty($this->conn, 'attributes');
        $ref->setAccessible(true);
        $val = $ref->getValue($this->conn);
        $val['DRIVER_NAME'] = $driver;
        $ref->setValue($this->conn, $val);
    }
}
