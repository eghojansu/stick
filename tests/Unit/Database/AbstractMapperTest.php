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
use Fal\Stick\Database\Sql\Sql;
use Fal\Stick\Database\Sql\Mapper;
use PHPUnit\Framework\TestCase;

/**
 * Testing abstract mapper methods
 */
class AbstractMapperTest extends TestCase
{
    private $mapper;

    public function setUp()
    {
        $this->build();
    }

    public function tearDown()
    {
        error_clear_last();
    }

    private function build(string $dsn = null, string $option = null)
    {
        $cache = new Cache($dsn ?? '', 'test', TEMP . 'cache/');
        $cache->reset();

        $database = new Sql($cache, ($option ?? []) + [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user_mapper` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `first_name` TEXT NOT NULL,
    `last_name` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
insert into `user_mapper` (first_name) values ("foo"), ("bar"), ("baz");
CREATE TABLE `nokey` (
    `id` INTEGER NOT NULL,
    `name` TEXT NOT NULL
);
insert into `nokey` (id,name) values(1,"foo"), (2,"bar")
SQL1
,
            ],
        ]);

        $this->mapper = new Mapper($database, 'user_mapper');
    }

    public function testFindOne()
    {
        $found = $this->mapper->findOne();
        $this->assertEquals('foo', $found->get('first_name'));

        // with filter
        $this->assertNull($this->mapper->findOne('id = 4'));
    }

    public function testSave()
    {
        $this->mapper->set('first_name', 'bleh');
        $this->mapper->save();
        $check = $this->mapper->findId(4);
        $this->assertEquals('bleh', $check->get('first_name'));

        $this->mapper->set('first_name', 'bar');
        $this->mapper->save();
        $check = $this->mapper->findId(4);
        $this->assertEquals('bar', $check->get('first_name'));
    }

    public function testGetTable()
    {
        $this->assertEquals('user_mapper', $this->mapper->getTable());
    }

    public function testSetTable()
    {
        $this->assertEquals('nokey', $this->mapper->setTable('nokey')->getTable());
    }

    public function paginateProvider()
    {
        $str = 'qux, quux, corge, grault, garply, waldo, fred, plugh, xyzzy, thud';
        $all = split($str);
        $len = 3 + count($all);

        return [
            [
                $all,
                [1, null, ['perpage'=>2]],
                [
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 1,
                    'start' => 1,
                    'end'   => 2,
                ]
            ],
            [
                $all,
                [2, null, ['perpage'=>2]],
                [
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 2,
                    'start' => 3,
                    'end'   => 4,
                ]
            ],
            [
                $all,
                [2, null, ['perpage'=>3]],
                [
                    'total' => $len,
                    'pages' => 5,
                    'page'  => 2,
                    'start' => 4,
                    'end'   => 6,
                ]
            ],
            [
                $all,
                [5, null, ['perpage'=>3]],
                [
                    'total' => $len,
                    'pages' => 5,
                    'page'  => 5,
                    'start' => 13,
                    'end'   => 13,
                ]
            ],
            [
                $all,
                [0, null, ['perpage'=>2]],
                [
                    'total' => $len,
                    'pages' => 7,
                    'page'  => 0,
                    'start' => 0,
                    'end'   => 0,
                ]
            ],
            [
                [],
                [0, null, ['perpage'=>2]],
                [
                    'total' => 3,
                    'pages' => 2,
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
            $this->mapper->fromArray(['first_name'=>$s])->insert()->reset();
        }

        $res = $this->mapper->paginate(...$args);
        $subset = array_shift($res);
        $this->assertEquals($expected, $res);
    }

    public function testUnloaded()
    {
        $this->assertTrue($this->mapper->unloaded());
        $this->assertFalse($this->mapper->loadId(1)->unloaded());
    }

    public function testLoaded()
    {
        $this->assertFalse($this->mapper->loaded());
        $this->assertTrue($this->mapper->loadId(1)->loaded());
    }

    public function testTrigger()
    {
        $this->assertFalse($this->mapper->trigger('foo'));

        $ctr = 0;
        $target = 0;
        $first = function() use (&$target, &$ctr) {
            $target = 1;
            $ctr++;
        };
        $second = function() use (&$target, &$ctr) {
            $target = 2;

            return true;
        };
        $this->mapper->addTrigger('foo', $first);
        $this->mapper->addTrigger('foo', $second, true);
        $this->assertFalse($this->mapper->trigger('foo'));
        $this->assertEquals(2, $target);
        $this->assertEquals(0, $ctr);

        $target = 0;
        $first = function() use (&$target) {
            $target = 1;
        };
        $second = function() use (&$target) {
            $target = 2;
        };
        $this->mapper->addTrigger('bar', $first);
        $this->mapper->addTrigger('bar', $second, true);
        $this->assertTrue($this->mapper->trigger('bar'));
        $this->assertEquals(1, $target);
    }

    public function testOnload()
    {
        $this->assertEquals($this->mapper, $this->mapper->onload(function() {}));
    }

    public function testBeforeinsert()
    {
        $this->assertEquals($this->mapper, $this->mapper->beforeinsert(function() {}));
    }

    public function testAfterinsert()
    {
        $this->assertEquals($this->mapper, $this->mapper->afterinsert(function() {}));
    }

    public function testOninsert()
    {
        $this->assertEquals($this->mapper, $this->mapper->oninsert(function() {}));
    }

    public function testBeforeupdate()
    {
        $this->assertEquals($this->mapper, $this->mapper->beforeupdate(function() {}));
    }

    public function testAfterupdate()
    {
        $this->assertEquals($this->mapper, $this->mapper->afterupdate(function() {}));
    }

    public function testOnupdate()
    {
        $this->assertEquals($this->mapper, $this->mapper->onupdate(function() {}));
    }

    public function testBeforesave()
    {
        $this->assertEquals($this->mapper, $this->mapper->beforesave(function() {}));
    }

    public function testAftersave()
    {
        $this->assertEquals($this->mapper, $this->mapper->aftersave(function() {}));
    }

    public function testOnsave()
    {
        $this->assertEquals($this->mapper, $this->mapper->onsave(function() {}));
    }

    public function testBeforedelete()
    {
        $this->assertEquals($this->mapper, $this->mapper->beforedelete(function() {}));
    }

    public function testAfterdelete()
    {
        $this->assertEquals($this->mapper, $this->mapper->afterdelete(function() {}));
    }

    public function testOndelete()
    {
        $this->assertEquals($this->mapper, $this->mapper->ondelete(function() {}));
    }

    public function testArrayAccess()
    {
        $this->assertNull($this->mapper['id']);
        $this->mapper['id'] = 3;
        $this->assertEquals(3, $this->mapper['id']);
        $this->assertTrue(isset($this->mapper['id']));
        unset($this->mapper['id']);
        $this->assertTrue(isset($this->mapper['id']));
        $this->assertNull($this->mapper['id']);
    }

    public function testMagicAccess()
    {
        $this->assertNull($this->mapper->id);
        $this->mapper->id = 3;
        $this->assertEquals(3, $this->mapper->id);
        $this->assertTrue(isset($this->mapper->id));
        unset($this->mapper->id);
        $this->assertTrue(isset($this->mapper->id));
        $this->assertNull($this->mapper->id);
    }

    public function testMagicCall()
    {
        $found = $this->mapper->findOneByFirstName('bar');
        $this->assertEquals(2, $found->get('id'));
        $this->assertEquals('bar', $found->get('first_name'));

        $all = $this->mapper->findByFirstName('bar');
        $this->assertEquals(1, count($all));
        $this->assertEquals(2, $all[0]->get('id'));
        $this->assertEquals('bar', $all[0]->get('first_name'));

        $this->mapper->loadByFirstName('bar');
        $this->assertEquals(2, $this->mapper->get('id'));
        $this->assertEquals('bar', $this->mapper->get('first_name'));
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionRegex /Call to undefined method/
     */
    public function testMagicCallParent()
    {
        $this->mapper->undefinedMethod();
    }
}
