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
use Fal\Stick\Core;
use Fal\Stick\Cli;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    private $fw;
    private $console;
    private $cli;

    public function setUp()
    {
        $this->console = new Console($this->fw = new Core('phpunit-test'), $this->cli = new Cli());
    }

    public function testRegister()
    {
        Console::register($this->fw);

        $expected = array('/', '/@command', '/@command/@arguments*');
        $actual = array_keys($this->fw->get('ROUTES'));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider helpProvider
     */
    public function testHelpCommand($expected, $command = 'help', $version = false)
    {
        $this->expectOutputRegex($expected);

        Console::helpCommand($this->fw, $this->console, $this->cli, array('version' => $version), array('command' => $command), array('name' => 'help'));
    }

    public function testInitCommand()
    {
        $dir = TEST_TEMP.'init-test/';

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
        $this->assertFileExists($dir.'app/routes.php');
        $this->assertFileExists($dir.'app/services.php');
        $this->assertFileExists($dir.'app/env.php');
        $this->assertFileExists($dir.'app/bootstrap.php');
        $this->assertFileExists($dir.'public/index.php');
        $this->assertFileExists($dir.'public/robots.txt');
        $this->assertFileExists($dir.'composer.json');
        $this->assertFileExists($dir.'README.md');
        $this->assertFileExists($dir.'.editorconfig');
        $this->assertFileExists($dir.'.gitignore');
        $this->assertFileExists($dir.'.php_cs.dist');
        $this->assertFileExists($dir.'.stick.dist');

        $fw = require $dir.'app/bootstrap.php';
        $fw->run();

        $this->assertEquals('Welcome home, Vanilla lover!', $fw->get('OUTPUT'));
    }

    public function testInitException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Destination directory not exists: "".');

        Console::initCommand($this->fw, $this->cli, array('working-dir' => ''));
    }

    public function testBuildCommand()
    {
        $options = array(
            'working-dir' => TEST_FIXTURE.'compress/',
            'destination' => TEST_TEMP,
            'temp' => TEST_TEMP,
            'vendor-dir' => null,
            'version' => 'dev',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
            'composer' => null,
            'caseless' => true,
        );
        $file = TEST_TEMP.'compress-dev.zip';

        $this->expectOutputRegex('/'.preg_quote($file, '/').'/s');

        Console::buildCommand($this->fw, $this->cli, $options);
    }

    /**
     * @dataProvider buildExceptionProvider
     */
    public function testBuildException($expected, $options, $exception = 'LogicException')
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($expected);

        Console::buildCommand($this->fw, $this->cli, $options + array(
            'working-dir' => TEST_FIXTURE.'compress/',
            'destination' => TEST_TEMP,
            'temp' => TEST_TEMP,
            'vendor-dir' => null,
            'version' => 'dev',
            'add' => null,
            'excludes' => null,
            'merge' => '**/*.php',
            'merge_excludes' => 'a.php',
            'checkout' => false,
            'composer' => null,
            'caseless' => true,
        ));
    }

    public function testSetupCommand()
    {
        $this->fw->rule('Fal\\Stick\\Sql\\Connection', array(
            'arguments' => array(
                'fw' => '%fw%',
                'dsn' => 'sqlite::memory:',
            ),
        ));
        $this->fw->set('TEMP', TEST_TEMP);
        $options = array(
            'file' => 'TEST-VERSION',
            'versions' => array(
                'v2.1.0' => null,
                'v0.1.0' => null,
                'v0.1.0-beta' => null,
                'v0.1.0-alpha' => array(
                    'schemas' => TEST_FIXTURE.'files/schema.sql',
                    'run' => function () {},
                ),
            ),
        );
        $file = TEST_TEMP.'TEST-VERSION';

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
     * @dataProvider addCommandExceptionProvider
     */
    public function testAddCommandException($expected, $command, $exception = 'LogicException')
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($expected);

        $this->console->addCommand('foo', $command);
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($expected, $command = null, $options = array(), $args = array())
    {
        $this->fw->set('GET', $options);

        $this->expectOutputRegex($expected);

        $this->console->run($command, $args);
    }

    public function helpProvider()
    {
        return array(
            array('/command \[options\] \[arguments\]/'),
            array('/Stick-Framework/', 'help', ''),
            array('/init \[options\]/', 'init'),
            array('/setup \[options\]/', 'setup'),
        );
    }

    public function runProvider()
    {
        return array(
            array('/Usage:/'),
            array('/Stick-Framework/', 'help', array('v' => '')),
            array('/build \[options\]/', 'build', array('help' => '')),
            array('/build \[options\]/', 'help', array(), array('build')),
            array('/Command "foo" is not defined/', 'foo'),
            array('/Configuration file is not found: "foo"/', null, array('config' => 'foo')),
            array('/Custom command executed with env: foo!/', 'custom', array('config' => TEST_FIXTURE.'files/commands.php')),
            array('/Custom command2 executed with arg-foo=bar and option-config=NULL!/', 'custom2', array('config' => TEST_FIXTURE.'files/commands.php')),
            array('/custom2 \[options\] \[arguments\]/', 'custom2', array('config' => TEST_FIXTURE.'files/commands.php', 'help' => '')),
        );
    }

    public function buildExceptionProvider()
    {
        return array(
            array(
                'Working directory not exists: "foo".',
                array(
                    'working-dir' => 'foo',
                ),
            ),
            array(
                'Destination directory not exists: "foo".',
                array(
                    'destination' => 'foo',
                ),
            ),
            array(
                'Temp directory not exists: "foo".',
                array(
                    'temp' => 'foo',
                ),
            ),
            array(
                'Please provide release version.',
                array(
                    'version' => '',
                ),
            ),
        );
    }

    public function addCommandExceptionProvider()
    {
        return array(
            array(
                'Command "foo" is not callable.',
                array(
                    'run' => 'foo',
                ),
            ),
            array(
                'Command "foo", definition of argument-0 should be array with 3 elements.',
                array(
                    'run' => function () {},
                    'args' => array(null),
                ),
            ),
            array(
                'Command "foo", definition of option-0 should be array with 4 elements.',
                array(
                    'run' => function () {},
                    'options' => array(null),
                ),
            ),
        );
    }
}
