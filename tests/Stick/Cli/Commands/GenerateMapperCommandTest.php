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

use Fal\Stick\Cli\Commands\GenerateMapperCommand;
use Fal\Stick\Cli\Console;
use Fal\Stick\Cli\Input;
use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;
use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;

class GenerateMapperCommandTest extends MyTestCase
{
    private $console;
    private $input;
    private $command;
    private $dir;
    private $cwd;

    protected function setUp(): void
    {
        $this->cwd = getcwd();
        $this->dir = $this->tmp('', true);
        chdir($this->dir);

        $this->console = new Console(new Fw());
        $this->input = new Input();
        $this->command = new GenerateMapperCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->clear($this->tmp());
    }

    private function setDb(bool $tables = true)
    {
        $this->console->fw->set('db', new Db(
            $this->console->fw,
            new SqliteDriver(),
            'sqlite::memory:',
            null,
            null,
            $tables ? array($this->read('/files/schema_sqlite.sql')) : array()
        ));
    }

    public function testRun()
    {
        $this->input->resolve($this->command, array(), array(
            'yes' => 'yes',
        ));

        $expected = "src/Mapper/User.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Profile.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Friends.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Nokey.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Ta.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Tb.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Tc.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Session.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/TypesCheck.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Phone.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Ta2.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Tb2.php: \033[32mcreated\033[39m\n".
                    "src/Mapper/Tc2.php: \033[32mcreated\033[39m\n";

        $this->expectOutputString($expected);

        // create dir
        mkdir('src/Mapper', 0755, true);
        $this->setDb();
        $this->command->run($this->console, $this->input);
    }

    public function testRunNoDir()
    {
        $this->input->resolve($this->command, array(), array(
            'yes' => 'yes',
        ));

        $expected = "Directory not exists: \033[33msrc/Mapper\033[39m\n";

        $this->expectOutputString($expected);

        $this->setDb();
        $this->command->run($this->console, $this->input);
    }

    public function testRunNoTables()
    {
        $this->input->resolve($this->command, array(), array(
            'yes' => 'yes',
        ));

        $expected = "\033[33mNo table available\033[39m\n";

        $this->expectOutputString($expected);

        // create dir
        mkdir('src/Mapper', 0755, true);
        $this->setDb(false);
        $this->command->run($this->console, $this->input);
    }

    public function testRunNoYes()
    {
        $this->input->resolve($this->command, array(), array(
            'yes' => 'no',
        ));

        $expected = "Please give \033[33myes\033[39m first!\n".
                    "Given: \033[37;41mno\033[39;49m\n";

        $this->expectOutputString($expected);

        $this->command->run($this->console, $this->input);
    }
}
