<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Database;

use function Fal\Stick\split;
use Fal\Stick\Cache;
use Fal\Stick\Database\Sql;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
{
    private $expected = [
        'r1' => ['id'=>'1','first_name'=>'foo','last_name'=>null,'active'=>'1'],
        'r2' => ['id'=>'2','first_name'=>'bar','last_name'=>null,'active'=>'1'],
        'r3' => ['id'=>'3','first_name'=>'baz','last_name'=>null,'active'=>'1'],
    ];
    private $sql;

    public function setUp()
    {
        $this->build();
    }

    public function tearDown()
    {
        error_clear_last();
    }

    private function build(string $dsn = '', array $option = [])
    {
        $cache = new Cache($dsn, 'test', TEMP . 'cache/');
        $cache->reset();

        $this->sql = new Sql($cache, $option + [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `first_name` TEXT NOT null,
    `last_name` TEXT null DEFAULT null,
    `active` INTEGER NOT null DEFAULT 1
);
CREATE TABLE `user2` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `name` TEXT NOT null
)
SQL1
        ]);
    }

    private function filldb()
    {
        $this->sql->pdo()->exec('insert into user (first_name) values ("foo"), ("bar"), ("baz")');
    }

    private function enableDebug()
    {
        $this->sql->setOption(['debug'=>true] + $this->sql->getOption());
    }

    private function enableLog()
    {
        $this->sql->setOption(['log'=>true] + $this->sql->getOption());
    }

    private function changeDriver(string $driver)
    {
        $ref = new \ReflectionProperty($this->sql, 'driver');
        $ref->setAccessible(true);
        $ref->setValue($this->sql, $driver);
    }

    public function testGetCache()
    {
        $this->assertInstanceof(Cache::class, $this->sql->getCache());
    }

    public function testGetOption()
    {
        $option = $this->sql->getOption();
        $this->assertContains('sqlite::memory:', $option);
        $this->assertContains('sqlite', $option);
    }

    public function testSetOption()
    {
        $option = $this->sql->setOption([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ])->getOption();

        $this->assertContains('mysql', $option);
        $this->assertContains('test_stick', $option);
        $this->assertContains('127.0.0.1', $option);
        $this->assertContains(3306, $option);
        $this->assertContains('root', $option);
        $this->assertContains('pass', $option);
        $this->assertContains('mysql:host=127.0.0.1;port=3306;dbname=test_stick', $option);
    }

    public function testSetOptionDsn()
    {
        $this->sql->setOption(['dsn'=>'mysql:host=localhost;dbname=foo']);
        $this->assertContains('foo', $this->sql->getOption());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage There is no logic for unknown DSN creation, please provide a valid one
     */
    public function testSetOptionException1()
    {
        $this->sql->setOption([]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid mysql driver configuration
     */
    public function testSetOptionException2()
    {
        $this->sql->setOption([
            'driver' => 'mysql',
        ]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid sqlite driver configuration
     */
    public function testSetOptionException3()
    {
        $this->sql->setOption([
            'driver' => 'sqlite',
        ]);
    }

    public function testPdo()
    {
        $this->sql->setOption([
            'driver' => 'sqlite',
            'location' => ':memory:',
        ]);

        $pdo = $this->sql->pdo();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertEquals($pdo, $this->sql->pdo());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid database configuration
     */
    public function testPdoException1()
    {
        $this->sql->setOption([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ]);

        $this->sql->pdo();
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /^SQLSTATE[HY000]/
     */
    public function testPdoException2()
    {
        $this->sql->setOption([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
            'debug' => true,
        ]);

        $this->sql->pdo();
    }

    public function testExists()
    {
        $this->assertTrue($this->sql->exists('user'));
        $this->assertTrue($this->sql->exists('user2'));
        $this->assertFalse($this->sql->exists('foo'));
    }

    public function testExistsCache()
    {
        $this->build('auto');

        // with cache
        $this->assertTrue($this->sql->exists('user', 5));
        $this->assertTrue($this->sql->exists('user', 5));
    }

    public function testGetDriver()
    {
        $this->assertEquals('sqlite', $this->sql->getDriver());
    }

    public function testGetVersion()
    {
        $this->assertNotEmpty($this->sql->getVersion());
    }

    public function testGetLog()
    {
        $this->enableLog();
        $this->assertEquals('', $this->sql->getLog());

        $this->sql->exec('select * from user');
        $this->assertContains('select * from user', $this->sql->getLog());

        $this->sql->exec('insert into user (first_name) values (?)', ['quux']);
        $this->assertContains("insert into user (first_name) values ('quux')", $this->sql->getLog());

        $this->sql->exec('insert into user (id,first_name) values (?,?)', [['5',\PDO::PARAM_INT],'bleh']);
        $this->assertContains("insert into user (id,first_name) values (5,'bleh')", $this->sql->getLog());
    }


    public function testSchema()
    {
        $schema = $this->sql->schema('user');
        $this->assertEquals(['id','first_name','last_name','active'], array_keys($schema));

        // second schema
        $schema = $this->sql->schema('user2');
        $this->assertEquals(['id','name'], array_keys($schema));
    }

    public function testSchemaWithFields()
    {
        $schema = $this->sql->schema('user', ['first_name','last_name']);
        $this->assertEquals(2, count($schema));
    }

    public function testSchemaCache()
    {
        $this->build('auto');

        $init = $this->sql->schema('user', null, 5);
        $cached = $this->sql->schema('user', null, 5);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Driver unknown is not supported
     */
    public function testSchemaException()
    {
        $this->changeDriver('unknown');
        $this->sql->schema('foo.user');
    }

    public function testGetQuote()
    {
        $this->assertEquals(['`','`'], $this->sql->getQuote());

        $this->changeDriver('odbc');
        $this->assertEquals(['[',']'], $this->sql->getQuote());
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->sql->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->sql->quotekey('foo.bar'));

        $this->changeDriver('unknown');
        $this->assertEquals('foo.bar', $this->sql->quotekey('foo.bar', true));
    }

    public function testBegin()
    {
        $this->assertTrue($this->sql->begin());
    }

    public function testCommit()
    {
        $this->assertTrue($this->sql->begin());
        $this->assertTrue($this->sql->commit());
    }

    public function testRollback()
    {
        $this->assertTrue($this->sql->begin());
        $this->assertTrue($this->sql->rollBack());
    }

    public function testIsTrans()
    {
        $this->assertFalse($this->sql->isTrans());
    }

    public function execProvider()
    {
        extract($this->expected);

        return [
            [
                'select * from user',
                null,
                [$r1,$r2,$r3],
            ],
            [
                'select * from user where id = ?',
                [1],
                [$r1],
            ],
            [
                'select * from user where id = :id',
                [':id'=>1],
                [$r1],
            ],
            [
                'select * from user where id = ?',
                [1],
                [$r1],
            ],
            [
                'insert into user (first_name) values ("bleh")',
                null,
                1,
            ],
            [
                'insert into user (first_name) values (?)',
                ['one'],
                1,
            ],
            [
                [
                    'select * from user where id = ?',
                    'select * from user where id = ?',
                ],
                1,
                [[$r1],[$r1]]
            ],
            [
                '',
                null,
                null
            ]
        ];
    }

    /** @dataProvider execProvider */
    public function testExec($sql, $params, $expected)
    {
        $this->filldb();

        $this->assertEquals($expected, $this->sql->exec($sql, $params));
    }

    public function testExecCache()
    {
        $this->build('auto');
        $this->filldb();

        $sql = 'select * from user limit 1';
        $this->assertEquals([$this->expected['r1']], $this->sql->exec($sql, null, 5));
        $this->assertEquals([$this->expected['r1']], $this->sql->exec($sql, null, 5));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage PDO: no such table: muser
     */
    public function testExecException()
    {
        $this->sql->begin();
        $this->sql->exec('select * from muser');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage PDOStatement: bind or column index out of range
     */
    public function testExecException2()
    {
        $this->sql->begin();
        $this->sql->exec('select * from user where id = ?', [1,2]);
    }

    public function filterProvider()
    {
        $filter = [];

        $filter[] = [
            ['`foo` = :foo', ':foo' => 'bar'],
            ['foo'=>'bar'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo'=>'bar', ['bar'=>'baz']],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar) OR baz = 2', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo'=>'bar', ['bar'=>'baz'], '|'=>'baz = 2'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar) OR baz = 2 OR qux = 3', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo'=>'bar', ['bar'=>'baz'], '|'=>'baz = 2', '| #qux'=>'qux = 3'],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar)', ':foo' => 'bar', ':bar' => 'baz'],
            ['foo'=>'bar', '&' => ['bar'=>'baz']],
        ];
        $filter[] = [
            ['`foo` = :foo AND (`bar` = :bar OR `baz` = :baz)', ':foo' => 'bar', ':bar' => 'baz',':baz'=>'qux'],
            ['foo'=>'bar', '&' => ['bar'=>'baz','| baz'=>'qux']],
        ];
        $filter[] = [
            ['foo = 1 OR `bar` != :bar', ':bar'=>'baz'],
            ['foo = 1', '| bar !=' => 'baz'],
        ];
        $filter[] = [
            ['foo = 1 OR `bar` != :bar AND (baz = 2)', ':bar'=>'baz'],
            ['foo = 1', '| bar !=' => 'baz', ['baz = 2']],
        ];
        $filter[] = [
            ['`foo` = :foo AND `bar` = :bar', ':foo' => 'bar', ':bar'=>'baz'],
            ['foo'=>'bar', 'bar'=>'baz'],
        ];
        $filter[] = [
            ['`foo` = :foo AND `bar` <> :bar', ':foo' => 'bar', ':bar'=>'baz'],
            ['foo'=>'bar', 'bar <>'=>'baz'],
        ];
        $filter[] = [
            ['`foo` LIKE :foo', ':foo' => 'bar'],
            ['foo ~'=>'bar'],
        ];
        $filter[] = [
            ['`foo` NOT LIKE :foo', ':foo' => 'bar'],
            ['foo !~'=>'bar'],
        ];
        $filter[] = [
            ['`foo` SOUNDS LIKE :foo', ':foo' => 'bar'],
            ['foo @'=>'bar'],
        ];
        $filter[] = [
            ['`foo` BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2'=>3],
            ['foo ><'=>[1,3]],
        ];
        $filter[] = [
            ['`foo` NOT BETWEEN :foo1 AND :foo2', ':foo1' => 1, ':foo2'=>3],
            ['foo !><'=>[1,3]],
        ];
        $filter[] = [
            ['`foo` IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2'=>'bar',':foo3'=>'baz'],
            ['foo []'=>['foo','bar','baz']],
        ];
        $filter[] = [
            ['`foo` NOT IN (:foo1, :foo2, :foo3)', ':foo1' => 'foo', ':foo2'=>'bar',':foo3'=>'baz'],
            ['foo ![]'=>['foo','bar','baz']],
        ];
        $filter[] = [
            ['`foo` = bar + 1'],
            ['foo'=>'```bar + 1'],
        ];

        return $filter;
    }

    /** @dataProvider filterProvider */
    public function testFilter($expected, $filter)
    {
        $this->assertEquals($expected, $this->sql->filter($filter));
    }

    public function testFilterEmpty()
    {
        $this->assertEquals([], $this->sql->filter(null));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage BETWEEN operator needs an array operand, string given
     */
    public function testFilterException1()
    {
        $this->sql->filter(['foo ><'=>'str']);
    }

    public function testGetName()
    {
        $this->assertEquals('', $this->sql->getName());
    }

    public function testGetEncoding()
    {
        $this->assertEquals('UTF-8', $this->sql->getEncoding());
    }

    public function testSetEncoding()
    {
        $this->assertEquals('foo', $this->sql->setEncoding('foo')->getEncoding());
    }

    public function testType()
    {
        $this->assertEquals(\PDO::PARAM_NULL, $this->sql->type(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->sql->type(true));
        $this->assertEquals(\PDO::PARAM_INT, $this->sql->type(1));
        $this->assertEquals(Sql::PARAM_FLOAT, $this->sql->type(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->sql->type('foo'));
        $fp = fopen(FIXTURE . 'long.txt', 'rb');
        $this->assertEquals(\PDO::PARAM_LOB, $this->sql->type($fp));
    }

    public function testRealType()
    {
        $this->assertEquals(\PDO::PARAM_STR, $this->sql->realType(0.0));
        $this->assertEquals(\PDO::PARAM_STR, $this->sql->realType(null, Sql::PARAM_FLOAT));
    }

    public function testValue()
    {
        $this->assertEquals(null, $this->sql->value(\PDO::PARAM_NULL, 'foo'));
        $this->assertEquals('0.0', $this->sql->value(Sql::PARAM_FLOAT, 0.0));
        $this->assertEquals(true, $this->sql->value(\PDO::PARAM_BOOL, 1));
        $this->assertEquals(1, $this->sql->value(\PDO::PARAM_INT, '1'));
        $this->assertEquals('1', $this->sql->value(\PDO::PARAM_STR, '1'));
        $this->assertEquals('1', $this->sql->value('foo', '1'));
        $fp = fopen(FIXTURE . 'long.txt', 'rb');
        $this->assertEquals((binary) $fp, $this->sql->value(\PDO::PARAM_LOB, $fp));
    }

    public function testQuote()
    {
        $this->assertEquals("'foo'", $this->sql->quote('foo'));
        $this->changeDriver(Sql::DB_ODBC);
        $this->assertEquals("'foo'", $this->sql->quote('foo'));
    }

    public function testGetRows()
    {
        $this->assertEquals(0, $this->sql->getRows());
        $out = $this->sql->exec('insert into user (first_name) values ("foo")');
        $this->assertEquals(1, $out);
        $this->assertEquals(1, $this->sql->getRows());
    }
}
