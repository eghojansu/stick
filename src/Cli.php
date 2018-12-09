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

namespace Fal\Stick;

/**
 * Console output helper.
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
     * @param string|null $str
     * @param mixed       ...$args
     *
     * @return Cli
     */
    public function writeln(string $str = null, ...$args): Cli
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
     * @param string      $message
     * @param string|null &$clearText
     *
     * @return string
     */
    public function colorize(string $message, string &$clearText = null): string
    {
        $offset = 0;
        $output = '';
        $length = strlen($message);
        preg_match_all('~<((\w+) | /(\w+)?)>~ix', $message, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $key => $match) {
            list($tag, $position) = $match;

            if ('/' === $tag[1]) {
                continue;
            }

            list($closeTag, $closePosition) = $matches[0][$key + 1] ?? array('</>', $length);

            $tmp = substr($message, $offset, $position - $offset);
            $output .= $tmp;
            $clearText .= $tmp;
            $offset = $position + strlen($tag);
            $part = substr($message, $offset, $closePosition - $offset);
            $offset += strlen($closeTag) + strlen($part);
            $style = $matches[1][$key][0];

            if (isset($this->styles[$style])) {
                $output .= $this->applyStyle($style, $part);
                $clearText .= $part;
            } else {
                $tmp = $tag.$part.$closeTag;
                $output .= $tmp;
                $clearText .= $tmp;
            }
        }

        if ($matches[0]) {
            $tmp = substr($message, $position + strlen($tag));
            $output .= $tmp;
            $clearText .= $tmp;
        } else {
            $output = $message;
            $clearText = $message;
        }

        return $output;
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
        list($foreground, $background, $options) = $this->styles[$style];
        $foregroundColor = self::$availableForegroundColors[$foreground] ?? null;
        $backgroundColor = self::$availableBackgroundColors[$background] ?? null;

        $setCodes = array($foregroundColor['set'] ?? null, $backgroundColor['set'] ?? null);
        $unsetCodes = array($foregroundColor['unset'] ?? null, $backgroundColor['unset'] ?? null);

        foreach ((array) $options as $option) {
            $color = self::$availableOptions[$option] ?? null;

            $setCodes[] = $color['set'] ?? null;
            $unsetCodes[] = $color['unset'] ?? null;
        }

        $set = implode(';', array_filter($setCodes));
        $unset = implode(';', array_filter($unsetCodes));

        if ($set) {
            return sprintf("\033[%sm%s\033[%sm", $set, $message, $unset);
        }

        return $message;
    }
}
