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
 * Provide magic access to children class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class Magic implements \ArrayAccess
{
    /**
     * Returns true if member exists.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract public function exists(string $key): bool;

    /**
     * Returns member value.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function &get(string $key);

    /**
     * Sets member value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Magic
     */
    abstract public function set(string $key, $val): Magic;

    /**
     * Remove member.
     *
     * @param string $key
     *
     * @return Magic
     */
    abstract public function clear(string $key): Magic;

    /**
     * Provide checking member as array.
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * Provide retrieving member as array.
     */
    public function &offsetGet($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * Provide assigning member as array.
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Provide removing member as array.
     */
    public function offsetUnset($key)
    {
        $this->clear($key);
    }

    /**
     * Provide checking member as property.
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Provide retrieving member as property.
     */
    public function &__get($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * Provide assigning member as property.
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Provide removing member as property.
     */
    public function __unset($key)
    {
        $this->clear($key);
    }
}
