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

namespace Fal\Stick\Test\Cli\Commands;

use Fal\Stick\Fw;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\Console;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Cli\Commands\HelpCommand;

class HelpCommandTest extends MyTestCase
{
    public function testRun()
    {
        $console = new Console();
        $input = new Input();
        $fw = new Fw();
        $command = new HelpCommand();
        $command->setHelp('foo');
        $command->addOption('opt', 'option', null, 'foo');
        $input->resolve($command, array('help'), array());

        $console->add($command);
        $expected = "Display \033[32mhelp\033[39m for a given command\n\n".
                    "\033[33mUsage:\033[39m\n".
                    "  help [options] [--] [arguments]\n\n".
                    "\033[33mArguments:\033[39m\n".
                    "  \033[32mcommand_name\033[39m The command name \033[33m[default: 'help']\033[39m\n\n".
                    "\033[33mOptions:\033[39m\n".
                    "  \033[32m    --opt    \033[39m option \033[33m[default: 'foo']\033[39m\n".
                    "  \033[32m-h, --help   \033[39m Display command help\n".
                    "  \033[32m-v, --version\033[39m Display application version\n\n".
                    "\033[33mHelp:\033[39m\n".
                    "  foo\n";

        $this->expectOutputString($expected);
        $command->run($console, $input, $fw);
    }
}
