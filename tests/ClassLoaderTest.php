<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\ClassLoader;
use PHPUnit\Framework\TestCase;

class ClassLoaderTest extends TestCase
{
    private $loader;

    public function setup()
    {
        $this->loader = new ClassLoader();
    }

    public function teardown()
    {
        $items = new \APCUIterator('/^fal_cache_loader/');

        foreach ($items as $item) {
            apcu_delete($item['key']);
        }
    }

    public function testGetFallback()
    {
        $this->assertEquals([], $this->loader->getFallback());
    }

    public function testSetFallback()
    {
        $this->assertEquals(['foo'], $this->loader->setFallback(['foo'])->getFallback());
    }

    public function testGetNamespaces()
    {
        $this->assertEquals([], $this->loader->getNamespaces());
    }

    public function testAddNamespace()
    {
        $expected = [
            'foo\\' => ['foo', 'bar'],
        ];

        $this->loader->addNamespace('foo\\', 'foo/');
        $this->loader->addNamespace('foo\\', ['bar/']);

        $this->assertEquals($expected, $this->loader->getNamespaces());
    }

    public function testAddNamespaceException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Namespace should ends with "\\"');

        $this->loader->addNamespace('foo', 'bar');
    }

    public function testSetNamespaces()
    {
        $expected = [
            'foo\\' => ['foo'],
        ];

        $this->assertEquals($expected, $this->loader->setNamespaces(['foo\\' => 'foo/'])->getNamespaces());
    }

    public function testGetApcuPrefix()
    {
        $this->assertNull($this->loader->getApcuPrefix());
    }

    public function testSetApcuPrefix()
    {
        $this->assertEquals('foo', $this->loader->setApcuPrefix('foo')->getApcuPrefix());
    }

    public function testRegister()
    {
        $this->loader->register();
        $autoloaders = spl_autoload_functions();
        $expected = [$this->loader, 'loadClass'];

        $this->assertEquals($expected, end($autoloaders));

        spl_autoload_unregister($expected);
    }

    public function testUnregister()
    {
        $this->loader->register();
        $this->loader->unregister();
        $autoloaders = spl_autoload_functions();
        $expected = [$this->loader, 'loadClass'];

        $this->assertNotEquals($expected, end($autoloaders));
    }

    public function testFindClass()
    {
        $this->loader->setApcuPrefix('test');
        $this->loader->setNamespaces(['SpecialNamespace\\' => TEST_FIXTURE.'/special-namespace/']);
        $this->loader->setFallback([TEST_FIXTURE.'/special-namespace/different-namespace/']);

        $this->assertEquals(TEST_FIXTURE.'/special-namespace/LoadOnceOnlyClass.php', $this->loader->findClass('SpecialNamespace\\LoadOnceOnlyClass'));
        // second call hit cache
        $this->assertEquals(TEST_FIXTURE.'/special-namespace/LoadOnceOnlyClass.php', $this->loader->findClass('SpecialNamespace\\LoadOnceOnlyClass'));

        // fallback
        $this->assertEquals(TEST_FIXTURE.'/special-namespace/different-namespace/Divergent/DifferentClass.php', $this->loader->findClass('Divergent\\DifferentClass'));

        // unknown
        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
        // hit missing class
        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
    }

    public function testLoadClass()
    {
        $this->assertFalse(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));

        // register autoloader
        $this->loader->setNamespaces(['SpecialNamespace\\' => TEST_FIXTURE.'/special-namespace/']);

        $this->assertTrue($this->loader->loadClass('SpecialNamespace\\AutoloadOnceOnlyClass'));
        $this->assertTrue(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));

        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
        $this->assertFalse(class_exists('SpecialNamespace\\UnknownClass'));
    }
}
