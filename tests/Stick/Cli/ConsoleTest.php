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
use Fal\Stick\Cli\Command;
use Fal\Stick\TestSuite\MyTestCase;

class ConsoleTest extends MyTestCase
{
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

    public function testRegister()
    {
        $this->console->register($fw = new Fw());

        $this->assertCount(2, $fw->get('ROUTES'));
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

    public function testHandleDefault()
    {
        $fw = new Fw();
        $fw->set('GET', array('v' => ''));

        $expected = "\033[32meghojansu/stick\033[39m \033[33mv0.1.0-beta\033[39m\n";

        $this->expectOutputString($expected);

        $this->console->handleDefault($fw, array());
    }

    public function testHandleCommand()
    {
        $fw = new Fw();
        $fw->set('GET', array('h' => ''));

        $expected = "List available commands\n\n".
                    "\033[33mUsage:\033[39m\n".
                    "  list [options]\n\n".
                    "\033[33mOptions:\033[39m\n".
                    "  \033[32m-h, --help   \033[39m Display command help\n".
                    "  \033[32m-v, --version\033[39m Display application version\n";

        $this->expectOutputString($expected);

        $this->console->handleCommand($fw, array('commands' => array('list')));
    }

    public function testGetWidth()
    {
        $this->assertGreaterThan(0, $this->console->getWidth());
    }

    public function testGetHeight()
    {
        $this->assertGreaterThan(0, $this->console->getHeight());
    }
}
