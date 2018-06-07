<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Sql;

use Fal\Stick\Cache;
use Fal\Stick\Logger;
use Fal\Stick\Sql\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private $expected = [
        'r1' => ['id' => '1', 'username' => 'foo', 'password' => null, 'active' => '1'],
        'r2' => ['id' => '2', 'username' => 'bar', 'password' => null, 'active' => '1'],
        'r3' => ['id' => '3', 'username' => 'baz', 'password' => null, 'active' => '1'],
    ];
    private $conn;

    public function setUp()
    {
        $this->build();
    }

    private function build(string $dsn = '')
    {
        $cache = new Cache($dsn, 'test', TEMP.'cache/');
        $cache->reset();

        $logger = new Logger(TEMP.'conlog/', Logger::LEVEL_DEBUG);
        $logger->clear();

        $this->conn = new Connection($cache, $logger, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT NULL,
    `password` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE `profile` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `fullname` TEXT NOT NULL,
    `user_id` INTEGER NOT NULL
);
SQL1
        ]);
    }

    private function filldb()
    {
        $this->conn->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
    }

    private function enableDebug()
    {
        $this->conn->setOptions(['debug' => true] + $this->conn->getOptions());
    }

    private function changeDriver(string $driver)
    {
        $ref = new \ReflectionProperty($this->conn, 'driver');
        $ref->setAccessible(true);
        $ref->setValue($this->conn, $driver);
    }

    public function testGetCache()
    {
        $this->assertInstanceof(Cache::class, $this->conn->getCache());
    }

    public function testGetLogger()
    {
        $this->conn->exec('select * from user');
        $this->conn->exec('insert into user (username) values (?)', ['quux']);
        $this->conn->exec('insert into user (id,username) values (?,?)', [['5', \PDO::PARAM_INT], 'bleh']);

        $logs = $this->conn->getLogger()->files();
        $this->assertCount(1, $logs);

        $log = file_get_contents($logs[0]);
        $this->assertContains('select * from user', $log);
        $this->assertContains("insert into user (username) values ('quux')", $log);
        $this->assertContains("insert into user (id,username) values (5,'bleh')", $log);
    }

    public function testGetOptions()
    {
        $options = $this->conn->getOptions();
        $this->assertContains('sqlite::memory:', $options);
        $this->assertContains('sqlite', $options);
    }

    public function testSetOptions()
    {
        $options = $this->conn->setOptions([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ])->getOptions();

        $this->assertContains('mysql', $options);
        $this->assertContains('test_stick', $options);
        $this->assertContains('127.0.0.1', $options);
        $this->assertContains(3306, $options);
        $this->assertContains('root', $options);
        $this->assertContains('pass', $options);
        $this->assertContains('mysql:host=127.0.0.1;port=3306;dbname=test_stick', $options);
    }

    public function testSetOptionDsn()
    {
        $this->conn->setOptions(['dsn' => 'mysql:host=localhost;dbname=foo']);
        $this->assertContains('foo', $this->conn->getOptions());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage There is no logic for unknown DSN creation, please provide a valid one
     */
    public function testSetOptionsException1()
    {
        $this->conn->setOptions([]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid mysql driver configuration
     */
    public function testSetOptionsException2()
    {
        $this->conn->setOptions([
            'driver' => 'mysql',
        ]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid sqlite driver configuration
     */
    public function testSetOptionsException3()
    {
        $this->conn->setOptions([
            'driver' => 'sqlite',
        ]);
    }

    public function testPdo()
    {
        $this->conn->setOptions([
            'driver' => 'sqlite',
            'location' => ':memory:',
        ]);

        $pdo = $this->conn->pdo();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertEquals($pdo, $this->conn->pdo());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid database configuration
     */
    public function testPdoException1()
    {
        $this->conn->setOptions([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ]);

        $this->conn->pdo();
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessageRegExp /^SQLSTATE\[HY000\]/
     */
    public function testPdoException2()
    {
        $this->conn->setOptions([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
            'debug' => true,
        ]);

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
        $this->build('auto');

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
        $this->assertEquals(Logger::LEVEL_INFO, $this->conn->getLogLevel());
    }

    public function testSetLogLevel()
    {
        $this->assertEquals('foo', $this->conn->setLogLevel('foo')->getLogLevel());
    }

    public function testSchema()
    {
        $schema = $this->conn->schema('user');
        $expected = [
            'id' => [
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => null,
                'nullable' => false,
                'pkey' => true,
            ],
            'username' => [
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => false,
                'pkey' => false,
            ],
            'password' => [
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => true,
                'pkey' => false,
            ],
            'active' => [
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => 1,
                'nullable' => false,
                'pkey' => false,
            ],
        ];
        $this->assertEquals($expected, $schema);

        // second schema
        $schema = $this->conn->schema('profile');
        $this->assertEquals(['id', 'fullname', 'user_id'], array_keys($schema));
    }

    public function testSchemaWithFields()
    {
        $schema = $this->conn->schema('user', ['username', 'password']);
        $this->assertCount(2, $schema);
    }

    public function testSchemaCache()
    {
        $this->build('auto');

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

    public function testGetQuote()
    {
        $this->assertEquals(['`', '`'], $this->conn->getQuote());

        $this->changeDriver('odbc');
        $this->assertEquals(['[', ']'], $this->conn->getQuote());
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->conn->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->conn->quotekey('foo.bar'));

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

        return [
            [
                'select * from user',
                null,
                [$r1, $r2, $r3],
            ],
            [
                'select * from user where id = ?',
                [1],
                [$r1],
            ],
            [
                'select * from user where id = :id',
                [':id' => 1],
                [$r1],
            ],
            [
                'select * from user where id = ?',
                [1],
                [$r1],
            ],
            [
                'insert into user (username) values ("bleh")',
                null,
                1,
            ],
            [
                'insert into user (username) values (?)',
                ['one'],
                1,
            ],
            [
                [
                    'select * from user where id = ?',
                    'select * from user where id = ?',
                ],
                1,
                [[$r1], [$r1]],
            ],
            [
                '',
                null,
                null,
            ],
        ];
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
        $this->assertEquals([$this->expected['r1']], $this->conn->exec($sql, null, 5));
        $this->assertEquals([$this->expected['r1']], $this->conn->exec($sql, null, 5));
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
        $this->conn->exec('select * from user where id = ?', [1, 2]);
    }

    public function buildFilterProvider()
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

        return $filter;
    }

    /** @dataProvider buildFilterProvider */
    public function testBuildFilter($expected, $filter)
    {
        $this->assertEquals($expected, $this->conn->buildFilter($filter));
    }

    public function testBuildFilterEmpty()
    {
        $this->assertEquals([], $this->conn->buildFilter(null));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage BETWEEN operator needs an array operand, string given
     */
    public function testBuildFilterException1()
    {
        $this->conn->buildFilter(['foo ><' => 'str']);
    }

    public function testGetName()
    {
        $this->assertEquals('', $this->conn->getName());
    }

    public function testPdoType()
    {
        $this->assertEquals(\PDO::PARAM_NULL, $this->conn->pdoType(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->conn->pdoType(true));
        $this->assertEquals(\PDO::PARAM_INT, $this->conn->pdoType(1));
        $this->assertEquals(Connection::PARAM_FLOAT, $this->conn->pdoType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->conn->pdoType('foo'));
        $fp = fopen(FIXTURE.'long.txt', 'rb');
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
        $fp = fopen(FIXTURE.'long.txt', 'rb');
        $this->assertEquals((binary) $fp, $this->conn->phpValue(\PDO::PARAM_LOB, $fp));
    }

    public function testQuote()
    {
        $this->assertEquals("'foo'", $this->conn->quote('foo'));
        $this->changeDriver(Connection::DB_ODBC);
        $this->assertEquals("'foo'", $this->conn->quote('foo'));
    }

    public function testGetRows()
    {
        $this->assertEquals(0, $this->conn->getRows());
        $out = $this->conn->exec('insert into user (username) values ("foo")');
        $this->assertEquals(1, $out);
        $this->assertEquals(1, $this->conn->getRows());
    }
}
