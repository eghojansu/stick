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

namespace Fal\Stick\Cli\Commands;

use Fal\Stick\Fw;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;

/**
 * List command.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ListCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('List available commands');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input, Fw $fw)
    {
        $commands = $console->getCommands();

        if ($commands) {
            $max = max(array_map('strlen', array_keys($commands)));

            $console->writeln('<comment>Commands list:</comment>');

            foreach ($commands as $name => $command) {
                $console->writeln(sprintf('  <info>%-'.$max.'s</info> %s', $name, $command->getDescription()));
            }
        } else {
            $console->writeln('<comment>No command</comment>');
        }
    }
}
