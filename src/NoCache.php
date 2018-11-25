<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 13:03
 */

namespace Fal\Stick;

/**
 * No cache implementation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class NoCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function seed(string $seed): CacheInterface
    {
        return $this;
    }

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
    public function set(string $key, CacheItem $value): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = ''): CacheInterface
    {
        return $this;
    }
}
