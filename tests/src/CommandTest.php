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

use Fal\Stick\Command;
use Fal\Stick\Fw;
use Fal\Stick\Util\Cli;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    private $fw;
    private $command;

    public function setUp()
    {
        $this->fw = new Fw();
        $this->command = new Command($this->fw, new Cli());
    }

    public function testRegister()
    {
        Command::register($this->fw);

        $expected = array('/', '/@command', '/@command/*');
        $actual = array_keys($this->fw['ROUTES']);

        $this->assertEquals($expected, $actual);
    }

    public function testHelp()
    {
        $this->expectOutputRegex('/Usage:/');

        $this->command->help();
    }

    public function testVersion()
    {
        $this->expectOutputRegex('/Stick-Framework/');

        $this->command->version();
    }

    public function testBuild()
    {
        $config = array(
            'dir' => FIXTURE.'compress/',
            'destination' => TEMP,
            'merge' => array(
                '**/*.php',
            ),
            'merge_excludes' => array(
                'a.php',
            ),
        );
        $file = TEMP.'compress-dev.zip';

        $this->expectOutputRegex('/'.preg_quote($file, '/').'/s');

        $this->command->build($config);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Working directory not exists: "foo".
     */
    public function testBuildException()
    {
        $this->command->build(array(
            'dir' => 'foo',
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Destination directory not exists: "foo".
     */
    public function testBuildException2()
    {
        $this->command->build(array(
            'destination' => 'foo',
        ));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Please provide release version.
     */
    public function testBuildException3()
    {
        $this->command->build(array(
            'destination' => TEMP,
            'version' => '',
        ));
    }

    /**
     * @dataProvider getCommands
     */
    public function testRun($expected, $command = null, $options = array(), $args = array())
    {
        $this->fw['GET'] = $options;

        $this->expectOutputRegex($expected);

        $this->command->run($command, $args);
    }

    public function testAddCommandsException()
    {
        $this->fw['GET']['config'] = FIXTURE.'files/commands_invalid.php';

        $this->expectOutputRegex('/Command "custom" handler is not callable/');

        $this->command->run('custom');
    }

    public function getCommands()
    {
        return array(
            array('/Usage:/'),
            array('/Stick-Framework/', 'version'),
            array('/Stick-Framework/', 'help', array('v' => '')),
            array('/Command "foo" is not defined/', 'foo'),
            array('/Configuration file is not found: "foo"/', null, array('config' => 'foo')),
            array('/Custom command executed!/', 'custom', array('config' => FIXTURE.'files/commands.php')),
            array('/Custom command2 executed!/', 'custom2', array('config' => FIXTURE.'files/commands.php')),
        );
    }
}
