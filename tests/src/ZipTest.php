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

namespace Fal\Stick\Test;

use Fal\Stick\Zip;
use PHPUnit\Framework\TestCase;

class ZipTest extends TestCase
{
    private $zip;

    public function setUp()
    {
        $this->zip = new Zip(TEST_TEMP.'zip-test.zip', 'create');
    }

    public function tearDown()
    {
        if (file_exists($file = TEST_TEMP.'zip-test.zip')) {
            unlink($file);
        }
    }

    public function testCreate()
    {
        $this->assertNotSame($this->zip, Zip::create(TEST_TEMP.'zip-test.zip', 'create'));
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->zip->count());
    }

    public function testCallArchiveMethod()
    {
        $this->assertEquals('No error', $this->zip->getStatusString());
    }

    public function testCallArchiveMethodException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Call to undefined method ZipArchive::foo.');

        $this->zip->foo();
    }

    public function testGetPrefix()
    {
        $this->assertNull($this->zip->getPrefix());
    }

    public function testSetPrefix()
    {
        $this->assertEquals('foo', $this->zip->setPrefix('foo')->getPrefix());
    }

    public function testGetArchive()
    {
        $this->assertInstanceOf('ZipArchive', $this->zip->getArchive());
    }

    public function testAdd()
    {
        $this->assertEquals(3, $this->zip->add(TEST_FIXTURE.'compress')->count());
    }

    public function testAddPatterns()
    {
        $patterns = array(
            '**/*.php',
            '*.php',
        );
        $excludes = array(
            'b.php',
        );

        $this->assertEquals(2, $this->zip->add(TEST_FIXTURE.'compress', $patterns, $excludes)->count());
    }

    public function testOpen()
    {
        $this->assertSame($this->zip, $this->zip->open(TEST_TEMP.'zip-test.zip', 'create'));
    }

    public function testOpenException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No such file.');

        $this->zip->open(TEST_TEMP.'zip-test.zip', 'overwrite');
    }
}
