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

namespace Fal\Stick\Test\Db\Pdo;

use Fal\Stick\Fw;
use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;

class DbTest extends MyTestCase
{
    private $fw;

    public function setup(): void
    {
        $this->fw = new Fw();
    }

    protected function createInstance()
    {
        return new Db($this->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
        ));
    }

    public function testPdo()
    {
        $pdo = $this->db->pdo();
        // second call, same connection
        $this->assertSame($pdo, $this->db->pdo());

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Unable to connect database!');
        $db = new Db($this->fw, new SqliteDriver(), 'sqlite');
        $db->pdo();
    }

    public function testDriver()
    {
        $this->assertEquals('sqlite', $this->db->driver());
    }

    public function testVersion()
    {
        $this->assertNotEmpty($this->db->version());
    }

    public function testDbname()
    {
        $this->assertEquals(':memory:', $this->db->dbname());
    }

    public function testTrans()
    {
        $this->assertFalse($this->db->trans());
    }

    public function testRows()
    {
        $this->assertEquals(0, $this->db->rows());
    }

    public function testBegin()
    {
        $this->assertTrue($this->db->begin());
    }

    public function testCommit()
    {
        $this->assertTrue($this->db->begin());
        $this->assertTrue($this->db->commit());
    }

    public function testRollback()
    {
        $this->assertTrue($this->db->begin());
        $this->assertTrue($this->db->rollback());
    }

    public function testLog()
    {
        $this->assertSame($this->db, $this->db->log(false));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\DbProvider::prepare
     */
    public function testPrepare($expected, $sql, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->db->begin();
            $this->db->prepare($sql);

            return;
        }

        $this->assertInstanceOf('PDOStatement', $this->db->prepare($sql));
    }

    public function testBindValues()
    {
        $this->expectOutputString(<<<OUTPUT
SQL: [51] insert into user (username, password) values (?, ?)
Params:  2
Key: Position #0:
paramno=0
name=[0] ""
is_param=1
param_type=2
Key: Position #1:
paramno=1
name=[0] ""
is_param=1
param_type=2

OUTPUT
);
        $query = $this->db->prepare('insert into user (username, password) values (?, ?)');
        $this->db->bindValues($query, array(
            array('foo', \PDO::PARAM_STR),
            'bar',
        ));

        $query->debugDumpParams();
    }

    public function testExec()
    {
        $this->fw->set('CACHE', 'filesystem='.$this->tmp('/'));

        $result1 = $this->db->exec('insert into user (username, password) values (?, ?)', array(
            array('foo', \PDO::PARAM_STR), 'foo',
        ));
        $result2 = $this->db->exec('insert into user (username, password) values (:username, :password)', array(
            ':username' => 'bar', ':password' => 'bar',
        ));
        $result3 = $this->db->exec('select username, password from user', null, 1);
        $expected = array(
            array('username' => 'foo', 'password' => 'foo'),
            array('username' => 'bar', 'password' => 'bar'),
        );

        $this->assertEquals(1, $result1);
        $this->assertEquals(1, $result2);
        $this->assertEquals($expected, $result3);
        // second call hit cache
        $this->assertEquals($expected, $this->db->exec('select username, password from user', null, 1));

        $this->fw->creset();

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Query: [23000 - 19] NOT NULL constraint failed: user.username.');

        $this->db->begin();
        $this->db->exec('insert into user (id, username) values (null, null)');
    }

    public function testMexec()
    {
        $commands = array(
            'insert into user (username, password) values (?, ?)' => array('foo', 'foo'),
            'insert into user (username, password) values (:username, :password)' => array(':username' => 'bar', ':password' => 'bar'),
            'select username, password from user',
        );
        $expected = array(
            1,
            1,
            array(
                array('username' => 'foo', 'password' => 'foo'),
                array('username' => 'bar', 'password' => 'bar'),
            ),
        );

        $this->assertEquals($expected, $this->db->mexec($commands));
    }

    public function testExists()
    {
        $this->assertTrue($this->db->exists('user'));
        $this->assertFalse($this->db->exists('foo'));
    }

    public function testSchema()
    {
        $this->fw->set('CACHE', 'filesystem='.$this->tmp('/'));
        $expected = array(
            'id' => array(
                'default' => null,
                'nullable' => false,
                'pkey' => true,
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'data_type' => 'INTEGER',
                'constraint' => null,
            ),
            'username' => array(
                'default' => null,
                'nullable' => false,
                'pkey' => false,
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'data_type' => 'TEXT',
                'constraint' => null,
            ),
            'active' => array(
                'default' => 1,
                'nullable' => false,
                'pkey' => false,
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'data_type' => 'INTEGER',
                'constraint' => null,
            ),
        );
        $result1 = $this->db->schema('user', 'id,username,active', 1);
        // second call hit cache
        $result2 = $this->db->schema('user', 'id,username,active', 1);

        $this->assertEquals($expected, $result1->getSchema());
        $this->assertEquals($expected, $result2->getSchema());

        $this->fw->creset();
    }
}
