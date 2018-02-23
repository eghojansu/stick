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

class Wincache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'wincache';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): string
    {
        return (string) wincache_ucache_get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface
    {
        wincache_ucache_set($key, $value, $ttl);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return wincache_ucache_delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        $regex = '/' . preg_quote($prefix, '/') . '.+' . preg_quote($suffix, '/') . '/';
        $info = wincache_ucache_info();
        foreach ($info['ucache_entries'] as $item) {
            if (preg_match($regex, $item['key_name'])) {
                wincache_ucache_delete($item['key_name']);
            }
        }

        return true;
    }
}
