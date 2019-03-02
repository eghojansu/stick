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
 * Container interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface ContainerInterface
{
    /**
     * Grab callable expression.
     *
     * @param string $expression
     *
     * @return mixed
     */
    public function grab(string $expression);

    /**
     * Execute callback with arguments auto-resolved.
     *
     * @param callable   $callback
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function call(callable $callback, array $arguments = null);

    /**
     * Returns true if service exists.
     *
     * @param string $class
     *
     * @return bool
     */
    public function has(string $class): bool;

    /**
     * Returns class instance object.
     *
     * @param string     $class
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function get(string $class, array $arguments = null);

    /**
     * Sets class instance object.
     *
     * @param string     $class
     * @param Definition $definition
     *
     * @return ContainerInterface
     */
    public function set(string $class, Definition $definition): ContainerInterface;

    /**
     * Returns true if parameters exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter(string $name): bool;

    /**
     * Returns parameters value if exists, otherwise returns null.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name);

    /**
     * Sets parameter value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return ContainerInterface
     */
    public function setParameter(string $name, $value): ContainerInterface;

    /**
     * Returns parameters.
     *
     * @return array
     */
    public function getParameters(): array;
}
