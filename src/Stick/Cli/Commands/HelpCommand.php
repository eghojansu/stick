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

use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;

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
            ->addArgument('command_name', 'The command name', 'help', true)
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

            foreach ($arguments as $argument => $desc) {
                list($info, $default) = (array) $desc + array(1 => null);

                $text .= sprintf('%s<info>%-'.$max.'s</> %s', $tab, $argument, $info);

                if (null !== $default) {
                    $text .= ' <comment>[default: '.$console->fw->stringify($default).']</>';
                }

                $text .= $eol;
            }
        }

        if ($options = $cmd->getOptions()) {
            $text .= $eol.'<comment>Options:</>'.$eol;

            $max = max(array_map('strlen', array_keys($options)));
            $max = max($max, 4);

            foreach ($options as $option => $desc) {
                list($info, $alias, $default) = (array) $desc + array(1 => null, null);

                $text .= sprintf('%s<info>%-3s --%-'.$max.'s</> %s', $tab, $alias ? '-'.$alias.',' : '', $option, $info);

                if (null !== $default) {
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
