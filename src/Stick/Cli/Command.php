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

use Fal\Stick\Util\Common;

/**
 * Console command.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Command
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var callable
     */
    protected $code;

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var string
     */
    protected $help = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * Create command.
     *
     * @param string   $name
     * @param callable $name
     * @param string   $description
     *
     * @return Command
     */
    public static function create(
        string $name,
        callable $code,
        string $description = null
    ): Command {
        return (new static($name, $description))->setCode($code);
    }

    /**
     * Class constructor.
     *
     * @param string $name
     * @param string $description
     */
    public function __construct(string $name = null, string $description = null)
    {
        $this->setName($this->name ?? $name ?? preg_replace(
            '/:command$/',
            '',
            str_replace('_', ':', Common::snakeCase(Common::classname($this)))
        ));
        $this->setDescription($description ?? '');
        $this->configure();
    }

    /**
     * Returns the command name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the command name.
     *
     * @param string $name
     *
     * @return Command
     */
    public function setName(string $name): Command
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns handler.
     *
     * @return callable|null
     */
    public function getCode(): ?callable
    {
        return $this->code;
    }

    /**
     * Sets handler.
     *
     * @param callable $code
     *
     * @return Command
     */
    public function setCode(callable $code): Command
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Returns the command arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Add input argument.
     *
     * @param InputArgument $argument
     *
     * @return Command
     */
    public function addArgument(InputArgument $argument): Command
    {
        $this->arguments[$argument->getName()] = $argument;

        return $this;
    }

    /**
     * Sets the command argument.
     *
     * @param string      $name
     * @param string|null $description
     * @param mixed       $defaultValue
     * @param int         $valueRequirement
     *
     * @return Command
     */
    public function setArgument(
        string $name,
        string $description = null,
        $defaultValue = null,
        int $valueRequirement = 0
    ): Command {
        return $this->addArgument(new InputArgument(
            $name,
            $description,
            $defaultValue,
            $valueRequirement
        ));
    }

    /**
     * Returns the command options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Add input option.
     *
     * @param InputArgument $option
     *
     * @return Command
     */
    public function addOption(InputOption $option): Command
    {
        $this->options[$option->getName()] = $option;

        return $this;
    }

    /**
     * Sets the command option.
     *
     * @param string      $name
     * @param string|null $description
     * @param string|null $alias
     * @param mixed       $defaultValue
     * @param int         $valueRequirement
     *
     * @return Command
     */
    public function setOption(
        string $name,
        string $description = null,
        string $alias = null,
        $defaultValue = null,
        int $valueRequirement = 0
    ): Command {
        return $this->addOption(new InputOption(
            $name,
            $description,
            $alias,
            $defaultValue,
            $valueRequirement
        ));
    }

    /**
     * Returns the command help.
     *
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * Sets the command help.
     *
     * @param string $help
     *
     * @return Command
     */
    public function setHelp(string $help): Command
    {
        $this->help = $help;

        return $this;
    }

    /**
     * Returns the command description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the command description.
     *
     * @param string $description
     *
     * @return Command
     */
    public function setDescription(string $description): Command
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Run this command.
     *
     * @param Console $console
     * @param Input   $input
     *
     * @return int
     */
    public function run(Console $console, Input $input): int
    {
        $statusCode = $this->code ? ($this->code)($console, $input, $this) : $this->execute($console, $input);

        return is_numeric($statusCode) ? (int) $statusCode : 0;
    }

    /**
     * Create input for this command.
     *
     * @param array|null $arguments
     * @param array|null $options
     *
     * @return Input
     */
    public function createInput(array $arguments = null, array $options = null): Input
    {
        $resolvedArguments = array();
        $resolvedOptions = array();

        if (null === $arguments) {
            $arguments = array();
        }

        if (null === $options) {
            $options = array();
        }

        foreach ($this->arguments as $name => $argument) {
            $resolvedArguments[$name] = $this->resolveArgument($argument, $arguments);
        }

        foreach ($this->options as $name => $option) {
            $resolvedOptions[$name] = $this->resolveOption($option, $options);
        }

        return new Input($resolvedArguments, $resolvedOptions);
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
    }

    /**
     * Command logic.
     *
     * @param Console $console
     * @param Input   $input
     *
     * @return mixed
     */
    protected function execute(Console $console, Input $input)
    {
        throw new \LogicException('You must override the execute() method in the concrete command class.');
    }

    /**
     * Resolve positional input argument.
     *
     * @param InputArgument $argument
     * @param array         &$argv
     *
     * @return mixed
     */
    protected function resolveArgument(InputArgument $argument, array &$argv)
    {
        $reqValue = $argument->getValueRequirement();
        $value = $argument->getDefaultValue();

        if (($reqValue & InputArgument::IS_ARRAY) && $argv) {
            $value = array_values($argv);
            $argv = array();
        } elseif ($argv) {
            $value = array_shift($argv);
        }

        if (
            (null === $value || array() === $value) &&
            ($reqValue & InputArgument::IS_REQUIRED)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Argument "%s" is required',
                $argument->getName()
            ));
        }

        return $value;
    }

    /**
     * Resolve input option.
     *
     * @param InputOption $option
     * @param array       &$argv
     *
     * @return mixed
     */
    protected function resolveOption(InputOption $option, array &$argv)
    {
        $name = $option->getName();
        $alias = $option->getAlias();
        $reqValue = $option->getValueRequirement();
        $argv = array_values($argv);
        $argc = count($argv);
        $value = null;
        $found = false;

        for ($i = 0; $i < $argc; ++$i) {
            $arg = $argv[$i];

            // not an option?
            if ('-' !== $arg[0]) {
                continue;
            }

            // long option?
            if ('-' === $arg[1]) {
                list($argName, $argValue) = explode(
                    '=',
                    substr($arg, 2),
                    2
                ) + array(1 => null);

                // not found?
                if ($name !== $argName) {
                    continue;
                }

                // found
                $found = true;
                $value = $argValue;

                unset($argv[$i]);
                break;
            }

            // no alias?
            if (!$alias) {
                continue;
            }

            list($argName, $argValue) = explode(
                '=',
                substr($arg, 1),
                2
            ) + array(1 => null);
            $aliasPos = strpos($argName, $alias);

            // not found
            if (false === $aliasPos) {
                continue;
            }

            $found = true;
            $prefix = substr($argName, 0, $aliasPos);
            $suffix = substr($argName, $aliasPos + 1);

            // not assignment mode?
            if (null === $argValue) {
                // empty suffix = request consume next!
                $value = $suffix ?: null;
                $suffix = null;
            } elseif (!$suffix) {
                // assignment only for last option
                $value = $argValue;
                $suffix = null;
                $argValue = null;
            } else {
                // append back
                $argValue = '='.$argValue;
            }

            // should update?
            if ($prefix || $suffix) {
                $argv[$i] = '-'.$prefix.$suffix.$argValue;
            } else {
                unset($argv[$i]);
            }

            // already found, so break here
            break;
        }

        if ($reqValue & InputOption::VALUE_NONE) {
            return $found;
        }

        $reqArray = $reqValue & InputOption::VALUE_ARRAY;

        // consume next?
        if (null === $value) {
            for (++$i; $i < $argc; ++$i) {
                $arg = $argv[$i];

                // is another option?
                if ('-' === $arg[0]) {
                    break;
                }

                unset($argv[$i]);

                if ($reqArray) {
                    $value[] = $arg;
                } else {
                    $value = $arg;
                    break;
                }
            }
        }

        if (null === $value) {
            $value = $option->getDefaultValue();
        }

        if ($reqArray && !is_array($value)) {
            $value = is_string($value) ? array_map(
                'trim',
                preg_split('/,/', $value, 0, PREG_SPLIT_NO_EMPTY)
            ) : array();
        }

        if (
            (null === $value || array() === $value) &&
            ($reqValue & InputOption::VALUE_REQUIRED)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Option value "%s" is required',
                $name
            ));
        }

        return $value;
    }
}
