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
use Fal\Stick\Cli\Console;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\InputArgument;
use Fal\Stick\Cli\InputOption;
use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;

class CommandTest extends MyTestCase
{
    public function testCreate()
    {
        $this->assertEquals('foo', Command::create('foo', function () {})->getName());
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

    public function testSetArgument()
    {
        $this->assertCount(1, $this->command->setArgument('foo')->getArguments());
    }

    public function testAddArgument()
    {
        $this->assertCount(1, $this->command->addArgument(InputArgument::create('foo'))->getArguments());
    }

    public function testGetOptions()
    {
        $this->assertCount(0, $this->command->getOptions());
    }

    public function testSetOption()
    {
        $this->assertCount(1, $this->command->setOption('foo')->getOptions());
    }

    public function testAddOption()
    {
        $this->assertCount(1, $this->command->addOption(InputOption::create('foo'))->getOptions());
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
        $this->command->setCode(function () {});

        $this->assertEquals(0, $this->command->run(new Console(new Fw()), new Input()));
    }

    public function testRunExecute()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('You must override the execute() method in the concrete command class.');

        $this->assertEquals(0, $this->command->run(new Console(new Fw()), new Input()));
    }

    public function testCreateInput()
    {
        $this->command
            ->setOption('opt1')
            ->setOption('opt2', null, 'o')
            // none
            ->setOption('opt3', null, 'p', null, 1)
            // array
            ->setOption('opt4', null, 't', null, 4)
            ->setOption('opt5', null, 'z', null, 4)
            ->setOption('opt6', null, 'x')
            ->setOption('opt7')
            ->setArgument('arg1')
            // array
            ->setArgument('arg2', null, null, 2)
        ;
        $arguments = array(
            // arg1 value
            '1',
            // next is arg2 value (array)
            '2',
            '3',
            '4',
        );
        $options = array(
            '--opt1',
            'optvalue',
            // this line is skipped because opt1 only get one value
            'skipped',
            // two line below is unknown option
            '--unknown',
            '-u',
            // array in assignment form
            '-opt=a,b,c',
            // array too with consume next option value
            '-z',
            'x',
            'y',
            'z',
            // to make array consume stop and demo short option form
            '-xopt6val',
        );
        $input = $this->command->createInput($arguments, $options);

        $expectedOptions = array(
            'opt1' => 'optvalue',
            'opt2' => null,
            'opt3' => true,
            'opt4' => array('a', 'b', 'c'),
            'opt5' => array('x', 'y', 'z'),
            'opt6' => 'opt6val',
            'opt7' => null,
        );
        $expectedArguments = array(
            'arg1' => '1',
            'arg2' => array('2', '3', '4'),
        );

        $this->assertEquals($expectedArguments, $input->getArguments());
        $this->assertEquals($expectedOptions, $input->getOptions());
    }

    public function testCreateInputNone()
    {
        $input = $this->command->createInput();

        $this->assertCount(0, $input->getArguments());
        $this->assertCount(0, $input->getOptions());
    }

    public function testCreateInputArgumentRequired()
    {
        $this->command->setArgument('foo', null, null, 1);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Argument "foo" is required');

        $this->command->createInput();
    }

    public function testCreateInputOptionValueRequired()
    {
        $this->command->setOption('foo', null, null, null, 2);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Option value "foo" is required');

        $this->command->createInput();
    }
}
