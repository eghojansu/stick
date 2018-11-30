<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 30, 2018 22:58
 */

namespace Fal\Stick\Test;

use Fal\Stick\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    private $kernel;

    public function setUp()
    {
        $this->kernel = new TestKernel();
    }

    public function testCreate()
    {
        $this->assertNotSame($this->kernel, TestKernel::create());
    }

    public function testGetFw()
    {
        $this->assertInstanceOf('Fal\\Stick\\Fw', $this->kernel->getFw());
        $this->assertSame($this->kernel, $this->kernel->getFw()->service('kernel'));
    }

    public function testGetEnvironment()
    {
        $this->assertEquals('prod', $this->kernel->getEnvironment());
    }

    public function testSetEnvironment()
    {
        $this->assertEquals('foo', $this->kernel->setEnvironment('foo')->getEnvironment());
    }

    public function testRun()
    {
        $this->expectOutputRegex('/No route defined./');

        $this->kernel->run();
    }
}

class TestKernel extends Kernel
{
}
