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
        $this->zip = new Zip(TEST_TEMP.'zip-test.zip', Zip::CREATE | Zip::OVERWRITE, 'foo');
    }

    public function tearDown()
    {
        if (file_exists($file = TEST_TEMP.'zip-test.zip')) {
            unlink($file);
        }
    }

    public function testGetPrefix()
    {
        $this->assertEquals('foo', $this->zip->getPrefix());
    }

    public function testSetPrefix()
    {
        $this->assertEquals('bar', $this->zip->setPrefix('bar')->getPrefix());
    }

    public function testIsCaseless()
    {
        $this->assertTrue($this->zip->isCaseless());
    }

    public function testSetCaseless()
    {
        $this->assertFalse($this->zip->setCaseless(false)->isCaseless());
    }

    /**
     * @dataProvider addProvider
     */
    public function testAdd($expected, $patterns = null, $excludes = null)
    {
        $this->assertEquals($expected, $this->zip->add(TEST_FIXTURE.'compress', $patterns, $excludes)->count());
    }

    public function addProvider()
    {
        return array(
            array(
                5,
                array(
                    '/foo/**/*.php',
                    '/{a,b,c}.php',
                    '/a?.php',
                    '/a,.php',
                ),
                array(
                    '/b.php',
                ),
            ),
        );
    }
}
