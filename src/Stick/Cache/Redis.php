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
 * Redis cache.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Redis implements CacheInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * Class constructor.
     *
     * @param string      $host
     * @param int         $port
     * @param int|null    $db
     * @param string|null $prefix
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $db = null, string $prefix = null)
    {
        $this->prefix = $prefix ?? 'stick_cache_';
        $this->redis = new \Redis();
        $this->redis->connect($host, $port, 2);

        if (null !== $db) {
            $this->redis->select($db);
        }
    }

    /**
     * Returns redis.
     *
     * @return Redis
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->redis->exists($this->prefix.$key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CacheItem
    {
        $raw = $this->redis->get($this->prefix.$key);

        return $raw ? CacheItem::fromString($raw) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CacheItem $value): bool
    {
        return $this->redis->set($this->prefix.$key, $value->toString(), array_filter(array('ex' => $value->getTtl())));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return (bool) $this->redis->del($this->prefix.$key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = null): int
    {
        $affected = 0;

        foreach ($this->redis->keys($this->prefix.'*'.$suffix) as $key) {
            $affected += (int) $this->redis->del($key);
        }

        return $affected;
    }
}
