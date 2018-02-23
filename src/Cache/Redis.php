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

use Redis as Ref;

class Redis implements CacheInterface
{
    /** @var Ref */
    protected $ref;

    /**
     * Class constructor
     *
     * @param string $host
     * @param int    $port
     * @param string $db
     */
    public function __construct(string $host, ?int $db = null, int $port = 0)
    {
        $this->ref = new Ref();
        try {
            $this->ref->connect($host, $port ?: 6379, 2);

            if (isset($db)) {
                $this->ref->select($db);
            }
        } catch(\Throwable $e) {
            throw new \LogicException('Failed connect with your redis server');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'redis';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): string
    {
        return (string) $this->ref->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $value, int $ttl = 0): CacheInterface
    {
        $this->ref->set($key, $value, array_filter(['ex'=>$ttl]));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return (bool) $this->ref->del($key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        $keys = $this->ref->keys($prefix . '*' . $suffix);
        foreach($keys as $key) {
            $this->ref->del($key);
        }

        return true;
    }
}
