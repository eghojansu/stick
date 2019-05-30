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

/**
 * Generate simple mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GenerateMapperCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate mapper')
            ->addOption('yes', 'Confirmation: type in <comment>yes</>', null, null, true)
            ->addOption('conn', 'Connection name', 'c', 'db')
            ->addOption(
                'dir',
                'Mapper directory, relative to working dir',
                null,
                'src/Mapper'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console $console, Input $input)
    {
        if ('yes' !== $yes = $input->getOption('yes')) {
            $console->writeln('Please give <comment>yes</> first!');
            $console->writeln(sprintf('Given: <error>%s</>', $yes));

            return;
        }

        $tables = $this->tables($console->fw, $input->getOption('conn'));
        $dir = $input->getOption('dir');

        if (!is_dir($dir)) {
            $console->writeln(sprintf(
                'Directory not exists: <comment>%s</>',
                $dir
            ));

            return;
        }

        if (!$tables) {
            $console->writeln('<comment>No table available</>');

            return;
        }

        foreach ($tables as $className => $tableName) {
            $filename = rtrim($dir, '/\\').'/'.$className.'.php';
            $message = "$filename: <comment>skipped</>";

            if (!file_exists($filename)) {
                file_put_contents($filename, $this->content($className, $tableName));
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
            'select tbl_name from sqlite_master where type = "table" and tbl_name not like "sqlite_%"';

        return array_reduce(
            $db->pdo()->query($sql)->fetchAll(\PDO::FETCH_NUM),
            function ($carry, $row) use ($fw) {
                return $carry + array($fw->pascalCase($row[0]) => $row[0]);
            },
            array()
        );
    }

    /**
     * Returns mapper content.
     *
     * @param string $className
     * @param string $tableName
     *
     * @return string
     */
    protected function content(string $className, string $tableName): string
    {
        return <<<CONTENT
<?php

namespace App\Mapper;

use Fal\Stick\Db\Pdo\Mapper;

class $className extends Mapper
{}
CONTENT;
    }
}
