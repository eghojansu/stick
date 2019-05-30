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
    /** @var array */
    private $options = array();

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
        return count($this->options);
    }

    /**
     * {inheritdoc}.
     */
    public function getIterator()
    {
        return new \ArrayIterator(Fw::arrColumn($this->options, 'value'));
    }

    /**
     * {inheritdoc}.
     */
    public function has(string $option): bool
    {
        return isset($this->options[$option]);
    }

    /**
     * {inheritdoc}.
     */
    public function &get(string $option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option]['value'];
        }

        throw new \LogicException(sprintf('Option %s is not available.', $option));
    }

    /**
     * {inheritdoc}.
     */
    public function set(string $option, $value): Magic
    {
        $this->validate($option, $value);

        $opt = &$this->options[$option];

        if (is_array($value) && is_array($opt['value'])) {
            $opt['value'] = array_replace($opt['value'], $value);
        } else {
            $opt['value'] = $value;
        }

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function rem(string $option): Magic
    {
        unset($this->options[$option]);

        return $this;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
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
        return $this->options[$option]['required'] ?? false;
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
     * Returns true if option allowed.
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return bool
     */
    public function isAllowed(string $option, $value): bool
    {
        $allowed = $this->options[$option]['allowed'] ?? null;

        return $allowed ? in_array($value, $allowed) : true;
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
            $this->assignOption(
                $option,
                $default instanceof \Closure ? $default($this) : $default,
                'value',
                'default'
            );
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
            $this->assignOption($option, true, 'required');
        }

        return $this;
    }

    /**
     * Set optional options.
     *
     * @param string|array $options
     *
     * @return Option
     */
    public function setOptional($options): Option
    {
        foreach (Fw::split($options) as $option) {
            $this->assignOption($option, false, 'required');
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
            $this->assignOption($option, Fw::split($default), 'allowed');
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
            $this->assignOption(
                $option,
                array_map(function ($type) {
                    return str_replace('boolean', 'bool', $type);
                }, Fw::split($types)),
                'types'
            );
        }

        return $this;
    }

    /**
     * Alternative way to add option.
     *
     * @param string       $option
     * @param mixed        $default
     * @param string|array $type
     * @param bool         $required
     *
     * @return Option
     */
    public function add(
        string $option,
        $default = null,
        $type = null,
        bool $required = false
    ): Option {
        return $this
            ->setDefaults(array($option => $default))
            ->setTypes(array($option => $type ?? gettype($default)))
            ->setRequired($required ? $option : null);
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
            if (null === $value && $this->isRequired($option)) {
                throw new \LogicException(sprintf('Option required: %s.', $option));
            }

            $this->set($option, $value);
        }

        return $this;
    }

    /**
     * Assign option value.
     *
     * @param string $option
     * @param mixed  $value
     * @param string $keys
     */
    protected function assignOption(string $option, $value, string ...$keys): void
    {
        if (!isset($this->options[$option])) {
            $this->options[$option] = array(
                'allowed' => array(),
                'default' => null,
                'required' => false,
                'types' => array(),
                'value' => null,
            );
        }

        foreach ($keys as $key) {
            $this->options[$option][$key] = $value;
        }
    }

    /**
     * Returns true if option valid.
     *
     * @param string $option
     * @param mixed  $value
     */
    protected function validate(string $option, $value): void
    {
        if (!isset($this->options[$option])) {
            throw new \LogicException(sprintf('Option %s is not available.', $option));
        }

        $opt = $this->options[$option];

        if (!$this->isAllowed($option, $value)) {
            throw new \OutOfBoundsException(sprintf(
                'Option %s only allow these values: %s.',
                $option,
                Fw::csv($opt['allowed'])
            ));
        }

        $obj = is_object($value);

        foreach ($opt['types'] as $type) {
            if (is_callable($check = 'is_'.$type) && $check($value)) {
                return;
            }

            if ($obj && class_exists($type) && $value instanceof $type) {
                return;
            }
        }

        if ($opt['types']) {
            throw new \UnexpectedValueException(sprintf(
                'Option %s expect %s, given %s type.',
                $option,
                implode(' or ', $opt['types']),
                gettype($value)
            ));
        }
    }
}
