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

/**
 * Cli input helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Input
{
    /**
     * Current command arguments.
     *
     * @var array
     */
    protected $arguments;

    /**
     * Current command options.
     *
     * @var array
     */
    protected $options;

    /**
     * Class constructor.
     *
     * @param array|null $arguments
     * @param array|null $options
     */
    public function __construct(array $arguments = null, array $options = null)
    {
        $this->arguments = $arguments ?? array();
        $this->options = $options ?? array();
    }

    /**
     * Returns argument value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getArgument(string $name)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }

        throw new \LogicException(sprintf('Argument not exists: %s.', $name));
    }

    /**
     * Returns arguments.
     *
     * @return arrray
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns option value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        throw new \LogicException(sprintf('Option not exists: %s.', $name));
    }

    /**
     * Returns options.
     *
     * @return arrray
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
