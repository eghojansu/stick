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
 * Apc cache.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Apc implements CacheInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * Class constructor.
     *
     * @param string|null $prefix
     */
    public function __construct(string $prefix = null)
    {
        $this->prefix = $prefix ?? 'stick_cache_';
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return apc_exists($this->prefix.$key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CacheItem
    {
        $raw = apc_fetch($this->prefix.$key);

        return $raw ? CacheItem::fromString($raw) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CacheItem $value): bool
    {
        return apc_store($this->prefix.$key, $value->toString(), $value->getTtl());
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return apc_delete($this->prefix.$key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = null): int
    {
        $affected = 0;
        $pattern = '/^'.preg_quote($this->prefix, '/').'.+'.preg_quote($suffix ?? '', '/').'$/';

        foreach (new \APCIterator('user', $pattern) as $item) {
            $affected += (int) apc_delete($item['key']);
        }

        return $affected;
    }
}
