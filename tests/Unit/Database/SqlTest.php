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
        'success' => ['00000',null,null],
        'outrange' => ['HY000',25,'bind or column index out of range'],
        'notable' => ['HY000',1,'no such table: muser'],
        'nocolumn' => ['HY000',1,'no such column: foo'],
    ];
    private $sql;

    public function setUp()
    {
        $this->build();
    }

    protected function build(string $dsn = '', array $option = [])
    {
        $cache = new Cache($dsn, 'test', TEMP . 'cache/');
        $cache->reset();

        $this->sql = new Sql($cache, $option + [
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
        $this->sql->pdo()->exec('insert into user (first_name) values ("foo"), ("bar"), ("baz")');
    }

    protected function enableDebug()
    {
        $this->sql->setOption(['debug'=>true] + $this->sql->getOption());
    }

    protected function enableLog()
    {
        $this->sql->setOption(['log'=>true] + $this->sql->getOption());
    }

    protected function changeDriver(string $driver)
    {
        $ref = new \ReflectionProperty($this->sql, 'driverName');
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
     * @expectedExceptionMessage Currently, there is no logic for unknown DSN creation, please provide a valid one
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
            'attributes' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT
            ],
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

    public function testIsTableExists()
    {
        $this->assertTrue($this->sql->isTableExists('user'));
        $this->assertFalse($this->sql->isTableExists('foo'));
    }

    public function testIsTableExistsCache()
    {
        $this->build('auto');

        // with cache
        $this->assertTrue($this->sql->isTableExists('user', 5));
        $this->assertTrue($this->sql->isTableExists('user', 5));
    }

    public function testGetDriverName()
    {
        $this->assertEquals('sqlite', $this->sql->getDriverName());
    }

    public function testGetDriverVersion()
    {
        $this->assertNotEmpty($this->sql->getDriverVersion());
    }

    public function testGetLogs()
    {
        $this->enableLog();
        $this->assertEquals([], $this->sql->getLogs());

        $this->sql->run('select * from user');
        $this->assertEquals([['select * from user',null]], $this->sql->getLogs());
    }

    public function testGetErrors()
    {
        $this->assertEquals([], $this->sql->getErrors());

        $this->sql->select('muser');
        $this->assertEquals([$this->expected['notable']], $this->sql->getErrors());
    }

    public function testGetSchema()
    {
        $schema = $this->sql->getSchema('user');
        $this->assertEquals(['id','first_name','last_name','active'], array_keys($schema));

        // second schema
        $schema = $this->sql->getSchema('user2');
        $this->assertEquals(['id','name'], array_keys($schema));
    }

    public function testGetSchemaWithFields()
    {
        $schema = $this->sql->getSchema('user', ['first_name','last_name']);
        $this->assertEquals(2, count($schema));
    }

    public function testGetSchemaCache()
    {
        $this->build('auto');

        $init = $this->sql->getSchema('user', null, 5);
        $cached = $this->sql->getSchema('user', null, 5);

        $this->assertEquals($init, $cached);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Driver unknown is not supported
     */
    public function testGetSchemaException()
    {
        $this->changeDriver('unknown');
        $this->sql->getSchema('foo.user');
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

    public function runProvider()
    {
        extract($this->expected);

        return [
            [
                'select * from user',
                null,
                [
                    'success' => true,
                    'error' => $success,
                    'data' => [$r1,$r2,$r3],
                ]
            ],
            [
                'select * from user where id = ?',
                [1],
                [
                    'success' => true,
                    'error' => $success,
                    'data' => [$r1],
                ]
            ],
            [
                'select * from user where id = :id',
                [':id'=>1],
                [
                    'success' => true,
                    'error' => $success,
                    'data' => [$r1],
                ]
            ],
            [
                'select * from user where id = ?',
                [[1],['x'=>2]],
                [
                    'success' => false,
                    'error' => $outrange,
                    'data' => [[$r1]],
                ]
            ],
            [
                'select * from muser',
                null,
                [
                    'success' => false,
                    'error' => $notable,
                    'data' => [],
                ]
            ],
            [
                'insert into user (first_name) values ("bleh")',
                null,
                [
                    'success' => true,
                    'error' => $success,
                    'data' => true,
                ]
            ],
            [
                'insert into user (first_name) values (?)',
                [['one'],['two']],
                [
                    'success' => true,
                    'error' => $success,
                    'data' => [true,true],
                ]
            ],
        ];
    }

    /** @dataProvider runProvider */
    public function testRun($sql, $params, $expected)
    {
        $this->filldb();

        $this->assertEquals($expected, $this->sql->run($sql, $params));
    }

    public function runAllProvider()
    {
        extract($this->expected);
        $bleh = ['id'=>4,'first_name'=>'bleh','last_name'=>null,'active'=>1];

        return [
            [
                [
                    ['select * from user where id = 1'],
                    ['select * from user where id = 2'],
                ],
                true,
                [[$r1],[$r2]],
            ],
            [
                [
                    ['select * from user where id = 1'],
                    ['select * from muser'],
                ],
                true,
                [[$r1],[]],
            ],
            [
                [
                    ['select * from user where id = 1'],
                    ['insert into user (first_name) values ("bleh")'],
                    ['insert into user (first_name) values ("bleh2")'],
                    ['select * from user where first_name = "bleh"'],
                ],
                true,
                [[$r1],true,true,[$bleh]],
            ],
        ];
    }

    /** @dataProvider runAllProvider */
    public function testRunAll($queries, $stop, $expected)
    {
        $this->filldb();

        $this->assertEquals($expected, $this->sql->runAll($queries, $stop));
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

    public function selectProvider()
    {
        extract($this->expected);

        return [
            [
                ['user', ['first_name'=>'foo']],
                [$r1],
            ],
            [
                ['user', null, ['group'=>'id','having'=>['id []'=>[1,2,3]],'order'=>'id desc','offset'=>1,'limit'=>2]],
                [$r2,$r1],
            ],
            [
                ['user', 'foo = "bar"'],
                [],
            ],
            [
                ['user', null, ['column'=>['id','first_name','last_name','active']]],
                [$r1,$r2,$r3],
            ],
        ];
    }

    /** @dataProvider selectProvider */
    public function testSelect($args, $expected)
    {
        $this->filldb();

        $res = $this->sql->select(...$args);

        $this->assertEquals($expected, $res);
    }

    public function testSelectCache()
    {
        extract($this->expected);

        $this->build('auto');

        $this->filldb();

        // with cache
        $res = $this->sql->select('user', null, null, 5);
        $expected = [$r1,$r2,$r3];

        $this->assertEquals($expected, $res);

        // call again
        $res = $this->sql->select('user', null, null, 5);
        $this->assertEquals($expected, $res);
    }

    public function testSelectOne()
    {
        $this->filldb();
        $res = $this->sql->selectOne('user');

        $this->assertEquals($this->expected['r1'], $res);
    }

    public function insertProvider()
    {
        extract($this->expected);

        return [
            [
                ['user', ['first_name'=>'foo']],
                '1',
            ],
            [
                ['muser', ['first_name'=>'foo']],
                '0',
            ],
        ];
    }

    /** @dataProvider insertProvider */
    public function testInsert($args, $expected)
    {
        $res = $this->sql->insert(...$args);

        $this->assertEquals($expected, $res);
    }

    public function updateProvider()
    {
        extract($this->expected);

        return [
            [
                ['user', ['first_name'=>'xfoo'], 'id=1'],
                true,
                ['user', 'id=1'],
                [['first_name'=>'xfoo'] + $r1],
            ],
            [
                ['muser', ['first_name'=>'foo'],'id=1'],
                false,
                ['user', 'id=1'],
                [$r1],
            ],
            [
                ['user', ['first_name'=>'xfoo',['last_name = first_name']],'id=1'],
                true,
                ['user', 'id=1'],
                [['first_name'=>'xfoo','last_name'=>'foo'] + $r1],
            ],
        ];
    }

    /** @dataProvider updateProvider */
    public function testUpdate($args, $expected, $args2, $expected2)
    {
        $this->filldb();

        $res = $this->sql->update(...$args);
        $this->assertEquals($expected, $res);

        $confirm = $this->sql->select(...$args2);
        $this->assertEquals($expected2, $confirm);
    }

    public function testDelete()
    {
        $this->filldb();

        $this->assertEquals(3, $this->sql->count('user'));
        $this->assertEquals(1, $this->sql->count('user', 'id=1'));

        $this->assertTrue($this->sql->delete('user', 'id=1'));
        $this->assertEquals(2, $this->sql->count('user'));

        $this->assertTrue($this->sql->delete('user', null));
        $this->assertEquals(0, $this->sql->count('user'));
    }

    public function testCount()
    {
        $this->filldb();

        $this->assertEquals(3, $this->sql->count('user'));

        // query false
        $this->assertEquals(0, $this->sql->count('muser'));
    }

    public function paginateProvider()
    {
        $str = 'foo, bar, baz, qux, quux, corge, grault, garply, waldo, fred, plugh, xyzzy, thud';
        $all = split($str);
        $len = count($all);

        return [
            [
                $all,
                ['user', 1, null, ['column'=>'first_name','limit'=>2]],
                [
                    'subset' => [
                        ['first_name'=>'foo'],
                        ['first_name'=>'bar'],
                    ],
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 1,
                    'start' => 1,
                    'end'   => 2,
                ]
            ],
            [
                $all,
                ['user', 2, null, ['column'=>'first_name','limit'=>2]],
                [
                    'subset' => [
                        ['first_name'=>'baz'],
                        ['first_name'=>'qux'],
                    ],
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 2,
                    'start' => 3,
                    'end'   => 4,
                ]
            ],
            [
                $all,
                ['user', 2, null, ['column'=>'first_name','limit'=>3]],
                [
                    'subset' => [
                        ['first_name'=>'qux'],
                        ['first_name'=>'quux'],
                        ['first_name'=>'corge'],
                    ],
                    'total' => $len,
                    'pages' => 5,
                    'page'  => 2,
                    'start' => 4,
                    'end'   => 6,
                ]
            ],
            [
                $all,
                ['user', 5, null, ['column'=>'first_name','limit'=>3]],
                [
                    'subset' => [
                        ['first_name'=>'thud'],
                    ],
                    'total' => $len,
                    'pages' => 5,
                    'page'  => 5,
                    'start' => 13,
                    'end'   => 13,
                ]
            ],
            [
                $all,
                ['user', 0, null, ['column'=>'first_name','limit'=>2]],
                [
                    'subset' => [],
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 0,
                    'start' => 0,
                    'end'   => 0,
                ]
            ],
            [
                [],
                ['user', 0, null, ['column'=>'first_name','limit'=>2]],
                [
                    'subset' => [],
                    'total' => 0,
                    'pages' => 0,
                    'page'  => 0,
                    'start' => 0,
                    'end'   => 0,
                ]
            ],
        ];
    }

    /** @dataProvider paginateProvider */
    public function testPaginate($data, $args, $expected)
    {
        foreach ($data as $s) {
            $this->sql->insert('user', ['first_name'=>$s]);
        }

        $res = $this->sql->paginate(...$args);
        $this->assertEquals($expected, $res);
    }
}
