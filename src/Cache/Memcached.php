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

use Memcached as Ref;

class Memcached implements CacheInterface
{
    /** @var Ref */
    protected $ref;

    /**
     * Class constructor
     *
     * @param string ...$servers
     */
    public function __construct(string ...$servers)
    {
        foreach ($servers as $server) {
            list($host, $port) = explode(':', $server) + [1=>11211];
            if (empty($this->ref)) {
                $this->ref = new Ref();
            }
            $this->ref->addServer($host, $port);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'memcached';
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
        $this->ref->set($key, $value, $ttl);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return $this->ref->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $prefix = '', string $suffix = ''): bool
    {
        $regex = '/' . preg_quote($prefix, '/') . '.+' . preg_quote($suffix, '/') . '/';
        foreach ($this->ref->getallkeys()?:[] as $key) {
            if (preg_match($regex, $key)) {
                $this->ref->delete($key);
            }
        }

        return true;
    }
}
