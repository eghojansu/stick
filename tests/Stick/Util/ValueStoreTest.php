<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Util;

use Fal\Stick\Util\ValueStore;
use Fal\Stick\TestSuite\MyTestCase;

class ValueStoreTest extends MyTestCase
{
    private $store;

    public function setup(): void
    {
        $this->store = new ValueStore($this->fixture('/files/json_foo.json'), $this->tmp('/value_store.json', true));
    }

    public function teardown(): void
    {
        $this->clear($this->tmp());
    }

    public function testHas()
    {
        $this->assertTrue($this->store->has('foo'));
        $this->assertFalse($this->store->has('bar'));
    }

    public function testGet()
    {
        $this->assertEquals('bar', $this->store->get('foo'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Key not found: bar.');

        $this->store->get('bar');
    }

    public function testSet()
    {
        $this->assertEquals('foo', $this->store->set('bar', 'foo')->get('bar'));
    }

    public function testRem()
    {
        $this->store->set('bar', 'foo')->rem('bar');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Key not found: bar.');

        $this->store->get('bar');
    }

    public function testGetFilename()
    {
        $this->assertEquals($this->fixture('/files/json_foo.json'), $this->store->getFilename());
    }

    public function testGetSaveAs()
    {
        $this->assertEquals($this->tmp('/value_store.json'), $this->store->getSaveAs());
    }

    public function testCommit()
    {
        $this->store->set('bar', 'baz');
        $this->store->commit();

        $this->assertFileExists($this->store->getSaveAs());
        $this->assertEquals(array('foo' => 'bar'), $this->store->reload()->all());

        // second call, replace
        $this->store->set('bar', 'baz');
        $this->store->commit(true);
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $this->store->reload()->all());
    }

    public function testReload()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('JSON error: Syntax error.');

        new ValueStore($this->fixture('/files/json_invalid.json'));
    }

    public function testMerge()
    {
        $this->assertEquals('baz', $this->store->merge(array('bar' => 'baz'))->get('bar'));
    }

    public function testReplace()
    {
        $this->store->replace(array('bar' => 'baz'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Key not found: foo.');

        $this->store->get('foo');
    }
}
