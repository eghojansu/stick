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

use Fal\Stick\Cache;
use Fal\Stick\Database\DatabaseInterface;
use Fal\Stick\Database\Mapper;
use Fal\Stick\Database\Sql;
use Fal\Stick\Test\fixture\classes\UserEntity;
use Fal\Stick\Test\fixture\classes\UserMapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
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
    protected $mapper;

    public function setUp()
    {
        $this->build();
    }

    protected function build(string $dsn = null, string $option = null, string $mapper = null)
    {
        $cache = new Cache($dsn ?? '', 'test', TEMP . 'cache/');
        $cache->reset();

        $database = new Sql($cache, ($option ?? []) + [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user_mapper` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `first_name` TEXT NOT null,
    `last_name` TEXT null DEFAULT null,
    `active` INTEGER NOT null DEFAULT 1
);
insert into user_mapper (first_name) values ("foo"), ("bar"), ("baz")
SQL1
,
            ],
        ]);

        $use = $mapper ?? Mapper::class;

        $this->mapper = new $use($database, $mapper ? null : 'user_mapper');
    }

    protected function enabledebug($log = false, string $dsn = null, string $mapper = null)
    {
        $this->build($dsn, ['debug'=>true, 'log'=>$log], $mapper);
    }

    protected function changeTable(string $table)
    {
        $ref = new \ReflectionProperty($this->mapper, 'table');
        $ref->setAccessible(true);
        $ref->setValue($this->mapper, $table);

        $ref = new \ReflectionProperty($this->mapper, 'source');
        $ref->setAccessible(true);
        $ref->setValue($this->mapper, $table);
    }

    public function testWithSource()
    {
        $clone = $this->mapper->withSource('user_mapper');

        $this->assertEquals($clone, $this->mapper);
    }

    public function testSetSource()
    {
        $this->assertEquals('user_mapper', $this->mapper->setSource('')->getSource());
    }

    public function testGetSource()
    {
        $this->assertEquals('user_mapper', $this->mapper->getSource());
    }

    public function testGetDb()
    {
        $this->assertInstanceof(DatabaseInterface::class, $this->mapper->getDb());
    }

    public function testFind()
    {
        $this->assertEquals($this->expected['r1'], $this->mapper->find(1));
        $this->assertEquals($this->expected['r1'], $this->mapper->find([1]));
    }

    /**
     * @expectedException ArgumentCountError
     * @expectedExceptionRegex /expect exactly 1 arguments, given only 2 arguments$/
     */
    public function testFindException()
    {
        $this->mapper->find([1,2]);
    }

    public function testFindOne()
    {
        $res = $this->mapper->findOne(['first_name'=>'foo']);
        $this->assertEquals($this->expected['r1'], $res);
    }

    public function testFindAll()
    {
        extract($this->expected);

        $res = $this->mapper->findAll();
        $expected = [$r1,$r2,$r3];

        $this->assertEquals($expected, $res);
    }

    public function testPaginate()
    {
        extract($this->expected);

        $res = $this->mapper->paginate();
        $expected = [
            'subset' => [$r1,$r2,$r3],
            'total' => 3,
            'pages' => 1,
            'page'  => 1,
            'start' => 1,
            'end'   => 3,
        ];

        $this->assertEquals($expected, $res);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mapper->count());
        $this->assertEquals(1, $this->mapper->count('id=1'));
    }

    public function testInsert()
    {
        $this->assertEquals('4', $this->mapper->insert(['first_name'=>'bleh']));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->mapper->update(['first_name'=>'xbleh'], 'id=1'));

        $this->changeTable('muser');
        $this->assertFalse($this->mapper->update(['first_name'=>'xbleh'], 'id=1'));
    }

    public function testDelete()
    {
        $this->assertTrue($this->mapper->delete('id=1'));

        $this->changeTable('muser');
        $this->assertFalse($this->mapper->delete('id=1'));
    }

    public function testMagicMethod()
    {
        $res = $this->mapper->findByFirstName('foo');
        $this->assertEquals([$this->expected['r1']], $res);

        $res = $this->mapper->findOneByFirstName('foo');
        $this->assertEquals($this->expected['r1'], $res);
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionRegex /^Invalid method/
     */
    public function testMagicMethodException()
    {
        $this->mapper->invalidMethodCall();
    }

    public function testFactory()
    {
        extract($this->expected);

        $this->build(null, null, UserMapper::class);

        $res = $this->mapper->find(1);
        $this->assertInstanceof(UserEntity::class, $res);

        $subset = [];
        foreach ([$r1,$r2,$r3] as $r) {
            $user = new UserEntity;
            $user->setId($r['id']);
            $user->setFirstName($r['first_name']);
            $user->setLastName($r['last_name']);

            $subset[] = $user;
        }

        $pagination = [
            'subset' => $subset,
            'total' => 3,
            'pages' => 1,
            'page'  => 1,
            'start' => 1,
            'end'   => 3,
        ];
        $res = $this->mapper->paginate();

        $this->assertEquals($pagination, $res);
    }

    public function testMapperEntity()
    {
        extract($this->expected);

        $this->build(null, null, UserMapper::class);

        $user = new UserEntity;
        $user->setId($r1['id']);
        $user->setFirstName($r1['first_name']);
        $user->setLastName($r1['last_name']);

        $res = $this->mapper->find(1);
        $this->assertEquals($user, $res);

        $res = $this->mapper->findOne($user);
        $this->assertEquals($user, $res);

        $this->mapper->beforeinsert(function($obj) {
            $obj->setFirstName('not bleh');
        });
        $user->setId(null);
        $user->setFirstName('bleh');
        $res = $this->mapper->insert($user);
        $this->assertEquals('4', $res);
        $user->setId('4');
        $res = $this->mapper->findone($user);
        $this->assertEquals($user, $res);
    }

    public function testOnload()
    {
        $this->mapper->onload(function($data, $id) {
            if ($id['id'] === '1') {
                return true;
            } elseif ($id['id'] === '2') {
                $data['first_name'] = 'load ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->find(1);
        $expected = $this->expected['r1'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->find(2);
        $expected = $this->expected['r2'];
        $expected['first_name'] = 'load bar';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->find(3);
        $this->assertNull($res);
    }

    public function testBeforeinsert()
    {
        $this->mapper->beforeinsert(function($data) {
            if ($data['first_name'] === 'bleh') {
                return true;
            } elseif ($data['first_name'] === 'xbleh') {
                $data['first_name'] = 'insert ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $this->mapper->insert(['first_name'=>'xbleh']);
        $res = $this->mapper->find(5);
        $expected = $this->expected['r1'];
        $expected['id'] = '5';
        $expected['first_name'] = 'insert xbleh';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $this->assertFalse($res);
    }

    public function testAfterinsert()
    {
        $this->mapper->afterinsert(function($data, $id) {
            if ($id === '4') {
                return true;
            } elseif ($id === '5') {
                $data['first_name'] = 'inserted ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xbleh']);
        $expected = ['first_name'=>'inserted xbleh'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $expected = '6';
        $this->assertEquals($expected, $res);
    }

    public function testOninsert()
    {
        $this->mapper->oninsert(function($data, $id) {
            if ($id === '4') {
                return true;
            } elseif ($id === '5') {
                $data['first_name'] = 'inserted ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xbleh']);
        $expected = ['first_name'=>'inserted xbleh'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $expected = '6';
        $this->assertEquals($expected, $res);
    }

    public function testBeforeupdate()
    {
        $this->mapper->beforeupdate(function($data, $id) {
            if ($id === 'id=1') {
                return true;
            } elseif ($id === 'id=2') {
                $data['first_name'] = 'update ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $this->assertTrue($res);
        $res = $this->mapper->find(2);
        $expected = $this->expected['r2'];
        $expected['first_name'] = 'update xbar';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertFalse($res);
    }

    public function testAfterupdate()
    {
        $this->mapper->afterupdate(function($data, $id) {
            if ($id === 'id=1') {
                return true;
            } elseif ($id === 'id=2') {
                $data['first_name'] = 'updated ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $expected = ['first_name'=>'updated xbar'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertTrue($res);
    }

    public function testOnupdate()
    {
        $this->mapper->onupdate(function($data, $id) {
            if ($id === 'id=1') {
                return true;
            } elseif ($id === 'id=2') {
                $data['first_name'] = 'updated ' . $data['first_name'];

                return $data;
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $expected = ['first_name'=>'updated xbar'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertTrue($res);
    }

    public function testBeforesave()
    {
        $this->mapper->beforesave(function($data, $id = null) {
            if ($id) {
                if ($id === 'id=1') {
                    return true;
                } elseif ($id === 'id=2') {
                    $data['first_name'] = 'update ' . $data['first_name'];

                    return $data;
                }
            } else {
                if ($data['first_name'] === 'bleh') {
                    return true;
                } elseif ($data['first_name'] === 'xbleh') {
                    $data['first_name'] = 'insert ' . $data['first_name'];

                    return $data;
                }
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $this->assertTrue($res);
        $res = $this->mapper->find(2);
        $expected = $this->expected['r2'];
        $expected['first_name'] = 'update xbar';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertFalse($res);

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $this->mapper->insert(['first_name'=>'xbleh']);
        $res = $this->mapper->find(5);
        $expected = $this->expected['r1'];
        $expected['id'] = '5';
        $expected['first_name'] = 'insert xbleh';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $this->assertFalse($res);
    }

    public function testAftersave()
    {
        $this->mapper->aftersave(function($data, $id = null) {
            if ($id) {
                if ($id === 'id=1') {
                    return true;
                } elseif ($id === 'id=2') {
                    $data['first_name'] = 'updated ' . $data['first_name'];

                    return $data;
                } elseif ($id === '4') {
                    return true;
                } elseif ($id === '5') {
                    $data['first_name'] = 'inserted ' . $data['first_name'];

                    return $data;
                }
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $expected = ['first_name'=>'updated xbar'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertTrue($res);

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xbleh']);
        $expected = ['first_name'=>'inserted xbleh'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $expected = '6';
        $this->assertEquals($expected, $res);
    }

    public function testOnsave()
    {
        $this->mapper->onsave(function($data, $id = null) {
            if ($id) {
                if ($id === 'id=1') {
                    return true;
                } elseif ($id === 'id=2') {
                    $data['first_name'] = 'updated ' . $data['first_name'];

                    return $data;
                } elseif ($id === '4') {
                    return true;
                } elseif ($id === '5') {
                    $data['first_name'] = 'inserted ' . $data['first_name'];

                    return $data;
                }
            }

            return false;
        });

        $res = $this->mapper->update(['first_name'=>'xfoo'], 'id=1');
        $this->assertTrue($res);

        $res = $this->mapper->update(['first_name'=>'xbar'], 'id=2');
        $expected = ['first_name'=>'updated xbar'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->update(['first_name'=>'xqux'], 'id=3');
        $this->assertTrue($res);

        $res = $this->mapper->insert(['first_name'=>'bleh']);
        $expected = '4';
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xbleh']);
        $expected = ['first_name'=>'inserted xbleh'];
        $this->assertEquals($expected, $res);

        $res = $this->mapper->insert(['first_name'=>'xfoo']);
        $expected = '6';
        $this->assertEquals($expected, $res);
    }

    public function testBeforedelete()
    {
        $this->mapper->beforedelete(function($filter, $id) {
            if ($filter === 'id=1') {
                return true;
            } elseif ($filter === 'id=2') {
                return 'id=3';
            }

            return false;
        });

        $res = $this->mapper->delete('id=1');
        $this->assertTrue($res);

        $res = $this->mapper->delete('id=2');
        $this->assertTrue($res);
        $this->assertEquals(1, $this->mapper->count('id=2'));
        $this->assertEquals(0, $this->mapper->count('id=3'));

        $res = $this->mapper->delete('id="2"');
        $this->assertFalse($res);
    }

    public function testAfterdelete()
    {
        $this->mapper->afterdelete(function($filter, $id) {
            if ($filter === 'id=1') {
                return true;
            } elseif ($filter === 'id=2') {
                return 'id=2 deleted';
            }

            return false;
        });

        $res = $this->mapper->delete('id=1');
        $this->assertTrue($res);

        $res = $this->mapper->delete('id=2');
        $this->assertEquals('id=2 deleted', $res);

        $res = $this->mapper->delete('id=3');
        $this->assertTrue($res);
    }

    public function testOndelete()
    {
        $this->mapper->ondelete(function($filter, $id) {
            if ($filter === 'id=1') {
                return true;
            } elseif ($filter === 'id=2') {
                return 'id=2 deleted';
            }

            return false;
        });

        $res = $this->mapper->delete('id=1');
        $this->assertTrue($res);

        $res = $this->mapper->delete('id=2');
        $this->assertEquals('id=2 deleted', $res);

        $res = $this->mapper->delete('id=3');
        $this->assertTrue($res);
    }
}
