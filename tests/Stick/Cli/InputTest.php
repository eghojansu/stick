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

use Fal\Stick\Cli\Command;
use Fal\Stick\TestSuite\MyTestCase;

class InputTest extends MyTestCase
{
    public function testHasArgument()
    {
        $this->assertFalse($this->input->hasArgument('foo'));
    }

    public function testGetArgument()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Argument not exists: foo.');

        $this->input->getArgument('foo');
    }

    public function testGetArguments()
    {
        $this->assertCount(0, $this->input->getArguments());
    }

    public function testHasOption()
    {
        $this->assertFalse($this->input->hasOption('foo'));
    }

    public function testGetOption()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option not exists: foo.');

        $this->input->getOption('foo');
    }

    public function testGetOptions()
    {
        $this->assertCount(0, $this->input->getOptions());
    }

    public function testResolve()
    {
        $command = new Command();
        $command->addArgument('foo');
        $command->addArgument('bar');
        $command->addOption('baz');
        $command->addOption('qux');

        $arguments = array('bar');
        $options = array('baz' => 'qux');

        $this->input->resolve($command, $arguments, $options);

        $this->assertEquals('bar', $this->input->getArgument('foo'));
        $this->assertFalse($this->input->hasArgument('bar'));
        $this->assertEquals('qux', $this->input->getOption('baz'));
        $this->assertTrue($this->input->hasOption('baz'));
        $this->assertFalse($this->input->hasOption('qux'));
    }

    public function testResolveArgumentException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Command command, argument foo is required.');

        $command = new Command();
        $command->addArgument('foo', 'bar', null, true);

        $this->input->resolve($command, array(), array());
    }

    public function testResolveOptionException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Command command, option foo is required.');

        $command = new Command();
        $command->addOption('foo', 'bar', 'f', null, true);

        $this->input->resolve($command, array(), array());
    }
}
