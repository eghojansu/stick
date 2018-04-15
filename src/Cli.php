<?php declare(strict_types=1);

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
 * Cli helper
 *
 * Color output from: https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 */
class Cli
{
    /** @var int Console width */
    protected $width;

    /** @var array Foreground color */
    protected $fgColors = [
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
    ];

    /** @var array Background color */
    protected $bgColors = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47',
    ];

    /** @var array Expression color map */
    protected $exprColorMap = [
        'success' => 'green',
        'warning' => 'yellow',
        'danger' => 'red',
        'info' => 'cyan',
    ];

    /**
     * Class constructor
     *
     * @param int $width
     */
    public function __construct(int $width = 80)
    {
        $this->setWidth($width);
    }

    /**
     * Get width
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Set width
     *
     * @param int $width
     * @return Cli
     */
    public function setWidth(int $width): Cli
    {
        $this->width = min($width, $this->getConsoleWidth());

        return $this;
    }

    /**
     * Get console width
     *
     * @return int
     */
    public function getConsoleWidth(): int
    {
        return 80;
    }

    /**
     * Update expr color map
     *
     * @param string $expr
     * @param string $color
     *
     * @return Cli
     */
    public function setExprColorMap(string $expr, string $color): Cli
    {
        $this->exprColorMap[$expr] = $color;

        return $this;
    }

    /**
     * Block success
     *
     * @param  string $line
     * @param  int    $newline
     *
     * @return Cli
     */
    public function success(string $line, int $newline = 1): Cli
    {
        return $this->block('Success!' . PHP_EOL . PHP_EOL . $line, 'white:success', $newline);
    }

    /**
     * Block danger
     *
     * @param  string $line
     * @param  int    $newline
     *
     * @return Cli
     */
    public function danger(string $line, int $newline = 1): Cli
    {
        return $this->block('!!!Alert!!!' . PHP_EOL . PHP_EOL . $line, 'white:danger', $newline);
    }

    /**
     * Block warning
     *
     * @param  string $line
     * @param  int    $newline
     *
     * @return Cli
     */
    public function warning(string $line, int $newline = 1): Cli
    {
        return $this->block('Warning:' . PHP_EOL . PHP_EOL . $line, 'white:warning', $newline);
    }

    /**
     * Block info
     *
     * @param  string $line
     * @param  int    $newline
     *
     * @return Cli
     */
    public function info(string $line, int $newline = 1): Cli
    {
        return $this->block('Info:' . PHP_EOL . PHP_EOL . $line, 'white:info', $newline);
    }

    /**
     * Write to console with new line
     *
     * @param  string      $line
     * @param  string|null $color   Foreground and background color, separated by colon
     * @param  int         $newline
     *
     * @return Cli
     */
    public function block(string $line, string $color = null, int $newline = 1): Cli
    {
        $lines = $this->parseLine($line, $this->width - 4);
        $lens = array_map('strlen', $lines);
        $max = max($lens);
        $fmt = $this->parseColor($color);
        $sep = str_repeat(' ', $this->width);

        $out = $fmt[0] . $fmt[1] . $sep . PHP_EOL;

        foreach ($lines as $single) {
            $out .= '  ' . str_pad($single, $this->width - 2) . PHP_EOL;
        }

        $out .= $sep . $fmt[2] . str_repeat(PHP_EOL, $newline);

        echo $out;

        return $this;
    }

    /**
     * Write to console with new line
     *
     * @param  string      $line
     * @param  string|null $color   Foreground and background color, separated by colon
     * @param  int         $newline
     *
     * @return Cli
     */
    public function writeln(string $line, string $color = null, int $newline = 1): Cli
    {
        $fmt = $this->parseColor($color);

        echo $fmt[0] . $fmt[1] . $line . $fmt[2] . str_repeat(PHP_EOL, $newline);

        return $this;
    }

    /**
     * Write to console
     *
     * @param  string      $line
     * @param  string|null $color
     *
     * @return Cli
     */
    public function write(string $line, string $color = null): Cli
    {
        return $this->writeln($line, $color, 0);
    }

    /**
     * Parse color
     *
     * @param  string|null $color
     *
     * @return array
     */
    protected function parseColor(string $color = null): array
    {
        if (!$color) {
            return array_fill(0, 3, '');
        }

        $x = explode(':', $color) + [1=>1];
        $fg = $this->exprColorMap[$x[0]] ?? $x[0];
        $bg = $this->exprColorMap[$x[1]] ?? $x[1];

        return [
            "\033[" . ($this->fgColors[$fg] ?? $this->fgColors['white']) . 'm',
            isset($this->bgColors[$bg]) ? "\033[" . $this->bgColors[$bg] . 'm' : '',
            "\033[0m"
        ];
    }

    /**
     * String line to array, after wrapped
     *
     * @param  string $line
     * @param  int    $width
     *
     * @return array
     */
    protected function parseLine(string $line, int $width): array
    {
        return explode("\n", wordwrap(preg_replace('/\r\n|\r/', "\n", $line), $width));
    }
}
