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

class Xcache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'xcache';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): string
    {
        return (string) xcache_get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface
    {
        xcache_set($key, $value, $ttl);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return xcache_unset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        xcache_unset_by_prefix($prefix . '.');

        return true;
    }
}
