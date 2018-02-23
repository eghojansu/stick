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

use Fal\Stick\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    private $helper;

    public function setUp()
    {
        $this->helper = new Helper(['serializer'=>'php']);
    }

    public function testGetOptions()
    {
        $this->assertEquals(['serializer'=>'php'], $this->helper->getOptions());
    }

    public function testGetOption()
    {
        $this->assertEquals('php', $this->helper->getOption('serializer'));
    }

    public function testSetOption()
    {
        $this->assertEquals('foo', $this->helper->setOption('bar', 'foo')->getOption('bar'));
    }

    public function testSerialize()
    {
        $arg = ['foo'=>'bar'];
        $expected = serialize($arg);
        $result = $this->helper->serialize($arg);
        $this->assertEquals($expected, $result);

        if (extension_loaded('igbinary')) {
            $expected = igbinary_serialize($arg);
            $this->helper->setOption('serializer', 'igbinary');
            $result = $this->helper->serialize($arg);

            $this->assertEquals($expected, $result);
        }
    }

    public function testUnserialize()
    {
        $expected = ['foo'=>'bar'];
        $arg = serialize($expected);
        $result = $this->helper->unserialize($arg);
        $this->assertEquals($expected, $result);

        if (extension_loaded('igbinary')) {
            $arg = igbinary_serialize($expected);
            $this->helper->setOption('serializer', 'igbinary');
            $result = $this->helper->unserialize($arg);

            $this->assertEquals($expected, $result);
        }
    }
}
