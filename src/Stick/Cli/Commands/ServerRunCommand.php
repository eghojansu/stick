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

/**
 * Run local development server.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * @codeCoverageIgnore
 */
class ServerRunCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Run development server')
            ->setOption('address', 'Address and port specification', 'a', '0.0.0.0:8000', InputOption::VALUE_REQUIRED)
            ->setOption('docroot', 'Specify document root', 't', 'public')
            ->setArgument('router', 'Specify router')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        $command = implode(' ', array(
            PHP_BINARY,
            '-S',
            $console->fw->get('BASE_URL') ?? $input->getOption('address'),
            '-t',
            $input->getOption('docroot'),
            $input->getArgument('router'),
        ));

        $console->writeln("Executing <comment>$command</>");
        exec($command);
    }
}
