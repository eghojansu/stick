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

use Fal\Stick\Cli;
use PHPUnit\Framework\TestCase;

class CliTest extends TestCase
{
    private $cli;

    public function setUp()
    {
        $this->cli = new Cli(array('foo' => null));
    }

    public function testGetStyle()
    {
        $this->assertEquals(array(null, null, null), $this->cli->getStyle('foo'));
        $this->assertEquals(array('white', 'red', null), $this->cli->getStyle('error'));
    }

    public function testAddStyle()
    {
        $expected = array('black', 'black', null);

        $this->assertEquals($expected, $this->cli->addStyle('foo', 'black', 'black')->getStyle('foo'));
    }

    /**
     * @dataProvider linesProvider
     */
    public function testWriteln($expected, $line, $args = array())
    {
        $this->expectOutputString($expected);
        $this->cli->writeln($line, ...$args);
    }

    public function testWrite()
    {
        $this->expectOutputString("\033[37;41merror foo\033[39;49m");
        $this->cli->write('<error>error %s</error>', 'foo');
    }

    /**
     * @dataProvider colorizeProvider
     */
    public function testColorize($message, $expected, $expectedClear)
    {
        $this->assertEquals($expected, $this->cli->colorize($message, $clearText));
        $this->assertEquals($expectedClear, $clearText);
    }

    public function testApplyStyleNull()
    {
        $this->cli->addStyle('null');

        $this->assertEquals('null', $this->cli->colorize('<null>null</null>'));
    }

    public function testApplyStyleOption()
    {
        $this->cli->addStyle('bold', null, null, array('bold'));
        $expected = "\033[1mbold\033[22m";

        $this->assertEquals($expected, $this->cli->colorize('<bold>bold</bold>'));
    }

    public function linesProvider()
    {
        return array(
            array('foo'.PHP_EOL, 'foo'),
            array("\033[37;41merror foo\033[39;49m".PHP_EOL, '<error>error %s</error>', array('foo')),
        );
    }

    public function colorizeProvider()
    {
        return array(
            array('info', 'info', 'info'),
            array('<error>error</error>', "\033[37;41merror\033[39;49m", 'error'),
            array('<info>info</info>', "\033[32minfo\033[39m", 'info'),
            array('<comment>comment</comment>', "\033[33mcomment\033[39m", 'comment'),
            array('<question>question</question>', "\033[30;46mquestion\033[39;49m", 'question'),
            array('<error>error</error>: Foo', "\033[37;41merror\033[39;49m: Foo", 'error: Foo'),
            array('<error>error</>: Foo <info>info</info> Bar ', "\033[37;41merror\033[39;49m: Foo \033[32minfo\033[39m Bar ", 'error: Foo info Bar '),
            array('<error>error</error> <custom>custom</>', "\033[37;41merror\033[39;49m <custom>custom</>", 'error <custom>custom</>'),
        );
    }
}
