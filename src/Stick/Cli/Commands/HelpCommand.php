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

use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\InputArgument;

/**
 * Help command.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class HelpCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Display <info>help</> for a given command')
            ->setArgument('command_name', 'The command name', 'help', InputArgument::IS_REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        $cmd = $console->getCommand($name = $input->getArgument('command_name'));
        $tab = '  ';
        $eol = PHP_EOL;
        $text = ltrim($cmd->getDescription().$eol.$eol);
        $text .= '<comment>Usage:</>'.$eol;
        $text .= $tab.$name.' [options]'.$eol;

        if ($arguments = $cmd->getArguments()) {
            // remove eol
            $text = rtrim($text);
            $text .= ' [--] [arguments]'.$eol.$eol;
            $text .= '<comment>Arguments:</>'.$eol;
            $max = max(array_map('strlen', array_keys($arguments)));

            foreach ($arguments as $argument) {
                $text .= sprintf(
                    '%s<info>%-'.$max.'s</> %s',
                    $tab,
                    $argument->getName(),
                    $argument->getDescription()
                );

                if (null !== $default = $argument->getDefaultValue()) {
                    $text .= ' <comment>[default: '.$console->fw->stringify($default).']</>';
                }

                $text .= $eol;
            }
        }

        if ($options = $cmd->getOptions()) {
            $text .= $eol.'<comment>Options:</>'.$eol;

            $max = max(array_map('strlen', array_keys($options)));
            $max = max($max, 4);

            foreach ($options as $option) {
                $alias = $option->getAlias();
                $text .= sprintf(
                    '%s<info>%-3s --%-'.$max.'s</> %s',
                    $tab,
                    $alias ? '-'.$alias.',' : '',
                    $option->getName(),
                    $option->getDescription()
                );

                if (null !== $default = $option->getDefaultValue()) {
                    $text .= ' <comment>[default: '.$console->fw->stringify($default).']</>';
                }

                $text .= $eol;
            }
        }

        if ($help = $cmd->getHelp()) {
            $text .= $eol.'<comment>Help:</>'.$eol.$tab.$help.$eol;
        }

        $console->write($text);
    }
}
