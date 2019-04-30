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

namespace Fal\Stick\Test\Cli;

use Fal\Stick\Fw;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;
use Fal\Stick\TestSuite\MyTestCase;

class CommandTest extends MyTestCase
{
    public function testCreate()
    {
        $this->assertEquals('command', Command::create()->getName());
    }

    public function testGetName()
    {
        $this->assertEquals('command', $this->command->getName());
    }

    public function testSetName()
    {
        $this->assertEquals('foo', $this->command->setName('foo')->getName());
    }

    public function testGetCode()
    {
        $this->assertNull($this->command->getCode());
    }

    public function testSetCode()
    {
        $this->assertInstanceOf('Closure', $this->command->setCode(function () {})->getCode());
    }

    public function testGetArguments()
    {
        $this->assertCount(0, $this->command->getArguments());
    }

    public function testAddArgument()
    {
        $this->assertCount(1, $this->command->addArgument('foo')->getArguments());
    }

    public function testGetOptions()
    {
        $this->assertCount(0, $this->command->getOptions());
    }

    public function testAddOption()
    {
        $this->assertCount(1, $this->command->addOption('foo')->getOptions());
    }

    public function testGetHelp()
    {
        $this->assertEquals('', $this->command->getHelp());
    }

    public function testSetHelp()
    {
        $this->assertEquals('foo', $this->command->setHelp('foo')->getHelp());
    }

    public function testGetDescription()
    {
        $this->assertEquals('', $this->command->getDescription());
    }

    public function testSetDescription()
    {
        $this->assertEquals('foo', $this->command->setDescription('foo')->getDescription());
    }

    public function testRun()
    {
        $console = new Console();
        $input = new Input();
        $fw = new Fw();

        $this->command->setCode(function () {});

        $this->assertEquals(0, $this->command->run($console, $input, $fw));
    }

    public function testRunExecute()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('You must override the execute() method in the concrete command class.');

        $console = new Console();
        $input = new Input();
        $fw = new Fw();

        $this->assertEquals(0, $this->command->run($console, $input, $fw));
    }
}
