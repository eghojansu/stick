<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick\Cli;
use PHPUnit\Framework\TestCase;

class CliTest extends TestCase
{
    private $cli;

    public function setUp()
    {
        $this->cli = new Cli;
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testGetWidth()
    {
        $this->assertEquals(80, $this->cli->getWidth());
    }

    public function testSetWidth()
    {
        $this->assertEquals(70, $this->cli->setWidth(70)->getWidth());
    }

    public function testGetConsoleWidth()
    {
        $this->assertEquals(80, $this->cli->getConsoleWidth());
    }

    public function testSetExprColorMap()
    {
        $this->assertEquals($this->cli, $this->cli->setExprColorMap('success', 'blue'));
    }

    public function testSuccess()
    {
        $line = str_repeat(' ', 80);
        $this->expectOutputRegex('/Success!/');
        $this->cli->success('foo');
    }

    public function testDanger()
    {
        $line = str_repeat(' ', 80);
        $this->expectOutputRegex('/!!!Alert!!!/');
        $this->cli->danger('foo');
    }

    public function testWarning()
    {
        $line = str_repeat(' ', 80);
        $this->expectOutputRegex('/Warning:/');
        $this->cli->warning('foo');
    }

    public function testInfo()
    {
        $line = str_repeat(' ', 80);
        $this->expectOutputRegex('/Info:/');
        $this->cli->info('foo');
    }

    public function testBlock()
    {
        $line = str_repeat(' ', 80);
        $this->expectOutputRegex('/foo/');
        $this->cli->block('foo', 'white:cyan');
    }

    public function testWriteln()
    {
        $this->expectOutputString("\033[1;37m\033[46mfoo\033[0m\n");
        $this->cli->writeln('foo', 'white:cyan');
    }

    public function testWrite()
    {
        $this->expectOutputString("\033[1;37m\033[46mfoo\033[0m");
        $this->cli->write('foo', 'white:cyan');
    }

    public function testWriteNoColor()
    {
        $this->expectOutputString('foo');
        $this->cli->write('foo');
    }
}
