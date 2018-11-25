<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 12:57
 */

namespace Fal\Stick;

/**
 * Cache wrapper interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface CacheInterface
{
    /**
     * Sets cache prefix.
     *
     * @param string $seed
     *
     * @return CacheInterface
     */
    public function seed(string $seed): CacheInterface;

    /**
     * Returns true if cache item exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Returns cache item.
     *
     * @param string $key
     *
     * @return CacheItem|null
     */
    public function get(string $key): ?CacheItem;

    /**
     * Returns true if cache item successfully saved.
     *
     * @param string    $key
     * @param CacheItem $value
     *
     * @return bool
     */
    public function set(string $key, CacheItem $value): bool;

    /**
     * Returns true if cache successfull cleared.
     *
     * @param string $key
     *
     * @return bool
     */
    public function clear(string $key): bool;

    /**
     * Reset cache.
     *
     * @param string $suffix
     *
     * @return CacheInterface
     */
    public function reset(string $suffix = ''): CacheInterface;
}
