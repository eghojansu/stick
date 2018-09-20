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
        $this->cli = new Cli();
    }

    public function writelnProvider()
    {
        return array(
            array('foo'.PHP_EOL, 'foo'),
            array("\033[0;31m\033[43mfoo\033[0m".PHP_EOL.PHP_EOL, 'foo', 'red:yellow', 2),
        );
    }

    /**
     * @dataProvider writelnProvider
     */
    public function testWriteln($expected, $line, $color = null, $newline = 1)
    {
        $this->expectOutputString($expected);
        $this->cli->writeln($line, $color, $newline);
    }

    public function testWrite()
    {
        $this->expectOutputString('foo');
        $this->cli->write('foo');
    }
}
