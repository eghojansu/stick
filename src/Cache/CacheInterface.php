<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Cache;

interface CacheInterface
{
    /**
     * Cache name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get cache
     *
     * @param  string $key
     * @return string
     */
    public function get(string $key): string;

    /**
     * Set cache
     *
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return  CacheInterface
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface;

    /**
     * Clear cache
     *
     * @param  string $key
     * @return bool
     */
    public function clear(string $key): bool;

    /**
     * Reset cache
     *
     * @param  string $prefix
     * @param  string $suffix
     * @return bool
     */
    public function reset(string $prefix = '', string $suffix = ''): bool;
}
