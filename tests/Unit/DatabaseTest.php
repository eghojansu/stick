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
use Fal\Stick\Database;
use Fal\Stick\Test\fixture\PureUserEntity;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $database;

    public function setUp()
    {
        $this->database = new Database([
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `first_name` TEXT NOT null,
    `last_name` TEXT null DEFAULT null,
    `active` INTEGER NOT null DEFAULT 1
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

    public function testPconst()
    {
        $this->assertEquals(\PDO::FETCH_ASSOC, Database::pconst('fetch_assoc'));
        $this->assertEquals(\PDO::FETCH_ASSOC|\PDO::FETCH_COLUMN, Database::pconst('ASSOC|column', 'fetch_'));
        $this->assertEquals(\PDO::ATTR_DRIVER_NAME, Database::pconst('attr_driver_name'));
    }

    public function testGetDriver()
    {
        $this->assertEquals('sqlite', $this->database->getDriver());
    }

    public function testGetVersion()
    {
        $this->assertNotEmpty($this->database->getVersion());
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
            'db_name' => 'test_stick',
            'db_user' => 'root',
            'db_pass' => 'pass',
        ])->getOptions();

        $this->assertContains('mysql', $options);
        $this->assertContains('test_stick', $options);
        $this->assertContains('127.0.0.1', $options);
        $this->assertContains(3306, $options);
        $this->assertContains('root', $options);
        $this->assertContains('pass', $options);
        $this->assertContains('mysql:host=127.0.0.1;port=3306;dbname=test_stick', $options);
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
                Database::pconst('ATTR_ERRMODE') => Database::pconst('ERRMODE_SILENT'),
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
            'db_name' => 'test_stick',
            'db_user' => 'root',
            'db_pass' => 'pass',
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
            'db_name' => 'test_stick',
            'db_user' => 'root',
            'db_pass' => 'pass',
            'debug' => true,
        ]);

        $this->database->pdo();
    }

    public function testGetLogs()
    {
        $this->enableLog();
        $this->assertEquals([], $this->database->getLogs());
        $this->database->find('user');
        $this->assertNotEmpty($this->database->getLogs());
    }

    public function testClearLogs()
    {
        $this->assertEquals($this->database, $this->database->clearLogs());
    }

    public function testQuotekey()
    {
        $this->assertEquals('`foo`', $this->database->quotekey('foo'));
        $this->assertEquals('`foo`.`bar`', $this->database->quotekey('foo.bar'));
        $this->assertEquals('foo.bar', $this->database->quotekey('foo.bar', true, 'unknown'));
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
     * @expectedExceptionMessage BETWEEN operator needs array operand, string given
     */
    public function testFilterException1()
    {
        $this->database->filter(['foo ><'=>'str']);
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->database->count('user'));
        $this->database->insert('user', ['first_name'=>'foo']);
        $this->assertEquals(1, $this->database->count('user'));

        // query false
        $this->assertEquals(0, $this->database->count('muser'));
    }

    public function testTableExists()
    {
        $this->assertTrue($this->database->tableExists('user'));
        $this->assertFalse($this->database->tableExists('foo'));
    }

    public function testTableExistsException()
    {
        $this->enableDebug();
        $this->assertFalse($this->database->tableExists('foo'));
    }

    public function testFindOne()
    {
        $this->filldb();
        $this->database->findOne('user', 'id = 1');

        $this->assertContains('foo', $this->database->fetch());
    }

    public function testFind()
    {
        $this->filldb();
        $this->database->find('user');

        $this->assertEquals(3, count($this->database->fetchAll()));

        // with group by
        $this->database->find('user', null, ['group'=>'id','having'=>['id []'=>[1,2,3]],'order'=>'id desc','offset'=>1,'limit'=>2]);
        $result = $this->database->fetchAll();
        $this->assertEquals(2, count($result));
    }

    public function testInsert()
    {
        $this->assertTrue($this->database->insert('user', ['first_name'=>'user'])->querySuccess());
        $this->assertEquals(1, $this->database->count('user'));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to insert
     */
    public function testInsertException()
    {
        $this->database->insert('user', []);
    }

    public function testInsertBatch()
    {
        $result = $this->database->insertBatch(
            'user',
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz'],
            ],
            true
        );
        $this->assertEquals([1,2], $result);
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'foobar']));
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'barbaz']));
    }

    public function testInsertBatchCanceled()
    {
        $this->assertEquals(
            [],
            $this->database->insertBatch(
                'muser',
                [
                    ['first_name'=>'foobar'],
                    ['first_name'=>'barbaz'],
                ],
                true
            )
        );
    }

    public function testInsertBatchCanceled2()
    {
        $this->assertEquals(
            [1],
            $this->database->insertBatch(
                'user',
                [
                    ['id'=>1,'first_name'=>'foobar'],
                    ['id'=>1,'first_name'=>'barbaz'],
                ],
                true
            )
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to insert (batch)
     */
    public function testInsertBatchException1()
    {
        $this->enableDebug();
        $this->database->insertBatch(
            'user',
            [],
            true
        );
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /no such table/
     */
    public function testInsertBatchException2()
    {
        $this->enableDebug();
        $this->database->insertBatch(
            'muser',
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz'],
            ],
            true
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid record #1
     */
    public function testInsertBatchException3()
    {
        $this->enableDebug();
        $this->database->insertBatch(
            'user',
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz','last_name'=>'bar'],
            ],
            false
        );
    }

    public function testUpdate()
    {
        $this->filldb();

        $this->assertTrue($this->database->update('user', ['first_name'=>'foobar', ['last_name = first_name || " baz"']], ['first_name'=>'foo'])->querySuccess());
        $this->assertEquals(3, $this->database->count('user'));
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'foobar']));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to update
     */
    public function testUpdateException()
    {
        $this->database->update('user', [], null);
    }

    public function testUpdateBatch()
    {
        $this->filldb();

        $this->assertTrue(
            $this->database->updateBatch(
                'user',
                ['set'=>['first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
                [
                    ['data'=>['first_name'=>'foobar'],'filter'=>['id'=>1]],
                    ['data'=>['first_name'=>'barbaz'],'filter'=>['id'=>2]],
                    ['data'=>['first_name'=>'quxqux'],'filter'=>['id'=>3]],
                ],
                true
            )
        );
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'foobar']));
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'barbaz']));
    }

    public function testUpdateBatchCanceled()
    {
        $this->assertEquals(
            false,
            $this->database->updateBatch(
                'muser',
                ['set'=>['first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
                [
                    ['data'=>['first_name'=>'foobar'],'filter'=>['id'=>1]],
                    ['data'=>['first_name'=>'barbaz'],'filter'=>['id'=>2]],
                    ['data'=>['first_name'=>'quxqux'],'filter'=>['id'=>3]],
                ],
                true
            )
        );
    }

    public function testUpdateBatchCanceled2()
    {
        $this->filldb();
        $result = $this->database->updateBatch(
            'user',
            ['set'=>['id','first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
            [
                ['data'=>['id'=>1,'first_name'=>'foobar'],'filter'=>['id'=>1]],
                ['data'=>['id'=>1,'first_name'=>'barbaz'],'filter'=>['id'=>2]],
            ],
            true
        );
        $this->assertEquals(false, $result);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to update (batch)
     */
    public function testUpdateBatchException1()
    {
        $this->enableDebug();
        $this->filldb();
        $this->database->updateBatch(
            'user',
            [],
            [],
            true
        );
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /no such table/
     */
    public function testUpdateBatchException2()
    {
        $this->enableDebug();
        $this->filldb();
        $this->database->updateBatch(
            'muser',
            ['set'=>['first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
            [
                ['data'=>['first_name'=>'foobar'],'filter'=>['id'=>1]],
                ['data'=>['first_name'=>'barbaz'],'filter'=>['id'=>2]],
            ],
            true
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid record #1
     */
    public function testUpdateBatchException3()
    {
        $this->enableDebug();
        $this->filldb();
        $this->database->updateBatch(
            'user',
            ['set'=>['first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
            [
                ['data'=>['first_name'=>'foobar'],'filter'=>['id'=>1]],
                ['data'=>['first_name'=>'barbaz','last_name'=>'baz'],'filter'=>['id'=>2]],
            ],
            false
        );
    }

    public function testDelete()
    {
        $this->filldb();

        $this->assertTrue($this->database->delete('user', null)->querySuccess());
        $this->assertEquals(0, $this->database->count('user'));

        $this->filldb();
        $this->assertTrue($this->database->delete('user', ['first_name'=>'foo'])->querySuccess());
        $this->assertEquals(2, $this->database->count('user'));
    }

    public function testGetQuery()
    {
        $this->assertInstanceOf(\PDOStatement::class, $this->database->find('user')->getQuery());
    }

    public function testQuerySuccess()
    {
        $this->assertTrue($this->database->find('user')->querySuccess());
        $this->assertFalse($this->database->find('muser')->querySuccess());
    }

    public function testQueryFailed()
    {
        $this->assertFalse($this->database->find('user')->queryFailed());
        $this->assertTrue($this->database->find('muser')->queryFailed());
    }

    public function testGetMessage()
    {
        $this->assertEquals('', $this->database->getMessage());

        // query false
        $this->assertEquals('Invalid query', $this->database->find('muser')->getMessage());
    }

    public function testLastInsertId()
    {
        $this->database->insert('user', ['first_name'=>'foo']);
        $this->assertEquals(1, $this->database->lastInsertId());
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionRegex /no such table/
     */
    public function testQueryException()
    {
        $this->enableDebug();
        // invalid table
        $this->database->findOne('muser');
    }

    public function testFetch()
    {
        $this->filldb();
        $this->database->find('user');
        $this->assertContains('foo', $this->database->fetch());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage You need to run a query first before call this method
     */
    public function testFetchException()
    {
        $this->assertContains('foo', $this->database->fetch());
    }

    public function testFetchAll()
    {
        $this->filldb();
        $this->database->find('user');
        $result = $this->database->fetchAll();
        $this->assertEquals(3, count($result));

        $result = $this->database->find('user')->fetchAll(Database::pconst('fetch_class'), \stdclass::class);
        $this->assertEquals(3, count($result));
        $this->assertInstanceOf(\stdclass::class, $result[0]);
        $this->assertEquals('foo', $result[0]->first_name);

        $result = $this->database->find('user')->fetchAll(Database::pconst('fetch_class'), PureUserEntity::class);
        $this->assertInstanceOf(PureUserEntity::class, $result[0]);
        $this->assertEquals('foo', $result[0]->getName());
    }

    public function testFetchColumn()
    {
        $this->filldb();
        $this->database->find('user');
        $this->assertEquals('foo', $this->database->fetchColumn(1));
    }

    public function testExecute()
    {
        $this->assertTrue($this->database->execute(function($db) {
            $db->insert('user', ['first_name'=>'foo']);
            $db->insert('user', ['first_name'=>'bar']);
        }));

        $this->assertEquals(2, $this->database->count('user'));
        $this->database->delete('user', null);

        $this->assertFalse($this->database->execute(function($db) {
            $db->insert('user', ['first_name'=>'foo']);
            $db->insert('muser', ['first_name'=>'bar']);

            return $db->querySuccess();
        }));

        $this->assertEquals(0, $this->database->count('user'));
    }

    public function testCommit()
    {
        $this->assertFalse($this->database->trans());
        $this->assertTrue($this->database->begin());
        $this->assertTrue($this->database->trans());
        $this->database->insert('user', ['first_name'=>'foo']);
        $this->database->insert('user', ['first_name'=>'bar']);
        $this->assertTrue($this->database->commit());
        $this->assertFalse($this->database->trans());

        $this->assertEquals(2, $this->database->count('user'));
    }

    public function testRollBack()
    {
        $this->assertTrue($this->database->begin());
        $this->database->insert('user', ['first_name'=>'foo']);
        $this->database->insert('user', ['first_name'=>'bar']);
        $this->assertTrue($this->database->rollBack());

        $this->assertEquals(0, $this->database->count('user'));
    }

    public function testPaginate()
    {
        $str = 'foo, bar, baz, qux, quux, corge, grault, garply, waldo, fred, plugh, xyzzy, thud';
        $total = 0;

        foreach (f\split($str) as $s) {
            $this->database->insert('user', ['first_name'=>$s]);
            $total++;
        }

        $result = $this->database->paginate('user', 1, 2, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [
                ['first_name'=>'foo'],
                ['first_name'=>'bar'],
            ],
            'total' => $total,
            'pages' => 7,
            'page'  => 1,
            'start' => 1,
            'end'   => 2,
        ];
        $this->assertEquals($expected, $result);

        $result = $this->database->paginate('user', 2, 2, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [
                ['first_name'=>'baz'],
                ['first_name'=>'qux'],
            ],
            'total' => $total,
            'pages' => 7,
            'page'  => 2,
            'start' => 3,
            'end'   => 4,
        ];
        $this->assertEquals($expected, $result);

        $result = $this->database->paginate('user', 1, 3, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [
                ['first_name'=>'foo'],
                ['first_name'=>'bar'],
                ['first_name'=>'baz'],
            ],
            'total' => $total,
            'pages' => 5,
            'page'  => 1,
            'start' => 1,
            'end'   => 3,
        ];
        $this->assertEquals($expected, $result);

        // last page
        $result = $this->database->paginate('user', 5, 3, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [
                ['first_name'=>'thud'],
            ],
            'total' => $total,
            'pages' => 5,
            'page'  => 5,
            'start' => 13,
            'end'   => 13,
        ];
        $this->assertEquals($expected, $result);

        // invalid page
        $result = $this->database->paginate('user', 0, 2, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [],
            'total' => $total,
            'pages' => 7,
            'page'  => 0,
            'start' => 0,
            'end'   => 0,
        ];
        $this->assertEquals($expected, $result);

        // no data
        $this->database->delete('user', null);
        $result = $this->database->paginate('user', 0, 2, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [],
            'total' => 0,
            'pages' => 0,
            'page'  => 0,
            'start' => 0,
            'end'   => 0,
        ];
        $this->assertEquals($expected, $result);
    }

    public function testMagicMethod()
    {
        $this->filldb();

        $this->assertEquals(3, $this->database->countUser());
        $this->assertContains('foo', $this->database->findOneUser()->fetch());
        $this->assertContains('bar', $this->database->findOneUserByFirstName('bar')->fetch());
        $this->assertNotEmpty($this->database->findUser()->fetchAll());
        $this->assertTrue($this->database->insertUser(['first_name'=>'test'])->querySuccess());
        $this->assertTrue($this->database->updateUser(['first_name'=>'test update'], 'first_name = "test"')->querySuccess());
        $this->assertTrue($this->database->deleteUser('first_name = "test"')->querySuccess());
        $this->assertEmpty($this->database->findOneUser('first_name = "test"')->fetch());
    }

    public function testMagicMethodInsertBatch()
    {
        $this->assertEquals(
            [1,2],
            $this->database->insertBatchUser(
                [
                    ['first_name'=>'foobar'],
                    ['first_name'=>'barbaz'],
                ]
            )
        );
    }

    public function testMagicMethodUpdateBatch()
    {
        $this->filldb();

        $this->assertTrue(
            $this->database->updateBatchUser(
                ['set'=>['first_name',['last_name = first_name + " update"']], 'filter'=>['id'=>null]],
                [
                    ['data'=>['first_name'=>'foobar'],'filter'=>['id'=>1]],
                    ['data'=>['first_name'=>'barbaz'],'filter'=>['id'=>2]],
                    ['data'=>['first_name'=>'quxqux'],'filter'=>['id'=>3]],
                ]
            )
        );
    }

    public function testMagicMethodPaginate()
    {
        $str = 'foo, bar, baz, qux, quux, corge, grault, garply, waldo, fred, plugh, xyzzy, thud';
        $total = 0;

        foreach (f\split($str) as $s) {
            $this->database->insert('user', ['first_name'=>$s]);
            $total++;
        }

        $result = $this->database->paginateUser(1, 2, null, ['column'=>'first_name']);
        $expected = [
            'subset' => [
                ['first_name'=>'foo'],
                ['first_name'=>'bar'],
            ],
            'total' => $total,
            'pages' => 7,
            'page'  => 1,
            'start' => 1,
            'end'   => 2,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionRegex Invalid method
     */
    public function testMagicMethodException()
    {
        $this->database->invalidMethodCall();
    }

    public function testGetCurrentMap()
    {
        $this->assertEquals('', $this->database->getCurrentMap());
        $this->assertEquals('user', $this->database->findUser()->getCurrentMap());
    }

    public function testSetCurrentMap()
    {
        $this->assertEquals('foo', $this->database->setCurrentMap('foo')->getCurrentMap());
    }

    public function testGetMaps()
    {
        $this->assertEquals([], $this->database->getMaps());
        $this->database->setMaps(['user'=>['class'=>PureUserEntity::class]]);
        $this->assertEquals(['user'=>['class'=>PureUserEntity::class]], $this->database->getMaps());
    }

    public function testSetMaps()
    {
        $this->database->setMaps(['user'=>['class'=>PureUserEntity::class]]);
        $this->assertEquals(['class'=>PureUserEntity::class,'transformer'=>null,'select'=>null,'safe'=>[],'table'=>'user'], $this->database->getMap('user'));
    }

    public function testGetMap()
    {
        $this->database->setMaps(['user'=>['class'=>PureUserEntity::class]]);
        $this->assertEquals(['class'=>PureUserEntity::class,'transformer'=>null,'select'=>null,'safe'=>[],'table'=>'user'], $this->database->getMap('user'));
    }

    public function testSetMap()
    {
        $this->filldb();

        $this->database->setMap('user', ['class'=>PureUserEntity::class]);
        $this->assertEquals(['class'=>PureUserEntity::class,'transformer'=>null,'select'=>null,'safe'=>[],'table'=>'user'], $this->database->getMap('user'));

        $result = $this->database->findUser()->fetchAll(Database::pconst('fetch_class'));

        $this->assertInstanceOf(PureUserEntity::class, $result[0]);
        $this->assertEquals('foo', $result[0]->getName());

        $transformer = function($id) {
            return [$id];
        };
        $this->database->setMap('user', ['transformer'=>$transformer]);

        $result = $this->database->findUser()->fetchAll(Database::pconst('fetch_func'));
        $this->assertEquals([1], $result[0]);

        // clear map
        $result = $this->database->findUser()->fetchAll(Database::pconst('fetch_obj'));
        $this->assertInstanceOf(\stdclass::class, $result[0]);
        $this->assertEquals('foo', $result[0]->first_name);

        // rule for safe column
        $this->database->setMap('user', ['safe'=>['first_name']]);
        $this->database->insertUser(['first_name'=>'xfoo','unsafe'=>'value']);

        $this->assertTrue($this->database->querySuccess());
        $this->assertContains('xfoo', $this->database->findOneUserByFirstName('xfoo')->fetch());

        // update
        $this->database->updateUser(['first_name'=>'foofoo','unsafe'=>'value'], 'first_name = "xfoo"');
        $this->assertTrue($this->database->querySuccess());
        $this->assertContains('foofoo', $this->database->findOneUserByFirstName('foofoo')->fetch());

        // rule for select column
        $this->database->setMap('user', ['select'=>'id']);
        $result = $this->database->findOneUser()->fetch();
        $this->assertEquals(['id'=>1], $result);
    }

    public function testSetMapInsertBatch()
    {
        $this->database->setMap('user', ['safe'=>['first_name']]);
        $result = $this->database->insertBatch(
            'user',
            [
                ['first_name'=>'foobar','unsafe'=>'value'],
                ['first_name'=>'barbaz','unsafe'=>'value'],
            ],
            true
        );
        $this->assertEquals([1,2], $result);
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'foobar']));
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'barbaz']));
    }

    public function testSetMapUpdateBatch()
    {
        $this->filldb();
        $this->database->setMap('user', ['safe'=>['first_name']]);

        $this->assertTrue(
            $this->database->updateBatch(
                'user',
                ['set'=>['first_name',['last_name = first_name + " update"'],'unsafe'], 'filter'=>['id'=>null]],
                [
                    ['data'=>['first_name'=>'foobar','unsafe'=>1],'filter'=>['id'=>1]],
                    ['data'=>['first_name'=>'barbaz','unsafe'=>1],'filter'=>['id'=>2]],
                    ['data'=>['first_name'=>'quxqux','unsafe'=>1],'filter'=>['id'=>3]],
                ],
                true
            )
        );
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'foobar']));
        $this->assertEquals(1, $this->database->count('user', ['first_name'=>'barbaz']));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Class does not exists: InvalidClass
     */
    public function testSetMapException1()
    {
        $this->database->setMap('user', ['class'=>'InvalidClass']);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Transformer is not callable
     */
    public function testSetMapException2()
    {
        $this->database->setMap('user', ['transformer'=>'InvalidClass']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Safe option is not array
     */
    public function testSetMapException3()
    {
        $this->database->setMap('user', ['safe'=>'str']);
    }
}
