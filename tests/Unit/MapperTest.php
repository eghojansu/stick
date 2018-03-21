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
use Fal\Stick\Mapper;
use Fal\Stick\Test\fixture\UserEntity;
use Fal\Stick\Test\fixture\UserMapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    protected $mapper;

    public function setUp()
    {
        $this->build();
    }

    public function build(array $options = [], string $cacheDsn = '', string $mapper = Mapper::class)
    {
        $cache = new Cache($cacheDsn, 'test', TEMP . 'cache/');
        $cache->reset();

        $database = new Database($cache, $options + [
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
insert into user (first_name) values ("foo"), ("bar"), ("baz")
SQL1
,
            ],
        ]);

        $this->mapper = new $mapper($database, Mapper::class === $mapper ? 'user' : null);
    }

    protected function enabledebug($log = false, string $cacheDsn = '', string $mapper = Mapper::class)
    {
        $this->build(['debug'=>true, 'log'=>$log], $cacheDsn, $mapper);
    }

    protected function changeTable(string $table)
    {
        $ref = new \ReflectionProperty($this->mapper, 'table');
        $ref->setAccessible(true);
        $ref->setValue($this->mapper, $table);
    }

    public function testWithSource()
    {
        $clone = $this->mapper->withSource('user');

        $this->assertEquals($clone, $this->mapper);
    }

    public function testSetSource()
    {
        $this->assertEquals('user', $this->mapper->setSource('')->getSource());
    }

    public function testGetSource()
    {
        $this->assertEquals('user', $this->mapper->getSource());
    }

    public function testGetDb()
    {
        $this->assertInstanceof(Database::class, $this->mapper->getDb());
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mapper->count());

        // query false
        $this->assertEquals(0, $this->mapper->count(['id'=>4]));
    }

    public function testFindBy()
    {
        $result = $this->mapper->findBy(['first_name'=>'foo']);

        $this->assertContains('foo', $result[0]);

        // with group by
        $result = $this->mapper->findBy(null, ['group'=>'id','having'=>['id []'=>[1,2,3]],'order'=>'id desc','offset'=>1,'limit'=>2]);
        $this->assertEquals(2, count($result));

        // with invalid query
        $result = $this->mapper->findBy('foo = "bar"');
        $this->assertEquals([], $result);
    }

    public function testFindByCache()
    {
        $this->build([], 'auto');

        // with cache
        $init = $this->mapper->findBy(null, null, 5);
        $this->assertContains('foo', $init[0]);

        $result = $this->mapper->findBy(null, null, 5);
        $this->assertEquals($init, $result);
    }

    public function testFindOneBy()
    {
        $result = $this->mapper->findOneBy(['first_name'=>'foo']);

        $this->assertContains('foo', $result);
    }

    public function testInsert()
    {
        $id = $this->mapper->insert(['first_name'=>'qux']);
        $this->assertEquals(4, $id);

        // with invalid key
        $id = $this->mapper->insert(['first_name'=>'qux','invalid'=>'foo']);
        $this->assertEquals(5, $id);

        // with error
        $this->changeTable('foo');

        $id = $this->mapper->insert(['first_name'=>'qux','invalid'=>'foo']);
        $this->assertEquals(0, $id);
    }

    public function testInsertEntity()
    {
        $this->build([], '', UserMapper::class);

        $entity = new UserEntity();
        $entity->setFirstName('qux');

        $id = $this->mapper->insert($entity);
        $this->assertEquals(4, $id);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to insert
     */
    public function testInsertException()
    {
        $this->mapper->insert([]);
    }

    public function testUpdate()
    {
        $result = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($result);
        $result = $this->mapper->findOneBy('id=1');
        $this->assertContains('xfoo', $result);

        // with invalid key
        $result = $this->mapper->update(['first_name'=>'xfooo','invalid'=>'foo'], 'id=1');
        $this->assertTrue($result);
        $result = $this->mapper->findOneBy('id=1');
        $this->assertContains('xfooo', $result);

        // with expression
        $result = $this->mapper->update(['first_name'=>'foo',['last_name = first_name']], 'id=1');
        $this->assertTrue($result);
        $result = $this->mapper->findOneBy('id=1');
        $this->assertContains('foo', $result);
        $this->assertContains('xfooo', $result);

        // with error
        $result = $this->mapper->update(['first_name'=>'xxfoo',['last_name = first_name']], 'id=1 and foo=1');
        $this->assertFalse($result);
        $result = $this->mapper->findOneBy('id=1');
        $this->assertContains('foo', $result);
        $this->assertNotContains('xxfoo', $result);
    }

    public function testUpdateEntity()
    {
        $this->build([], '', UserMapper::class);

        $entity = $this->mapper->find(1);
        $old = clone $entity;
        $entity->setFirstName('xfoo');

        $result = $this->mapper->update($entity, $old);
        $this->assertTrue($result);

        $updated = $this->mapper->find(1);
        $this->assertEquals('xfoo', $updated->getFirstName());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No data provided to update
     */
    public function testUpdateException()
    {
        $this->mapper->update([], []);
    }

    public function testDelete()
    {
        $this->assertTrue($this->mapper->delete('id=1'));
        $this->assertEquals(2, $this->mapper->count());

        // with error filter
        $this->assertFalse($this->mapper->delete('foo=1'));
        $this->assertEquals(2, $this->mapper->count());

        // delete all
        $this->assertTrue($this->mapper->delete(null));
        $this->assertEquals(0, $this->mapper->count());
    }

    public function testMagicMethod()
    {
        $this->assertEquals(3, $this->mapper->count());
        $this->assertContains('foo', $this->mapper->find(1));
        $this->assertContains('bar', $this->mapper->findOneByFirstName('bar'));

        $result = $this->mapper->findById(1);
        $this->assertContains('foo', $result[0]);

        // with extra arguments
        $result = $this->mapper->findById(1, ['limit'=>1]);
        $this->assertEquals(1, count($result));
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionRegex /^Invalid method/
     */
    public function testMagicMethodException()
    {
        $this->mapper->invalidMethodCall();
    }

    /**
     * @expectedException ArgumentCountError
     * @expectedExceptionRegex /expect exactly 1 parameters, given only 2 parameters$/
     */
    public function testMagicMethodException2()
    {
        $this->mapper->find([1,2]);
    }

    public function testPaginate()
    {
        $str = 'qux, quux, corge, grault, garply, waldo, fred, plugh, xyzzy, thud';
        $total = 3;

        foreach (f\split($str) as $s) {
            $this->mapper->insert(['first_name'=>$s]);
            $total++;
        }

        $result = $this->mapper->paginate(1, 2, null, ['column'=>'first_name']);
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

        $result = $this->mapper->paginate(2, 2, null, ['column'=>'first_name']);
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

        $result = $this->mapper->paginate(1, 3, null, ['column'=>'first_name']);
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
        $result = $this->mapper->paginate(5, 3, null, ['column'=>'first_name']);
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
        $result = $this->mapper->paginate(0, 2, null, ['column'=>'first_name']);
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
        $this->mapper->delete(null);
        $result = $this->mapper->paginate(0, 2, null, ['column'=>'first_name']);
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

    public function testInsertBatch()
    {
        $result = $this->mapper->insertBatch(
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz'],
            ],
            true
        );
        $this->assertEquals([4,5], $result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'foobar']));
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'barbaz']));

        // with error
        $this->changeTable('foo');
        $result = $this->mapper->insertBatch(
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz'],
            ],
            true
        );
        $this->assertEquals([], $result);
    }

    public function testInsertBatchEntity()
    {
        $this->build([], '', UserMapper::class);

        $result = $this->mapper->insertBatch(
            [
                (new UserEntity)->setFirstName('foobar'),
                (new UserEntity)->setFirstName('barbaz'),
            ],
            true
        );
        $this->assertEquals([4,5], $result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'foobar']));
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'barbaz']));
    }

    public function testInsertBatchCanceled()
    {
        $this->assertEquals(
            [4],
            $this->mapper->insertBatch(
                [
                    ['id'=>4,'first_name'=>'foobar'],
                    ['id'=>4,'first_name'=>'barbaz'],
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
        $this->enabledebug();
        $this->mapper->insertBatch(
            [],
            true
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid record: #1
     */
    public function testInsertBatchException2()
    {
        $this->enabledebug();
        $this->mapper->insertBatch(
            [
                ['first_name'=>'foobar'],
                ['first_name'=>'barbaz','last_name'=>'bar'],
            ],
            false
        );
    }

    public function testUpdateBatch()
    {
        $result = $this->mapper->updateBatch(
            [
                ['data'=>['first_name'=>'foobar',['last_name = first_name + " update"']],'filter'=>['id'=>1]],
                ['data'=>['first_name'=>'barbaz'],'filter'=>['id'=>2]],
                ['data'=>['first_name'=>'quxqux'],'filter'=>['id'=>3]],
            ],
            true
        );

        $this->assertTrue($result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'foobar']));
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'barbaz']));

        // with invalid key
        $result = $this->mapper->updateBatch(
            [
                ['data'=>['first_name'=>'xfoobar','invalid'=>null],'filter'=>['id'=>1]],
                ['data'=>['first_name'=>'xbarbaz'],'filter'=>['id'=>2]],
            ],
            true
        );
        $this->assertTrue($result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'xfoobar']));
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'xbarbaz']));

        // with invalid query
        $result = $this->mapper->updateBatch(
            [
                ['data'=>['first_name'=>'xxfoobar',['invalid = foo']],'filter'=>['id'=>1]],
            ],
            true
        );
        $this->assertFalse($result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'xfoobar']));
        $this->assertEquals(0, $this->mapper->count(['first_name'=>'xxfoobar']));
    }

    public function testUpdateBatchEntity()
    {
        $this->build([], '', UserMapper::class);

        $foo = $this->mapper->find(1);
        $bar = $this->mapper->find(2);

        $foo->setFirstName('xfoo');
        $bar->setFirstName('xbar');

        $result = $this->mapper->updateBatch(
            [
                ['data'=>$foo,'filter'=>['id'=>1]],
                ['data'=>$bar,'filter'=>['id'=>2]],
            ],
            true
        );

        $this->assertTrue($result);
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'xfoo']));
        $this->assertEquals(1, $this->mapper->count(['first_name'=>'xbar']));
    }

    public function testUpdateBatchCanceled()
    {
        $result = $this->mapper->updateBatch(
            [
                ['data'=>['id'=>1,'first_name'=>'foobar',['last_name = first_name + " update"']],'filter'=>['id'=>1]],
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
        $this->enabledebug();
        $this->mapper->updateBatch(
            [],
            true
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Invalid record: #1
     */
    public function testUpdateBatchException2()
    {
        $this->enabledebug();
        $this->mapper->updateBatch(
            [
                ['data'=>['first_name'=>'foobar',['last_name = first_name + " update"']],'filter'=>['id'=>1]],
                ['data'=>['first_name'=>'barbaz','last_name'=>'baz'],'filter'=>['id'=>2]],
            ],
            false
        );
    }
}
