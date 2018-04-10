<?php declare(strict_types=1);

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

    public function build(string $dsn = null, string $option = null, string $mapper = null)
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

    public function testMagicMethod()
    {
        $res = $this->mapper->findByFirstName('foo');
        $this->assertEquals([$this->expected['r1']], $res);

        $res = $this->mapper->findOneByFirstName('foo');
        $this->assertEquals($this->expected['r1'], $res);

        $this->assertEquals('4', $this->mapper->insert(['first_name'=>'bleh']));
        $this->assertEquals(true, $this->mapper->update(['first_name'=>'xbleh'], 'id=1'));
        $this->assertEquals(true, $this->mapper->delete('id=1'));
        $this->assertEquals(3, $this->mapper->count());
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
}
