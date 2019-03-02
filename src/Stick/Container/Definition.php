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

namespace Fal\Stick\Container;

/**
 * Service definition.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Definition
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $use;

    /**
     * @var bool
     */
    protected $shared = true;

    /**
     * @var object
     */
    protected $instance;

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @var callable
     */
    protected $factory;

    /**
     * @var callable
     */
    protected $boot;

    /**
     * Class constructor.
     *
     * @param string $class
     * @param mixed  $definition
     */
    public function __construct(string $class, $definition = null)
    {
        $this->class = $class;

        if (is_callable($definition)) {
            $this->factory = $definition;
        } elseif (is_object($definition)) {
            $this->use = get_class($definition);
            $this->instance = $definition;
        } elseif (is_string($definition)) {
            $this->use = $definition;
        } elseif (is_array($definition)) {
            foreach ($definition as $key => $value) {
                if (method_exists($this, $set = 'set'.$key)) {
                    $this->$set($value);
                }
            }
        } else {
            $this->shared = false !== $definition;
        }
    }

    /**
     * Returns class namespace.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Sets class namespace.
     *
     * @param string $class
     *
     * @return Definition
     */
    public function setClass(string $class): Definition
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Returns use class namespace.
     *
     * @return string|null
     */
    public function getUse(): ?string
    {
        return $this->use;
    }

    /**
     * Sets use class namespace.
     *
     * @param string $use
     *
     * @return Definition
     */
    public function setUse(string $use): Definition
    {
        $this->use = $use;

        return $this;
    }

    /**
     * Returns true if definition is shared.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Sets shared definition.
     *
     * @param bool $shared
     *
     * @return Definition
     */
    public function setShared(bool $shared): Definition
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * Returns instance.
     *
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Sets object instance.
     *
     * @param object $instance
     *
     * @return Definition
     */
    public function setInstance($instance): Definition
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Returns arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Sets arguments.
     *
     * @param array $arguments
     *
     * @return Definition
     */
    public function setArguments(array $arguments): Definition
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Returns factory.
     *
     * @return callable|null
     */
    public function getFactory(): ?callable
    {
        return $this->factory;
    }

    /**
     * Sets factory.
     *
     * @param callable $factory
     *
     * @return Definition
     */
    public function setFactory(callable $factory): Definition
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Returns boot.
     *
     * @return callable|null
     */
    public function getBoot(): ?callable
    {
        return $this->boot;
    }

    /**
     * Sets boot.
     *
     * @param callable $boot
     *
     * @return Definition
     */
    public function setBoot(callable $boot): Definition
    {
        $this->boot = $boot;

        return $this;
    }
}
