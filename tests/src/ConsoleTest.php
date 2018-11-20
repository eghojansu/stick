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

namespace Fal\Stick\Test;

use Fal\Stick\Console;
use Fal\Stick\Fw;
use Fal\Stick\Util\Cli;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    private $fw;
    private $console;
    private $cli;

    public function setUp()
    {
        $this->console = new Console($this->fw = new Fw(), $this->cli = new Cli());
    }

    public function testRegister()
    {
        Console::register($this->fw);

        $expected = array('/', '/@command', '/@command/*');
        $actual = array_keys($this->fw['ROUTES']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getHelps
     */
    public function testHelpCommand($expected, $command = 'help', $version = false)
    {
        $this->expectOutputRegex($expected);

        Console::helpCommand($this->fw, $this->console, $this->cli, array('version' => $version), array('command' => $command), array('name' => 'help'));
    }

    public function testInitCommand()
    {
        $dir = TEMP.'init-test/';

        if (is_dir($dir)) {
            $directoryIterator = new \RecursiveDirectoryIterator($dir);
            $iteratorIterator = new \RecursiveIteratorIterator($directoryIterator);
            $files = new \RegexIterator($iteratorIterator, '~^'.$dir.'.*~');

            foreach ($files as $file) {
                if (is_dir($file->getRealPath())) {
                    continue;
                }

                unlink($file->getRealPath());
            }
        } else {
            mkdir($dir, 0755, true);
        }

        $this->expectOutputRegex('/^Project initialized in/');

        Console::initCommand($this->fw, $this->cli, array('working-dir' => $dir));

        $this->assertFileExists($dir.'app/src/Controller/.gitkeep');
        $this->assertFileExists($dir.'app/src/Mapper/.gitkeep');
        $this->assertFileExists($dir.'app/src/Form/.gitkeep');
        $this->assertFileExists($dir.'app/template/.gitkeep');
        $this->assertFileExists($dir.'app/config.dist.php');
        $this->assertFileExists($dir.'app/controllers.php');
        $this->assertFileExists($dir.'app/env.php');
        $this->assertFileExists($dir.'app/events.php');
        $this->assertFileExists($dir.'app/routes.php');
        $this->assertFileExists($dir.'app/services.php');
        $this->assertFileExists($dir.'app/.htaccess');
        $this->assertFileExists($dir.'public/index.php');
        $this->assertFileExists($dir.'public/robots.txt');
        $this->assertFileExists($dir.'composer.json');
        $this->assertFileExists($dir.'README.md');
        $this->assertFileExists($dir.'.editorconfig');
        $this->assertFileExists($dir.'.gitignore');
        $this->assertFileExists($dir.'.php_cs.dist');
        $this->assertFileExists($dir.'.stick.dist');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Destination directory not exists: "".
     */
    public function testInitException()
    {
        Console::initCommand($this->fw, $this->cli, array('working-dir' => ''));
    }

    public function testBuildCommand()
    {
        $options = array(
            'working-dir' => FIXTURE.'compress/',
            'destination' => TEMP,
            'version' => 'dev',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
        );
        $file = TEMP.'compress-dev.zip';

        $this->expectOutputRegex('/'.preg_quote($file, '/').'/s');

        Console::buildCommand($this->fw, $this->cli, $options);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Working directory not exists: "foo".
     */
    public function testBuildException()
    {
        Console::buildCommand($this->fw, $this->cli, array(
            'working-dir' => 'foo',
            'destination' => TEMP,
            'version' => 'dev',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Destination directory not exists: "foo".
     */
    public function testBuildException2()
    {
        Console::buildCommand($this->fw, $this->cli, array(
            'working-dir' => FIXTURE.'compress/',
            'destination' => 'foo',
            'version' => 'dev',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Please provide release version.
     */
    public function testBuildException3()
    {
        Console::buildCommand($this->fw, $this->cli, array(
            'working-dir' => FIXTURE.'compress/',
            'destination' => TEMP,
            'version' => '',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
        ));
    }

    public function testSetupCommand()
    {
        $this->fw->rule('Fal\\Stick\\Sql\\Connection', array(
            'args' => array(
                'fw' => '%fw%',
                'dsn' => 'sqlite::memory:',
            ),
        ));
        $this->fw['TEMP'] = TEMP;
        $options = array(
            'file' => 'TEST-VERSION',
            'versions' => array(
                'v2.1.0' => null,
                'v0.1.0' => null,
                'v0.1.0-beta' => null,
                'v0.1.0-alpha' => array(
                    'schemas' => FIXTURE.'files/schema.sql',
                    'run' => function () {},
                ),
            ),
        );
        $file = TEMP.'TEST-VERSION';

        if (is_file($file)) {
            unlink($file);
        }

        ob_start();
        Console::setupCommand($this->fw, $this->cli, $options);
        $output = ob_get_clean();

        $this->assertContains('Setup to version', $output);
        $this->assertFileExists($file);
        $this->assertStringStartsWith('v2.1.0', file_get_contents($file));
        $this->assertTrue($this->fw->service('Fal\\Stick\\Sql\\Connection')->isTableExists('user'));

        // second call
        $this->expectOutputRegex('/Already in latest version/');
        Console::setupCommand($this->fw, $this->cli, $options);
    }

    public function testAddCommand()
    {
        $this->console->addCommand('foo', array(
            'run' => 'trim',
            'desc' => null,
        ));

        $expected = array(
            'name' => 'foo',
            'run' => 'trim',
            'desc' => '',
            'args' => array(),
            'options' => array(),
            'help' => array(),
        );

        $this->assertEquals($expected, $this->console->commands['foo']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Command "foo" is not callable.
     */
    public function testAddCommandException()
    {
        $this->console->addCommand('foo', array(
            'run' => 'foo',
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Command "foo", definition of argument-0 should be array with 3 elements.
     */
    public function testAddCommandException2()
    {
        $this->console->addCommand('foo', array(
            'run' => function () {},
            'args' => array(null),
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Command "foo", definition of option-0 should be array with 4 elements.
     */
    public function testAddCommandException3()
    {
        $this->console->addCommand('foo', array(
            'run' => function () {},
            'options' => array(null),
        ));
    }

    /**
     * @dataProvider getCommands
     */
    public function testRun($expected, $command = null, $options = array(), $args = array())
    {
        $this->fw['GET'] = $options;

        $this->expectOutputRegex($expected);

        $this->console->run($command, ...$args);
    }

    public function getHelps()
    {
        return array(
            array('/command \[options\] \[arguments\]/'),
            array('/Stick-Framework/', 'help', ''),
            array('/init \[options\]/', 'init'),
            array('/setup \[options\]/', 'setup'),
        );
    }

    public function getCommands()
    {
        return array(
            array('/Usage:/'),
            array('/Stick-Framework/', 'help', array('v' => '')),
            array('/build \[options\]/', 'build', array('help' => '')),
            array('/build \[options\]/', 'help', array(), array('build')),
            array('/Command "foo" is not defined/', 'foo'),
            array('/Configuration file is not found: "foo"/', null, array('config' => 'foo')),
            array('/Custom command executed with env: foo!/', 'custom', array('config' => FIXTURE.'files/commands.php')),
            array('/Custom command2 executed with arg-foo=bar and option-config=NULL!/', 'custom2', array('config' => FIXTURE.'files/commands.php')),
            array('/custom2 \[options\] \[arguments\]/', 'custom2', array('config' => FIXTURE.'files/commands.php', 'help' => '')),
        );
    }
}
