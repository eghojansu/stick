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

namespace Fal\Stick\Test\Helper;

use Fal\Stick\Helper\ValueStore;
use PHPUnit\Framework\TestCase;

class ValueStoreTest extends TestCase
{
    private $store;

    public function setup()
    {
        $this->store = new ValueStore(TEST_FIXTURE.'files/json_foo.json', TEST_TEMP.'value_store.json');
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->store['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertEquals('bar', $this->store['foo']);
    }

    public function testOffsetSet()
    {
        $this->store['foo'] = 'baz';

        $this->assertEquals('baz', $this->store['foo']);
    }

    public function testOffsetUnset()
    {
        unset($this->store['foo']);

        $this->assertNull($this->store['foo']);
    }

    public function testGetFilename()
    {
        $this->assertEquals(TEST_FIXTURE.'files/json_foo.json', $this->store->getFilename());
    }

    public function testGetSaveAs()
    {
        $this->assertEquals(TEST_TEMP.'value_store.json', $this->store->getSaveAs());
    }

    public function testGetData()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->store->getData());
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $this->store->setData(array('bar' => 'baz'))->getData());
    }

    public function testCommit()
    {
        $this->store->setData(array('bar' => 'baz'));
        $this->store->commit();

        $this->assertFileExists($this->store->getSaveAs());
        $this->assertEquals(array('foo' => 'bar'), $this->store->reload()->getData());

        // second call, replace
        $this->store->setData(array('bar' => 'baz'));
        $this->store->commit(true);
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $this->store->reload()->getData());
    }

    public function testReload()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('JSON error: Syntax error.');

        new ValueStore(TEST_FIXTURE.'files/json_invalid.json');
    }
}
