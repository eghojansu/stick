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
use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;

class ConsoleTest extends MyTestCase
{
    private $console;

    protected function setUp(): void
    {
        $this->console = new Console(new Fw());
    }

    public function testGetStyle()
    {
        $this->assertEquals(array('white', 'red', null), $this->console->getStyle('error'));
    }

    public function testSetStyle()
    {
        $this->console->setStyle('foo', 'black', 'white', array('bold'));

        $this->assertEquals(array('black', 'white', array('bold')), $this->console->getStyle('foo'));
    }

    public function testWriteln()
    {
        $this->expectOutputString("\033[37;41merror\033[39;49m".PHP_EOL);
        $this->console->writeln('<error>error</error>');
    }

    public function testWrite()
    {
        $this->expectOutputString("\033[37;41merror\033[39;49m");
        $this->console->write('<error>error</error>');
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Cli\ConsoleProvider::colorize
     */
    public function testColorize($expected, $clear, $text)
    {
        $this->console->setStyle('null');
        $this->console->setStyle('bold', 'white', null, array('bold'));

        $this->assertEquals($expected, $this->console->colorize($text, $clearText));
        $this->assertEquals($clear, $clearText);
    }

    public function testHasCommand()
    {
        $this->assertFalse($this->console->hasCommand('foo'));
    }

    public function testGetCommand()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Command not found: foo.');

        $this->console->getCommand('foo');
    }

    public function testGetCommands()
    {
        $this->assertCount(0, $this->console->getCommands());
    }

    public function testAdd()
    {
        $this->assertCount(1, $this->console->add(new Command('foo'))->getCommands());
    }

    public function testAddCommands()
    {
        $this->assertCount(1, $this->console->addCommands(array(new Command('foo')))->getCommands());
    }

    public function testGetWidth()
    {
        $this->assertGreaterThan(0, $this->console->getWidth());
    }

    public function testGetHeight()
    {
        $this->assertGreaterThan(0, $this->console->getHeight());
    }

    public function testGetRoutes()
    {
        $this->assertCount(0, $this->console->getRoutes());
    }

    public function testFindCommand()
    {
        // no command
        $this->assertNull($this->console->findCommand());

        // default command
        $this->assertEquals(array(
            'command' => 'default',
            'arguments' => array(),
        ), $this->console->findCommand('default'));

        // command with argument
        $this->console->fw->set('PATH', '/help/foo');
        $this->assertEquals(array(
            'command' => 'help',
            'arguments' => array('command_name' => 'foo'),
        ), $this->console->findCommand());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Cli\ConsoleProvider::run
     */
    public function testRun($expected, $hive)
    {
        $this->console->add(Command::create('show:error', function () {
            throw new \LogicException('I am an exception.');
        }));
        $this->console->fw->mset($hive);

        $this->expectOutputString($expected);
        $this->console->run();
    }
}
