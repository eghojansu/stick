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
use Fal\Stick\Cli\Commands\ListCommand;

class ListCommandTest extends MyTestCase
{
    public function testRun()
    {
        $console = new Console();
        $input = new Input();
        $fw = new Fw();
        $command = new ListCommand();

        $console->add($command);
        $expected = "\033[33mCommands list:\033[39m\n".
                    "  \033[32mlist\033[39m List available commands\n";

        $this->expectOutputString($expected);
        $command->run($console, $input, $fw);
    }

    public function testRunEmptyCommand()
    {
        $console = new Console();
        $input = new Input();
        $fw = new Fw();
        $command = new ListCommand();

        $expected = "\033[33mNo command\033[39m\n";

        $this->expectOutputString($expected);
        $command->run($console, $input, $fw);
    }
}
