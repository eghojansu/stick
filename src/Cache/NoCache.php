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

class NoCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'nocache';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface
    {
        return $this;
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
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        return true;
    }
}
