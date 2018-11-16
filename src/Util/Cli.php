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

namespace Fal\Stick\Util;

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
     * @var array
     */
    private static $availableForegroundColors = array(
        'black' => array('set' => 30, 'unset' => 39),
        'red' => array('set' => 31, 'unset' => 39),
        'green' => array('set' => 32, 'unset' => 39),
        'yellow' => array('set' => 33, 'unset' => 39),
        'blue' => array('set' => 34, 'unset' => 39),
        'magenta' => array('set' => 35, 'unset' => 39),
        'cyan' => array('set' => 36, 'unset' => 39),
        'white' => array('set' => 37, 'unset' => 39),
        'default' => array('set' => 39, 'unset' => 39),
    );

    /**
     * @var array
     */
    private static $availableBackgroundColors = array(
        'black' => array('set' => 40, 'unset' => 49),
        'red' => array('set' => 41, 'unset' => 49),
        'green' => array('set' => 42, 'unset' => 49),
        'yellow' => array('set' => 43, 'unset' => 49),
        'blue' => array('set' => 44, 'unset' => 49),
        'magenta' => array('set' => 45, 'unset' => 49),
        'cyan' => array('set' => 46, 'unset' => 49),
        'white' => array('set' => 47, 'unset' => 49),
        'default' => array('set' => 49, 'unset' => 49),
    );

    /**
     * @var array
     */
    private static $availableOptions = array(
        'bold' => array('set' => 1, 'unset' => 22),
        'underscore' => array('set' => 4, 'unset' => 24),
        'blink' => array('set' => 5, 'unset' => 25),
        'reverse' => array('set' => 7, 'unset' => 27),
        'conceal' => array('set' => 8, 'unset' => 28),
    );

    /**
     * @var array
     */
    private $styles = array(
        'error' => array('white', 'red', null),
        'info' => array('green', null, null),
        'comment' => array('yellow', null, null),
        'question' => array('black', 'cyan', null),
    );

    /**
     * Class constructor.
     *
     * @param array|null $styles
     */
    public function __construct(array $styles = null)
    {
        foreach ((array) $styles as $style => $definition) {
            $args = array_values((array) $definition);

            $this->addStyle($style, ...$args);
        }
    }

    /**
     * Get style definition.
     *
     * @param string $name
     *
     * @return array|null
     */
    public function getStyle(string $name): ?array
    {
        return $this->styles[$name] ?? null;
    }

    /**
     * Add style definition.
     *
     * @param string      $name
     * @param string|null $foreground
     * @param string|null $background
     * @param array|null  $options
     *
     * @return Cli
     */
    public function addStyle(string $name, string $foreground = null, string $background = null, array $options = null): Cli
    {
        $style = array(
            isset(self::$availableForegroundColors[$foreground]) ? $foreground : null,
            isset(self::$availableBackgroundColors[$background]) ? $background : null,
            null,
        );

        foreach ((array) $options as $option) {
            if (isset(self::$availableOptions[$option])) {
                $style[2][] = $option;
            }
        }

        $this->styles[$name] = $style;

        return $this;
    }

    /**
     * Write to console with new line.
     *
     * @param string $str
     * @param mixed  ...$args
     *
     * @return Cli
     */
    public function writeln(string $str, ...$args): Cli
    {
        echo $this->colorize(sprintf($str, ...$args)).PHP_EOL;

        return $this;
    }

    /**
     * Write to console.
     *
     * @param string $line
     * @param mixed  ...$args
     *
     * @return Cli
     */
    public function write(string $str, ...$args): Cli
    {
        echo $this->colorize(sprintf($str, ...$args));

        return $this;
    }

    /**
     * Colorize a message.
     *
     * @param string $message
     *
     * @return string
     */
    public function colorize(string $message): string
    {
        return $this->build($this->parse($message));
    }

    /**
     * Build message tree.
     *
     * @param string $message
     *
     * @return array
     */
    private function parse(string $message): array
    {
        $ptr = 0;
        $width = 5;
        $tmp = '';
        $tree = array();
        $len = strlen($message);
        $tags = implode('|', array_keys($this->styles));

        for (; $ptr < $len;) {
            $pattern = "/^(.{0,$width})?<(\/)?($tags)\b>/is";

            if (preg_match($pattern, substr($message, $ptr), $match)) {
                if ($tmp || $match[1]) {
                    $tree[] = $tmp.$match[1];
                }

                if ($match[2]) {
                    $stack = array();
                    for ($i = count($tree) - 1; $i >= 0; --$i) {
                        $item = $tree[$i];
                        if (is_array($item) && array_key_exists($match[3], $item) && !isset($item[$match[3]][0])) {
                            $tree[$i][$match[3]] += array_reverse($stack);
                            $tree = array_slice($tree, 0, $i + 1);
                            break;
                        } else {
                            $stack[] = $item;
                        }
                    }
                } else {
                    $node = &$tree[][$match[3]];
                    $node = array();
                }

                $tmp = '';
                $ptr += strlen($match[0]);
                $width = 5;
            } else {
                $tmp .= substr($message, $ptr, $width);
                $ptr += $width;
                $width += (int) ($width < 50);
            }
        }

        if ($tmp) {
            $tree[] = $tmp;
        }

        unset($node, $tmp);

        return $tree;
    }

    /**
     * Build message based on tree.
     *
     * @param array $tree
     *
     * @return string
     */
    private function build(array $tree): string
    {
        $out = '';

        foreach ($tree as $key => $node) {
            if (is_string($node)) {
                $out .= $node;
            } elseif (isset($this->styles[$key])) {
                $out .= $this->applyStyle($key, $this->build($node));
            } else {
                $out .= $this->build($node);
            }
        }

        return $out;
    }

    /**
     * Apply style to message.
     *
     * @param string $style
     * @param string $message
     *
     * @return string
     */
    private function applyStyle(string $style, string $message): string
    {
        list($fg, $bg, $options) = $this->styles[$style];
        $f = self::$availableForegroundColors[$fg] ?? null;
        $b = self::$availableBackgroundColors[$bg] ?? null;

        $setCodes = '';
        $unsetCodes = '';

        if (null !== $f) {
            $setCodes .= ';'.$f['set'];
            $unsetCodes .= ';'.$f['unset'];
        }

        if (null !== $b) {
            $setCodes .= ';'.$b['set'];
            $unsetCodes .= ';'.$b['unset'];
        }

        foreach ((array) $options as $option) {
            $o = self::$availableOptions[$option] ?? null;

            if (null !== $o) {
                $setCodes .= ';'.$o['set'];
                $unsetCodes .= ';'.$o['unset'];
            }
        }

        if (!$setCodes) {
            return $message;
        }

        return sprintf("\033[%sm%s\033[%sm", ltrim($setCodes, ';'), $message, ltrim($unsetCodes, ';'));
    }
}
