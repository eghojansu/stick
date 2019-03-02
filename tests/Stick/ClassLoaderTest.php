<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 01, 2019 06:35
 */

declare(strict_types=1);

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

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\ClassLoader', ClassLoader::create());
    }

    public function testGetFallback()
    {
        $this->assertEquals(array(), $this->loader->getFallback());
    }

    public function testSetFallback()
    {
        $this->assertEquals(array('foo'), $this->loader->setFallback(array('foo'))->getFallback());
    }

    public function testGetNamespaces()
    {
        $this->assertEquals(array('Fal\\Stick\\' => array(TEST_ROOT.'src/Stick')), $this->loader->getNamespaces());
    }

    public function testAddNamespace()
    {
        $expected = array(
            'Fal\\Stick\\' => array(TEST_ROOT.'src/Stick'),
            'foo\\' => array('foo', 'bar'),
        );

        $this->loader->addNamespace('foo\\', 'foo/');
        $this->loader->addNamespace('foo\\', array('bar/'));

        $this->assertEquals($expected, $this->loader->getNamespaces());

        // exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Namespace should ends with "\\"');

        $this->loader->addNamespace('foo', 'bar');
    }

    public function testSetNamespaces()
    {
        $expected = array(
            'Fal\\Stick\\' => array(TEST_ROOT.'src/Stick'),
            'foo\\' => array('foo'),
        );

        $this->assertEquals($expected, $this->loader->setNamespaces(array('foo\\' => 'foo/'))->getNamespaces());
    }

    public function testRegister()
    {
        $this->loader->register();
        $autoloaders = spl_autoload_functions();
        $expected = array($this->loader, 'loadClass');

        $this->assertEquals($expected, end($autoloaders));

        spl_autoload_unregister($expected);
    }

    public function testUnregister()
    {
        $this->loader->register();
        $this->loader->unregister();
        $autoloaders = spl_autoload_functions();
        $expected = array($this->loader, 'loadClass');

        $this->assertNotEquals($expected, end($autoloaders));
    }

    public function testFindClass()
    {
        $this->loader->setNamespaces(array('SpecialNamespace\\' => TEST_FIXTURE.'special-namespace/'));
        $this->loader->setFallback(array(TEST_FIXTURE.'special-namespace/different-namespace/'));

        $this->assertEquals(TEST_FIXTURE.'special-namespace/LoadOnceOnlyClass.php', $this->loader->findClass('SpecialNamespace\\LoadOnceOnlyClass'));

        // fallback
        $this->assertEquals(TEST_FIXTURE.'special-namespace/different-namespace/Divergent/DifferentClass.php', $this->loader->findClass('Divergent\\DifferentClass'));

        // unknown
        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
        // hit missing class
        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
    }

    public function testLoadClass()
    {
        $this->assertFalse(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));

        // register autoloader
        $this->loader->setNamespaces(array('SpecialNamespace\\' => TEST_FIXTURE.'special-namespace/'));

        $this->assertTrue($this->loader->loadClass('SpecialNamespace\\AutoloadOnceOnlyClass'));
        $this->assertTrue(class_exists('SpecialNamespace\\AutoloadOnceOnlyClass'));

        $this->assertNull($this->loader->loadClass('SpecialNamespace\\UnknownClass'));
        $this->assertFalse(class_exists('SpecialNamespace\\UnknownClass'));
    }
}
