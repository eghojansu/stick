<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Util;

/**
 * Console output helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Cli
{
    /**
     * @var array
     */
    protected static $availableForegroundColors = [
        'black'   => ['set' => 30, 'unset' => 39],
        'red'     => ['set' => 31, 'unset' => 39],
        'green'   => ['set' => 32, 'unset' => 39],
        'yellow'  => ['set' => 33, 'unset' => 39],
        'blue'    => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan'    => ['set' => 36, 'unset' => 39],
        'white'   => ['set' => 37, 'unset' => 39],
        'default' => ['set' => 39, 'unset' => 39],
    ];

    /**
     * @var array
     */
    protected static $availableBackgroundColors = [
        'black'   => ['set' => 40, 'unset' => 49],
        'red'     => ['set' => 41, 'unset' => 49],
        'green'   => ['set' => 42, 'unset' => 49],
        'yellow'  => ['set' => 43, 'unset' => 49],
        'blue'    => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan'    => ['set' => 46, 'unset' => 49],
        'white'   => ['set' => 47, 'unset' => 49],
        'default' => ['set' => 49, 'unset' => 49],
    ];

    /**
     * @var array
     */
    protected static $availableOptions = [
        'bold'       => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink'      => ['set' => 5, 'unset' => 25],
        'reverse'    => ['set' => 7, 'unset' => 27],
        'conceal'    => ['set' => 8, 'unset' => 28],
    ];

    /**
     * @var array
     */
    protected $styles = [
        'error'    => ['white', 'red', null],
        'info'     => ['green', null, null],
        'comment'  => ['yellow', null, null],
        'question' => ['black', 'cyan', null],
    ];

    /**
     * Get style definition.
     *
     * @param string $name
     *
     * @return array|null
     */
    public function getStyle($name)
    {
        return isset($this->styles[$name]) ? $this->styles[$name] : null;
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
    public function addStyle($name, $foreground = null, $background = null, array $options = null)
    {
        $style = [
            isset(static::$availableForegroundColors[$foreground]) ? $foreground : null,
            isset(static::$availableBackgroundColors[$background]) ? $background : null,
            null,
        ];

        foreach ((array) $options as $option) {
            if (isset(static::$availableOptions[$option])) {
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
     *
     * @return Cli
     */
    public function writeln($str = null)
    {
        echo $this->colorize($str).PHP_EOL;

        return $this;
    }

    /**
     * Write to console.
     *
     * @param string $line
     *
     * @return Cli
     */
    public function write($str)
    {
        echo $this->colorize($str);

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
    public function colorize($message, &$clearText = null)
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

            list($closeTag, $closePosition) = isset($matches[0][$key + 1]) ? $matches[0][$key + 1] : ['</>', $length];

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
    protected function applyStyle($style, $message)
    {
        list($foreground, $background, $options) = $this->styles[$style];
        $foregroundColor = isset(static::$availableForegroundColors[$foreground]) ? static::$availableForegroundColors[$foreground] : null;
        $backgroundColor = isset(static::$availableBackgroundColors[$background]) ? static::$availableBackgroundColors[$background] : null;

        $setCodes = [
            isset($foregroundColor['set']) ? $foregroundColor['set'] : null,
            isset($backgroundColor['set']) ? $backgroundColor['set'] : null,
        ];
        $unsetCodes = [
            isset($foregroundColor['unset']) ? $foregroundColor['unset'] : null,
            isset($backgroundColor['unset']) ? $backgroundColor['unset'] : null,
        ];

        foreach ((array) $options as $option) {
            $color = isset(static::$availableOptions[$option]) ? static::$availableOptions[$option] : null;

            $setCodes[] = isset($color['set']) ? $color['set'] : null;
            $unsetCodes[] = isset($color['unset']) ? $color['unset'] : null;
        }

        $set = implode(';', array_filter($setCodes));
        $unset = implode(';', array_filter($unsetCodes));

        if ($set) {
            return sprintf("\033[%sm%s\033[%sm", $set, $message, $unset);
        }

        return $message;
    }
}
