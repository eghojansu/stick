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
            ->setOption('dir', 'Directory to scan', 'd')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        $cwd = $input->getOption('dir') ?? getcwd().'/src';
        $length = strlen($cwd);
        $counter = 0;
        $error = 0;

        $console->writeln("<comment>Start linting php files [$cwd], please wait...</>");

        foreach (Common::files($cwd) as $file) {
            $filepath = $file->getRealPath();

            if (
                is_dir($filepath) ||
                0 !== strcasecmp('php', $file->getExtension())
            ) {
                continue;
            }

            $relative = substr($filepath, $length + 1);
            $command = implode(' ', array(
                PHP_BINARY,
                '-l',
                $filepath,
            ));

            exec($command, $output, $returnVar);

            if (0 !== $returnVar) {
                ++$error;
            }

            ++$counter;
        }

        $console->writeln("Error/Total file(s): <comment>$error</>/<info>$counter</>");
    }
}
