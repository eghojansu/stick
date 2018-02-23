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

class Apc implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'apc';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): string
    {
        return (string) apc_fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface
    {
        apc_store($key, $value, $ttl);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return apc_delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        $info = apc_cache_info('user');
        if ($info && isset($info['cache_list']) && $info['cache_list']) {
            $regex = '/' . preg_quote($prefix, '/') . '.+' . preg_quote($suffix, '/') . '/';
            $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
            foreach ($info['cache_list'] as $item) {
                if (preg_match($regex, $item[$key])) {
                    apc_delete($item[$key]);
                }
            }
        }

        return true;
    }
}
