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

namespace Fal\Stick\Cli;

use Fal\Stick\Cli\Commands\GenerateMapperCommand;
use Fal\Stick\Cli\Commands\HelpCommand;
use Fal\Stick\Cli\Commands\ListCommand;
use Fal\Stick\Fw;
use Fal\Stick\Util\Option;

/**
 * Console helper.
 *
 * Some source taken from Symfony/Console.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Console
{
    /**
     * @var array
     */
    public static $availableForegroundColors = array(
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
    public static $availableBackgroundColors = array(
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
    public static $availableOptions = array(
        'bold' => array('set' => 1, 'unset' => 22),
        'underscore' => array('set' => 4, 'unset' => 24),
        'blink' => array('set' => 5, 'unset' => 25),
        'reverse' => array('set' => 7, 'unset' => 27),
        'conceal' => array('set' => 8, 'unset' => 28),
    );

    /**
     * Terminal width.
     *
     * @var int
     */
    protected static $width;

    /**
     * Terminal height.
     *
     * @var int
     */
    protected static $height;

    /**
     * @var array
     */
    protected $styles = array(
        'error' => array('white', 'red', null),
        'info' => array('green', null, null),
        'comment' => array('yellow', null, null),
        'question' => array('black', 'cyan', null),
    );

    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var Option
     */
    public $app;

    /**
     * @var array
     */
    protected $commands = array();

    /**
     * @var array
     */
    protected $routes = array();

    /**
     * @var bool
     */
    protected $dry = true;

    /**
     * Class constructor.
     *
     * @param Fw         $fw
     * @param array|null $app
     */
    public function __construct(Fw $fw, array $app = null)
    {
        $this->fw = $fw;
        $this->app = (new Option())
            ->add('name', Fw::PACKAGE, 'string', true)
            ->add('version', Fw::VERSION, 'string', true)
            ->add('default_command', 'help', 'string', true)
            ->resolve($app ?? array());
    }

    /**
     * Run console.
     *
     * @return int
     */
    public function run(): int
    {
        $found = $this->findCommand($this->app->default_command);
        $options = $this->fw->get('GET') ?? array();
        $arguments = $found['arguments'];
        $name = $found['command'];
        $command = $this->getCommand($name);

        try {
            $input = (new Input())->resolve($command, $arguments, $options);

            if ($input->hasOption('v', 'version')) {
                $this->writeln(sprintf(
                    '<info>%s</> <comment>%s</>',
                    $this->app->name,
                    $this->app->version
                ));

                return 0;
            }

            if (
                'help' !== $name &&
                $input->hasOption('h', 'help') &&
                $this->hasCommand('help')
            ) {
                $command = $this->commands['help'];
                $input->resolve($command, array($name), array());
            }

            return $command->run($this, $input);
        } catch (\Throwable $e) {
            $this->writeln('<error>An error occured</>');
            $this->writeln();
            $this->writeln(sprintf('  <comment>%s</>', get_class($e)));
            $this->writeln(sprintf('  <info>%s</>', wordwrap(
                $e->getMessage(),
                $this->getWidth() - 5
            )));

            return 1;
        }
    }

    /**
     * Returns registered command routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Find command from fw path.
     *
     * @param string|null $default
     *
     * @return array|null
     */
    public function findCommand(string $default = null): ?array
    {
        if ($this->dry) {
            $this->dry = false;

            $this->addCommands($this->getDefaultCommands());
        }

        $path = urldecode($this->fw->get('PATH'));

        foreach ($this->routes as $pattern => $command) {
            if (null !== $arguments = $this->fw->routeMatch($path, $pattern)) {
                return compact('command', 'arguments');
            }
        }

        return $default ? array(
            'command' => $default,
            'arguments' => array(),
        ) : null;
    }

    /**
     * Add command.
     *
     * @param Command $command
     *
     * @return Console
     */
    public function add(Command $command): Console
    {
        $name = $command->getName();
        $path = '/'.$name.rtrim('/@'.implode('/@', array_keys($command->getArguments())), '/@');

        $this->routes[$path] = $name;
        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * Add commands.
     *
     * @param array $commands
     *
     * @return Console
     */
    public function addCommands(array $commands): Console
    {
        foreach ($commands as $command) {
            $this->add($command);
        }

        return $this;
    }

    /**
     * Returns true if command exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Returns command.
     *
     * @param string $name
     *
     * @return Command
     */
    public function getCommand(string $name): Command
    {
        if ($this->hasCommand($name)) {
            // reserved options:
            //   -h, --help,
            //   -v, --version
            return $this->commands[$name]
                ->addOption('help', 'Display command help', 'h')
                ->addOption('version', 'Display application version', 'v');
        }

        throw new \LogicException(sprintf('Command not found: %s.', $name));
    }

    /**
     * Returns commands list.
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
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
     * @return Console
     */
    public function setStyle(string $name, string $foreground = null, string $background = null, array $options = null): Console
    {
        $this->styles[$name] = array(
            isset(self::$availableForegroundColors[$foreground]) ? $foreground : null,
            isset(self::$availableBackgroundColors[$background]) ? $background : null,
            null,
        );

        if ($options) {
            foreach ($options as $option) {
                if (isset(self::$availableOptions[$option])) {
                    $this->styles[$name][2][] = $option;
                }
            }
        }

        return $this;
    }

    /**
     * Write to console with new line.
     *
     * @param string|null $text
     *
     * @return Console
     */
    public function writeln(string $text = null): Console
    {
        return $this->write($text.PHP_EOL);
    }

    /**
     * Write to console.
     *
     * @param string $text
     *
     * @return Console
     */
    public function write(string $text): Console
    {
        echo trim($text) ? $this->colorize($text) : $text;

        return $this;
    }

    /**
     * Colorize a text.
     *
     * @param string      $text
     * @param string|null &$clearText
     *
     * @return string
     */
    public function colorize(string $text, string &$clearText = null): string
    {
        preg_match_all('~<((\w+) | /(\w+)?)>~ix', $text, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0]) {
            $offset = 0;
            $output = '';
            $length = strlen($text);

            foreach ($matches[0] as $key => $match) {
                list($tag, $position) = $match;

                if ('/' === $tag[1]) {
                    continue;
                }

                list($closeTag, $closePosition) = $matches[0][$key + 1] ?? array('</>', $length);

                $tmp = substr($text, $offset, $position - $offset);
                $output .= $tmp;
                $clearText .= $tmp;
                $offset = $position + strlen($tag);
                $part = substr($text, $offset, $closePosition - $offset);
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

            $tmp = substr($text, $position + strlen($tag));
            $output .= $tmp;
            $clearText .= $tmp;
        } else {
            $output = $text;
            $clearText = $text;
        }

        return $output;
    }

    /**
     * Gets the terminal width.
     *
     * @return int
     *
     * @codeCoverageIgnore
     */
    public function getWidth(): int
    {
        $width = getenv('COLUMNS');

        if (false !== $width) {
            return (int) trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?? 80;
    }

    /**
     * Gets the terminal height.
     *
     * @return int
     *
     * @codeCoverageIgnore
     */
    public function getHeight(): int
    {
        $height = getenv('LINES');

        if (false !== $height) {
            return (int) trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?? 50;
    }

    /**
     * Apply style to text.
     *
     * @param string $style
     * @param string $text
     *
     * @return string
     */
    protected function applyStyle(string $style, string $text): string
    {
        $null = array('set' => null, 'unset' => null);

        list($foreground, $background, $options) = $this->styles[$style];
        $foregroundColor = self::$availableForegroundColors[$foreground] ?? $null;
        $backgroundColor = self::$availableBackgroundColors[$background] ?? $null;

        $setCodes = array($foregroundColor['set'], $backgroundColor['set']);
        $unsetCodes = array($foregroundColor['unset'], $backgroundColor['unset']);

        if ($options) {
            foreach ($options as $option) {
                $color = self::$availableOptions[$option];

                $setCodes[] = $color['set'];
                $unsetCodes[] = $color['unset'];
            }
        }

        if ($set = implode(';', array_filter($setCodes))) {
            return sprintf("\033[%sm%s\033[%sm", $set, $text, implode(';', array_filter($unsetCodes)));
        }

        return $text;
    }

    /**
     * Returns default commands.
     *
     * @return array
     */
    protected function getDefaultCommands(): array
    {
        return array(
            new HelpCommand(),
            new ListCommand(),
            new GenerateMapperCommand(),
        );
    }

    /**
     * Trying to find terminal dimension.
     *
     * @codeCoverageIgnore
     */
    protected static function initDimensions(): void
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int) $matches[1];
                self::$height = (int) ($matches[4] ?? $matches[2]);
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int) $dimensions[0];
                self::$height = (int) $dimensions[1];
            }
        } elseif ($sttyString = self::getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            } elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            }
        }
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return int[]|null An array composed of the width and the height or null if it could not be parsed
     *
     * @codeCoverageIgnore
     */
    protected static function getConsoleMode(): ?array
    {
        if (!\function_exists('proc_open')) {
            return null;
        }

        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, array('suppress_errors' => true));

        if (\is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                return array((int) $matches[2], (int) $matches[1]);
            }
        }
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     *
     * @return string|null
     *
     * @codeCoverageIgnore
     */
    protected static function getSttyColumns(): ?string
    {
        if (!\function_exists('proc_open')) {
            return null;
        }

        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, array('suppress_errors' => true));

        if (\is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $info;
        }
    }
}
