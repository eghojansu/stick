<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick as f;
use Fal\Stick\Cache;
use Fal\Stick\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $database;

    public function setUp()
    {
        $this->build();
    }

    public function build(string $cacheDsn = '')
    {
        $cache = new Cache($cacheDsn, 'test', TEMP . 'cache/');
        $cache->reset();

        $this->database = new Database($cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
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
,
            ],
        ]);
    }

    protected function filldb()
    {
        $this->database->pdo()->exec('insert into user (first_name) values ("foo"), ("bar"), ("baz")');
    }

    protected function enableDebug()
    {
        $this->database->setOptions(['debug'=>true] + $this->database->getOptions());
    }

    protected function enableLog()
    {
        $this->database->setOptions(['log'=>true] + $this->database->getOptions());
    }

    protected function changeDriver(string $driver)
    {
        $ref = new \ReflectionProperty($this->database, 'info');
        $ref->setAccessible(true);
        $init = $ref->getValue($this->database);
        $ref->setValue($this->database, ['driver'=>$driver] + $init);
    }

    public function testGetDriver()
    {
        $this->assertEquals('sqlite', $this->database->getDriver());
    }

    public function testGetVersion()
    {
        $this->assertNotEmpty($this->database->getVersion());
    }

    public function testGetError()
    {
        $this->assertEquals('', $this->database->getError());
    }

    public function testGetInfo()
    {
        $this->assertEquals([], $this->database->getInfo());
    }

    public function testGetOptions()
    {
        $options = $this->database->getOptions();
        $this->assertContains('sqlite::memory:', $options);
        $this->assertContains('sqlite', $options);
    }

    public function testSetOptions()
    {
        $options = $this->database->setOptions([
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

    public function testSetOptionsDsn()
    {
        $this->database->setOptions(['dsn'=>'mysql:host=localhost;dbname=foo']);
        $this->assertContains('foo', $this->database->getOptions());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Currently, there is no logic for unknown DSN creation, please provide a valid one
     */
    public function testSetOptionsException1()
    {
        $this->database->setOptions([]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid mysql driver configuration
     */
    public function testSetOptionsException2()
    {
        $this->database->setOptions([
            'driver' => 'mysql',
        ]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid sqlite driver configuration
     */
    public function testSetOptionsException3()
    {
        $this->database->setOptions([
            'driver' => 'sqlite',
        ]);
    }

    public function testPdo()
    {
        $this->database->setOptions([
            'driver' => 'sqlite',
            'location' => ':memory:',
            'attributes' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT
            ],
        ]);

        $pdo = $this->database->pdo();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertEquals($pdo, $this->database->pdo());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid database configuration
     */
    public function testPdoException1()
    {
        $this->database->setOptions([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
        ]);

        $this->database->pdo();
    }

    /**
     * @expectedException PDOException
     */
    public function testPdoException2()
    {
        $this->database->setOptions([
            'driver' => 'mysql',
            'dbname' => 'test_stick',
            'username' => 'root',
            'password' => 'pass',
            'debug' => true,
        ]);

        $this->database->pdo();
    }

    public function testGetLogs()
    {
        $this->enableLog();
        $this->assertEquals([], $this->database->getLogs());

        $this->database->prepare('select * from user');
        $this->assertEquals([['select * from user',null]], $this->database->getLogs());
    }

    public function testClearLogs()
    {
        $this->assertEquals($this->database, $this->database->clearLogs());
    }

    public function testQuotes()
    {
        $this->assertEquals(['`','`'], $this->database->quotes());

        $this->changeDriver('unknown');
        $this->assertEquals(['',''], $this->database->quotes());

        $this->changeDriver('pgsql');
        $this->assertEquals(['"','"'], $this->database->quotes());
    }

    public function testQuote()
    {
        $this->assertEquals("'foo'", $this->database->quote('foo'));

        $this->changeDriver('odbc');
        $this->assertEquals("'foo'", $this->database->quote('foo'));
        $this->assertEquals(1, $this->database->quote(1));
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->database->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->database->quotekey('foo.bar'));

        $this->changeDriver('unknown');
        $this->assertEquals('foo.bar', $this->database->quotekey('foo.bar', true));
    }

    public function testTableExists()
    {
        $this->assertTrue($this->database->tableExists('user'));
        $this->assertFalse($this->database->tableExists('foo'));
    }

    public function testTableExistsCache()
    {
        $this->build('auto');

        // with cache
        $this->assertTrue($this->database->tableExists('user', 5));
        $this->assertTrue($this->database->tableExists('user', 5));
    }

    public function testTableExistsException()
    {
        $this->assertFalse($this->database->tableExists('foo'));
    }

    public function testBegin()
    {
        $this->assertTrue($this->database->begin());
    }

    public function testCommit()
    {
        $this->assertTrue($this->database->begin());
        $this->assertTrue($this->database->commit());
    }

    public function testRollBack()
    {
        $this->assertTrue($this->database->begin());
        $this->assertTrue($this->database->rollBack());
    }

    public function testTrans()
    {
        $this->assertFalse($this->database->trans());
    }

    public function testSchema()
    {
        $schema = $this->database->schema('user');
        $this->assertEquals(['id','first_name','last_name','active'], array_keys($schema));

        // second schema
        $schema = $this->database->schema('user2');
        $this->assertEquals(['id','name'], array_keys($schema));
    }

    public function testSchemaWithFields()
    {
        $schema = $this->database->schema('user', 'first_name,last_name');
        $this->assertEquals(2, count($schema));
    }

    public function testSchemaCache()
    {
        $this->build('auto');

        $init = $this->database->schema('user', null, 5);
        $cached = $this->database->schema('user', null, 5);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Driver unknown is not supported
     */
    public function testSchemaException()
    {
        $this->changeDriver('unknown');
        $this->database->schema('foo.user');
    }

    public function testRun()
    {
        $query = $this->database->prepare('SELECT * FROM user');
        $result = $this->database->run($query);
        $this->assertTrue($result);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /boundary/
     */
    public function testRunException()
    {
        $this->enableDebug();
        $query = $this->database->prepare('SELECT * FROM user WHERE id = ?');
        $result = $this->database->run($query, [1,2]);
    }

    public function testPrepare()
    {
        $query = $this->database->prepare('SELECT * FROM user');
        $this->assertInstanceOf(\PDOStatement::class, $query);
    }

    public function testPrepareInvalid()
    {
        $query = $this->database->prepare('SELECT * FROM foo');
        $this->assertNull($query);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /no such table/
     */
    public function testPrepareException()
    {
        $this->enableDebug();
        $this->database->prepare('SELECT * FROM foo');
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
        $this->assertEquals($expected, $this->database->filter($filter));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage BETWEEN operator needs an array operand, string given
     */
    public function testFilterException1()
    {
        $this->database->filter(['foo ><'=>'str']);
    }
}
