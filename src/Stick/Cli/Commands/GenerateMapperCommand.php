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

use Fal\Stick\Fw;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;
use Fal\Stick\Cli\Input;
use Fal\Stick\Cli\InputOption;
use Fal\Stick\Util\Common;

/**
 * Generate simple mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GenerateMapperCommand extends Command
{
    const TEMPLATE = 'template/mapper.php.txt';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate mapper')
            ->setOption('yes', 'Generate mapper confirmation', null, null, InputOption::VALUE_NONE)
            ->setOption('conn', 'Connection name', null, 'db', InputOption::VALUE_REQUIRED)
            ->setOption('dir', 'Mapper directory', null, 'src/Mapper', InputOption::VALUE_REQUIRED)
            ->setOption('namespace', 'Mapper namespace', null, 'App\\Mapper', InputOption::VALUE_REQUIRED)
            ->setOption('template', 'Mapper template', null, self::TEMPLATE, InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        if (!$input->getOption('yes')) {
            $console->writeln('Please confirm by passing option <comment>--yes|-y</>!');

            return;
        }

        $dir = $input->getOption('dir');

        if (!is_dir($dir)) {
            $console->writeln(sprintf(
                'Directory not exists: <comment>%s</>',
                $dir
            ));

            return;
        }

        $template = $input->getOption('template');
        $templateDir = self::TEMPLATE === $template ? __DIR__.'/' : '';

        if (!is_file($templateDir.$template)) {
            $console->writeln(sprintf(
                'Template file not exists: <comment>%s</>',
                $template
            ));

            return;
        }

        $tables = $this->tables($console->fw, $input->getOption('conn'));

        if (!$tables) {
            $console->writeln('<comment>No table available</>');

            return;
        }

        $templateContent = file_get_contents($templateDir.$template);
        $templateSearch = array(
            '${namespace}',
            '${classname}',
        );
        $namespace = $input->getOption('namespace');

        foreach ($tables as $className => $tableName) {
            $filename = rtrim($dir, '/\\').'/'.$className.'.php';
            $message = "$filename: <comment>skipped</>";

            if (!file_exists($filename)) {
                $replace = array(
                    $namespace,
                    $className,
                );
                $content = str_replace($templateSearch, $replace, $templateContent);
                file_put_contents($filename, $content);

                $message = "$filename: <info>created</>";
            }

            $console->writeln($message);
        }
    }

    /**
     * Returns available tables.
     *
     * @param Fw     $fw
     * @param string $conn
     *
     * @return array
     */
    protected function tables(Fw $fw, string $conn): array
    {
        $db = $fw->service($conn, 'Fal\\Stick\\Db\\Pdo\\Db');
        $sql = 'sqlite' !== $db->driver() ? 'show tables' :
            'select tbl_name from sqlite_master'.
            ' where type = "table" and tbl_name not like "sqlite_%"';

        return array_reduce(
            $db->pdo()->query($sql)->fetchAll(\PDO::FETCH_NUM),
            function ($carry, $row) use ($fw) {
                return $carry + array(Common::pascalCase($row[0]) => $row[0]);
            },
            array()
        );
    }
}
