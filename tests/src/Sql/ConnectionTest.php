<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private $expected = array(
        'r1' => array('id' => '1', 'username' => 'foo', 'password' => null, 'active' => '1'),
        'r2' => array('id' => '2', 'username' => 'bar', 'password' => null, 'active' => '1'),
        'r3' => array('id' => '3', 'username' => 'baz', 'password' => null, 'active' => '1'),
    );
    private $conn;

    public function setUp()
    {
        $this->build();
    }

    private function build($cacheDsn = null)
    {
        $app = App::create()->mset(array(
            'TEMP' => TEMP,
            'CACHE' => $cacheDsn,
            'LOG' => 'logs-connection/',
            'THRESHOLD' => App::LOG_LEVEL_DEBUG,
        ))->logClear()->cacheReset();

        $this->conn = new Connection($app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
    }

    private function filldb()
    {
        $this->conn->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
    }

    private function enableDebug()
    {
        $this->conn->setOptions(array('debug' => true) + $this->conn->getOptions());
    }

    private function changeDriver($driver)
    {
        $ref = new \ReflectionProperty($this->conn, 'driver');
        $ref->setAccessible(true);
        $ref->setValue($this->conn, $driver);
    }

    public function testGetOptions()
    {
        $options = $this->conn->getOptions();
        $this->assertContains('sqlite::memory:', $options);
    }

    public function testSetOptions()
    {
        $options = $this->conn->setOptions(array(
            'dsn' => 'mysql:host=localhost;dbname=test_stick',
            'username' => 'root',
            'password' => 'pass',
        ))->getOptions();

        $this->assertContains('root', $options);
        $this->assertContains('pass', $options);
        $this->assertContains('mysql:host=localhost;dbname=test_stick', $options);
        $this->assertEquals('test_stick', $this->conn->getDbName());
    }

    public function testGetDbName()
    {
        $this->assertEquals('', $this->conn->getDbName());
    }

    public function testPdo()
    {
        $pdo = $this->conn->pdo();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertSame($pdo, $this->conn->pdo());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid database configuration.
     */
    public function testPdoException()
    {
        $this->conn->setOptions(array(
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ));

        $this->conn->pdo();
    }

    public function testExists()
    {
        $this->assertTrue($this->conn->exists('user'));
        $this->assertTrue($this->conn->exists('profile'));
        $this->assertFalse($this->conn->exists('foo'));
    }

    public function testExistsCache()
    {
        $this->build('fallback');

        // with cache
        $this->assertTrue($this->conn->exists('user', 5));
        $this->assertTrue($this->conn->exists('user', 5));
    }

    public function testGetDriver()
    {
        $this->assertEquals('sqlite', $this->conn->getDriver());
    }

    public function testGetVersion()
    {
        $this->assertNotEmpty($this->conn->getVersion());
    }

    public function testGetLogLevel()
    {
        $this->assertEquals(App::LOG_LEVEL_INFO, $this->conn->getLogLevel());
    }

    public function testSetLogLevel()
    {
        $this->assertEquals('foo', $this->conn->setLogLevel('foo')->getLogLevel());
    }

    public function testSchema()
    {
        $schema = $this->conn->schema('user');
        $expected = array(
            'id' => array(
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => null,
                'nullable' => false,
                'pkey' => true,
            ),
            'username' => array(
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => false,
                'pkey' => false,
            ),
            'password' => array(
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => true,
                'pkey' => false,
            ),
            'active' => array(
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => 1,
                'nullable' => false,
                'pkey' => false,
            ),
        );
        $this->assertEquals($expected, $schema);

        // second schema
        $schema = $this->conn->schema('profile');
        $this->assertEquals(array('id', 'fullname', 'user_id'), array_keys($schema));
    }

    public function testSchemaWithFields()
    {
        $schema = $this->conn->schema('user', array('username', 'password'));
        $this->assertCount(2, $schema);
    }

    public function testSchemaCache()
    {
        $this->build('fallback');

        $init = $this->conn->schema('user', null, 5);
        $cached = $this->conn->schema('user', null, 5);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Driver unknown is not supported
     */
    public function testSchemaException()
    {
        $this->changeDriver('unknown');
        $this->conn->schema('foo.user');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Table "noschema" contains no defined schema
     */
    public function testSchemaException2()
    {
        $this->conn->schema('noschema');
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->conn->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->conn->quotekey('foo.bar'));

        $this->changeDriver('odbc');
        $this->assertEquals('[foo]', $this->conn->quotekey('foo'));

        $this->changeDriver('unknown');
        $this->assertEquals('foo.bar', $this->conn->quotekey('foo.bar', true));
    }

    public function testBegin()
    {
        $this->assertTrue($this->conn->begin());
    }

    public function testCommit()
    {
        $this->assertTrue($this->conn->begin());
        $this->assertTrue($this->conn->commit());
    }

    public function testRollback()
    {
        $this->assertTrue($this->conn->begin());
        $this->assertTrue($this->conn->rollBack());
    }

    public function testIsTrans()
    {
        $this->assertFalse($this->conn->isTrans());
    }

    public function execProvider()
    {
        extract($this->expected);

        return array(
            array(
                'select * from user',
                null,
                array($r1, $r2, $r3),
            ),
            array(
                'select * from user where id = ?',
                array(1),
                array($r1),
            ),
            array(
                'select * from user where id = :id',
                array(':id' => 1),
                array($r1),
            ),
            array(
                'select * from user where id = ?',
                array(1),
                array($r1),
            ),
            array(
                'insert into user (username) values ("bleh")',
                null,
                1,
            ),
            array(
                'insert into user (username) values (?)',
                array('one'),
                1,
            ),
            array(
                array(
                    'select * from user where id = ?',
                    'select * from user where id = ?',
                ),
                1,
                array(array($r1), array($r1)),
            ),
            array(
                '',
                null,
                null,
            ),
        );
    }

    /** @dataProvider execProvider */
    public function testExec($sql, $params, $expected)
    {
        $this->filldb();

        $this->assertEquals($expected, $this->conn->exec($sql, $params));
    }

    public function testExecCache()
    {
        $this->build('auto');
        $this->filldb();

        $sql = 'select * from user limit 1';
        $this->assertEquals(array($this->expected['r1']), $this->conn->exec($sql, null, 5));
        $this->assertEquals(array($this->expected['r1']), $this->conn->exec($sql, null, 5));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage PDO: no such table: muser
     */
    public function testExecException()
    {
        $this->conn->begin();
        $this->conn->exec('select * from muser');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage PDOStatement: bind or column index out of range
     */
    public function testExecException2()
    {
        $this->conn->begin();
        $this->conn->exec('select * from user where id = ?', array(1, 2));
    }

    public function buildFilterProvider()
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

        return $filter;
    }

    /** @dataProvider buildFilterProvider */
    public function testBuildFilter($expected, $filter)
    {
        $this->assertEquals($expected, $this->conn->buildFilter($filter));
    }

    public function testBuildFilterEmpty()
    {
        $this->assertEquals(array(), $this->conn->buildFilter(null));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage BETWEEN operator needs an array operand, string given.
     */
    public function testBuildFilterException1()
    {
        $this->conn->buildFilter(array('foo ><' => 'str'));
    }

    public function testPdoType()
    {
        $this->assertEquals(\PDO::PARAM_NULL, $this->conn->pdoType(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->conn->pdoType(true));
        $this->assertEquals(\PDO::PARAM_INT, $this->conn->pdoType(1));
        $this->assertEquals(Connection::PARAM_FLOAT, $this->conn->pdoType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->pdoType('foo'));
        $fp = fopen(FIXTURE.'files/long.txt', 'rb');
        $this->assertEquals(\PDO::PARAM_LOB, $this->conn->pdoType($fp));
    }

    public function testRealPdoType()
    {
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->realPdoType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->realPdoType(null, Connection::PARAM_FLOAT));
    }

    public function testPhpValue()
    {
        $this->assertEquals(null, $this->conn->phpValue(\PDO::PARAM_NULL, 'foo'));
        $this->assertEquals('0.0', $this->conn->phpValue(Connection::PARAM_FLOAT, 0.0));
        $this->assertEquals(true, $this->conn->phpValue(\PDO::PARAM_BOOL, 1));
        $this->assertEquals(1, $this->conn->phpValue(\PDO::PARAM_INT, '1'));
        $this->assertEquals('1', $this->conn->phpValue(\PDO::PARAM_STR, '1'));
        $this->assertEquals('1', $this->conn->phpValue('foo', '1'));
        $fp = fopen(FIXTURE.'files/long.txt', 'rb');
        $this->assertEquals((binary) $fp, $this->conn->phpValue(\PDO::PARAM_LOB, $fp));
    }

    public function testQuote()
    {
        $this->assertEquals("'foo'", $this->conn->quote('foo'));
        $this->changeDriver(Connection::DB_ODBC);
        $this->assertEquals('foo', $this->conn->quote('foo'));
    }

    public function testGetRows()
    {
        $this->assertEquals(0, $this->conn->getRows());
        $out = $this->conn->exec('insert into user (username) values ("foo")');
        $this->assertEquals(1, $out);
        $this->assertEquals(1, $this->conn->getRows());
    }

    public function testSafeRollback()
    {
        $this->conn->begin();
        $this->assertFalse($this->conn->safeRollback()->isTrans());
    }

    public function testSafeBegin()
    {
        $this->assertTrue($this->conn->safeBegin()->isTrans());
        $this->conn->rollBack();
    }
}
