<?php

/**
 * This file is part of the eghojansu/stick.
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
use Fal\Stick\Cli\InputOption;
use Fal\Stick\Util\Common;

/**
 * Perform PHP lint.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * @codeCoverageIgnore
 */
class LintCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Perform PHP lint')
            ->setOption(
                'directories',
                'Directories to scan',
                'd',
                'src,test',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_ARRAY
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        $directories = $input->getOption('directories');
        $counter = 0;
        $error = 0;

        $console->writeln('<comment>Start linting php files, please wait...</>');
        $console->writeln();

        foreach ($directories as $dir) {
            $console->writeln("Scanning directory \"<comment>$dir</>\"");

            $dCounter = 0;
            $dError = 0;

            foreach (Common::files($dir) as $file) {
                $filepath = $file->getRealPath();

                if (
                    is_dir($filepath) ||
                    0 !== strcasecmp('php', $file->getExtension())
                ) {
                    continue;
                }

                $command = implode(' ', array(
                    PHP_BINARY,
                    '-l',
                    $filepath,
                ));

                exec($command, $output, $returnVar);

                if (0 !== $returnVar) {
                    ++$dError;
                }

                ++$dCounter;
            }

            $error += $dError;
            $counter += $dCounter;

            $console->writeln("  <info>done</> [Error/Total: <comment>$dError</>/<info>$dCounter</>]");
            $console->writeln();
        }

        $console->writeln("Error/Total file(s): <comment>$error</>/<info>$counter</>");
    }
}
