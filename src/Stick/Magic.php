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
 * Magic class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class Magic implements \ArrayAccess
{
    /**
     * Returns true if hive member exists.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract public function has(string $key): bool;

    /**
     * Returns hive member if exists, otherwise returns the defaults.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function &get(string $key);

    /**
     * Assign hive member.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Magic
     */
    abstract public function set(string $key, $value): Magic;

    /**
     * Remove hive member.
     *
     * @param string $key
     *
     * @return Magic
     */
    abstract public function rem(string $key): Magic;

    /**
     * Allow check hive member as class property.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Allow retrieve hive member as class property.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &__get($key)
    {
        $var = &$this->get($key);

        return $var;
    }

    /**
     * Allow assign hive member as class property.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Allow remove hive member as class property.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->rem($key);
    }

    /**
     * Allow check hive member as array.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Allow retrieve hive member as array.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &offsetGet($key)
    {
        $var = &$this->get($key);

        return $var;
    }

    /**
     * Allow assign hive member as array.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Allow remove hive member as array.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->rem($key);
    }
}
