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
 * No cache.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class NoCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CacheItem
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CacheItem $item): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = null): int
    {
        return 0;
    }
}
