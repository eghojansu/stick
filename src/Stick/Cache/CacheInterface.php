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

namespace Fal\Stick\Cache;

/**
 * Cache interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface CacheInterface
{
    /**
     * Returns true if cache item exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Returns CacheItem if exists otherwise null.
     *
     * @param string $key
     *
     * @return CacheItem|null
     */
    public function get(string $key): ?CacheItem;

    /**
     * Assign cache item.
     *
     * @param string    $key
     * @param CacheItem $value
     *
     * @return bool
     */
    public function set(string $key, CacheItem $value): bool;

    /**
     * Returns true if cache cleared successfully.
     *
     * @param string $key
     *
     * @return bool
     */
    public function clear(string $key): bool;

    /**
     * Returns affected cache item count.
     *
     * @param string $suffix
     *
     * @return int
     */
    public function reset(string $suffix = null): int;
}
