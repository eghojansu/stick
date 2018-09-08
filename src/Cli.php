<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Console output helper.
 *
 * Color output from: https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Cli
{
    /**
     * Foreground color.
     *
     * @var array
     */
    private static $fgColors = array(
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
    );

    /**
     * Background color.
     *
     * @var array
     */
    private static $bgColors = array(
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47',
    );

    /**
     * Write to console with new line.
     *
     * @param string      $line
     * @param string|null $color   Foreground and background color, separated by colon
     * @param int         $newline
     *
     * @return Cli
     */
    public function writeln($line, $color = null, $newline = 1)
    {
        $c = $this->parseColor($color);

        echo $c[0].$c[1].$line.$c[2].str_repeat(PHP_EOL, $newline);

        return $this;
    }

    /**
     * Write to console.
     *
     * @param string      $line
     * @param string|null $color
     *
     * @return Cli
     */
    public function write($line, $color = null)
    {
        return $this->writeln($line, $color, 0);
    }

    /**
     * Returns parsed color.
     *
     * @param string|null $color
     *
     * @return array
     */
    private function parseColor($color = null)
    {
        if ($color) {
            list($fg, $bg) = explode(':', $color) + array(1 => 'none');
            $fgFix = isset(self::$fgColors[$fg]) ? self::$fgColors[$fg] : self::$fgColors['white'];
            $bgFix = isset(self::$bgColors[$bg]) ? "\033[".self::$bgColors[$bg].'m' : '';

            return array("\033[".$fgFix.'m', $bgFix, "\033[0m");
        }

        return array('', '', '');
    }
}
