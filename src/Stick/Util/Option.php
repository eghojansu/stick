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

use Fal\Stick\Fw;
use Fal\Stick\Magic;

/**
 * Value store with data types.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Option extends Magic implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    private $defaults = array();

    /**
     * @var array
     */
    private $types = array();

    /**
     * @var array
     */
    private $allowed = array();

    /**
     * @var array
     */
    private $required = array();

    /**
     * Class constructor.
     *
     * @param array|array $defaults
     */
    public function __construct(array $defaults = null)
    {
        $this->setDefaults($defaults ?? array());
    }

    /**
     * {inheritdoc}.
     */
    public function count()
    {
        return count($this->defaults);
    }

    /**
     * {inheritdoc}.
     */
    public function getIterator()
    {
        $options = array();

        foreach ($this->defaults as $option => $value) {
            $options[$option] = $this->get($option);
        }

        return new \ArrayIterator($options);
    }

    /**
     * {inheritdoc}.
     */
    public function &get(string $option, $default = null)
    {
        if ($this->has($option)) {
            return $this->hive[$option];
        }

        throw new \LogicException(sprintf('Option %s is not available.', $option));
    }

    /**
     * {inheritdoc}.
     */
    public function set(string $option, $value): Magic
    {
        if ($this->has($option) && $this->isAllowed($option, $value) && $this->isValid($option, $value)) {
            $this->hive[$option] = is_array($value) && is_array($initial = $this->hive[$option]) ? array_replace($initial, $value) : $value;

            return $this;
        }

        throw new \LogicException(sprintf('Option %s is not available.', $option));
    }

    /**
     * {inheritdoc}.
     */
    public function rem(string $option): Magic
    {
        if ($this->has($option)) {
            $this->hive[$option] = $this->defaults[$option];
        }

        return $this;
    }

    /**
     * Returns true if option value allowed.
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return bool
     */
    public function isAllowed(string $option, $value): bool
    {
        if (($allowed = $this->allowed[$option] ?? null) && !in_array($value, $allowed)) {
            throw new \OutOfBoundsException(sprintf('Option %s only allow these values: %s.', $option, Fw::csv($allowed)));
        }

        return true;
    }

    /**
     * Returns true if option valid.
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return bool
     */
    public function isValid(string $option, $value): bool
    {
        if ($types = $this->types[$option] ?? null) {
            $success = 0;
            $obj = is_object($value);

            foreach ($types as $type) {
                if (is_callable($check = 'is_'.$type)) {
                    $success += (int) $check($value);
                } elseif ($obj && class_exists($type)) {
                    $success += (int) ($value instanceof $type);
                }

                if ($success > 0) {
                    break;
                }
            }

            if (!$success) {
                throw new \UnexpectedValueException(sprintf('Option %s expect %s, given %s type.', $option, implode(' or ', $types), gettype($value)));
            }

            return true;
        }

        return true;
    }

    /**
     * Returns true if option required.
     *
     * @param string $option
     *
     * @return bool
     */
    public function isRequired(string $option): bool
    {
        return isset($this->required[$option]) && $this->required[$option];
    }

    /**
     * Returns true if option optional.
     *
     * @param string $option
     *
     * @return bool
     */
    public function isOptional(string $option): bool
    {
        return !$this->isRequired($option);
    }

    /**
     * Returns defaults.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Set options default.
     *
     * @param array $options
     *
     * @return Option
     */
    public function setDefaults(array $options): Option
    {
        foreach ($options as $option => $default) {
            $this->defaults[$option] = $this->hive[$option] = $default instanceof \Closure ? $default($this) : $default;
        }

        return $this;
    }

    /**
     * Set allowed value.
     *
     * @param array $options
     *
     * @return Option
     */
    public function setAllowed(array $options): Option
    {
        foreach ($options as $option => $default) {
            $this->allowed[$option] = Fw::split($default);
        }

        return $this;
    }

    /**
     * Set allowed types.
     *
     * @param array $options
     *
     * @return Option
     */
    public function setTypes(array $options): Option
    {
        foreach ($options as $option => $types) {
            $this->types[$option] = array_map(function ($type) {
                return str_replace('boolean', 'bool', $type);
            }, Fw::split($types));
        }

        return $this;
    }

    /**
     * Set required options.
     *
     * @param string|array $options
     *
     * @return Option
     */
    public function setRequired($options): Option
    {
        foreach (Fw::split($options) as $option) {
            $this->required[$option] = true;
        }

        return $this;
    }

    /**
     * Set optionals options.
     *
     * @param string|array $options
     *
     * @return Option
     */
    public function setOptionals($options): Option
    {
        foreach (Fw::split($options) as $option) {
            $this->required[$option] = false;
        }

        return $this;
    }

    /**
     * Alternative way to set option.
     *
     * @param string       $option
     * @param mixed        $default
     * @param string|array $type
     * @param bool         $required
     *
     * @return Option
     */
    public function add(string $option, $default = null, $type = null, bool $required = false): Option
    {
        return $this->setDefaults(array(
            $option => $default,
        ))->setTypes(array(
            $option => $type ?? gettype($default),
        ))->setRequired(array(
            $option => $required,
        ));
    }

    /**
     * Resolve options.
     *
     * @param array $options
     *
     * @return Option
     */
    public function resolve(array $options): Option
    {
        foreach ($options as $option => $value) {
            if (array_key_exists($option, $this->defaults)) {
                if (null === $value && ($this->required[$option] ?? false)) {
                    throw new \LogicException(sprintf(sprintf('Option required: %s.', $option)));
                }

                $this->set($option, $value);
            } else {
                throw new \LogicException(sprintf('Not an option: %s.', $option));
            }
        }

        return $this;
    }
}
