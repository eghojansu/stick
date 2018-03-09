<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

class Cache
{
    /** @var string */
    protected $dsn;

    /** @var string */
    protected $prefix;

    /** @var string Fallback dir */
    protected $temp;

    /** @var string Cache id */
    protected $cache;

    /** @var Redis|Memcached|string */
    protected $cacheRef;

    /**
     * Class constructor
     *
     * @param string $dsn
     * @param string $prefix
     * @param string $temp
     */
    public function __construct(string $dsn, string $prefix, string $temp)
    {
        $this->temp = $temp;
        $this->setPrefix($prefix);
        $this->setDsn($dsn);
    }

    /**
     * Check and get cache with hash creation
     *
     * @param  string|null &$hash
     * @param  mixed       &$cached
     * @param  string      $suffix
     * @param  mixed       ...$args
     *
     * @return bool
     */
    public function isCached(
        string &$hash = null,
        &$cached = null,
        string $suffix = 'cache',
        ...$args
    ): bool {
        if ($hash === null) {
            if (!$args) {
                throw new \LogicException('Please provide a valid hash.');
            }

            $str = '';
            foreach ($args as $arg) {
                $str .= (is_string($arg) ? $arg : stringify($arg)) . '.';
            }

            $hash = hash(rtrim($str, '.')) . '.' . $suffix;
        }

        $exists = $this->exists($hash);
        if ($exists) {
            $cached = $this->get($hash);
        }

        return $exists;
    }

    /**
     * Check cache item by key
     *
     * @param  string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $this->load();

        $ndx = $this->prefix . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                return apc_exists($ndx);
            case 'apcu':
                return apcu_exists($ndx);
            case 'folder':
                return (bool) $this->parse($key, read($this->cacheRef . $ndx));
            case 'memcached':
                return (bool) $this->cacheRef->get($ndx);
            case 'redis':
                return $this->cacheRef->exists($ndx);
            case 'wincache':
                return wincache_ucache_exists($ndx);
            case 'xcache':
                return xcache_exists($ndx);
            default:
                return false;
        }
    }

    /**
     * Get cache item content
     *
     * @param  string $key
     *
     * @return array
     */
    public function get(string $key): array
    {
        $this->load();

        $ndx = $this->prefix . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                $raw = apc_fetch($ndx);
                break;
            case 'apcu':
                $raw = apcu_fetch($ndx);
                break;
            case 'folder':
                $raw = read($this->cacheRef . $ndx);
                break;
            case 'memcached':
                $raw = $this->cacheRef->get($ndx);
                break;
            case 'redis':
                $raw = $this->cacheRef->get($ndx);
                break;
            case 'wincache':
                $raw = wincache_ucache_get($ndx);
                break;
            case 'xcache':
                $raw = xcache_get($ndx);
                break;
            default:
                $raw = null;
                break;
        }

        return $this->parse($key, (string) $raw);
    }

    /**
     * Set cache item content
     *
     * @param  string $key
     * @param  mixed $val
     * @param  int $ttl
     *
     * @return Cache
     */
    public function set(string $key, $val, int $ttl = 0): Cache
    {
        $this->load();

        $ndx = $this->prefix . '.' . $key;
        $content = $this->compact($val, (int) microtime(true), $ttl);

        switch ($this->cache) {
            case 'apc':
                apc_store($ndx, $content, $ttl);
                break;
            case 'apcu':
                apcu_store($ndx, $content, $ttl);
                break;
            case 'folder':
                write($this->cacheRef . str_replace(['/', '\\'], '', $ndx), $content);
                break;
            case 'memcached':
                $this->cacheRef->set($ndx, $content);
                break;
            case 'redis':
                $this->cacheRef->set($ndx, $content, array_filter(['ex'=>$ttl]));
                break;
            case 'wincache':
                wincache_ucache_set($ndx, $content, $ttl);
                break;
            case 'xcache':
                xcache_set($ndx, $content, $ttl);
                break;
        }

        return $this;
    }

    /**
     * Remove cache item
     *
     * @param  string $key
     *
     * @return bool
     */
    public function clear(string $key): bool
    {
        $this->load();

        $ndx = $this->prefix . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                return apc_delete($ndx);
            case 'apcu':
                return apcu_delete($ndx);
            case 'folder':
                return delete($this->cacheRef . $ndx);
            case 'memcached':
                return $this->cacheRef->delete($ndx);
            case 'redis':
                return (bool) $this->cacheRef->del($ndx);
            case 'wincache':
                return wincache_ucache_delete($ndx);
            case 'xcache':
                return xcache_unset($ndx);
            default:
                return false;
        }
    }

    /**
     * Reset cache
     *
     * @param  string $suffix
     *
     * @return bool
     */
    public function reset(string $suffix = ''): bool
    {
        $this->load();

        $regex = '/' . preg_quote($this->prefix, '/') . '\..+' . preg_quote($suffix, '/') . '/';

        switch ($this->cache) {
            case 'apc':
                $info = apc_cache_info('user');
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    foreach ($info['cache_list'] as $item) {
                        if (preg_match($regex, $item[$key])) {
                            apc_delete($item[$key]);
                        }
                    }
                }

                return true;
            case 'apcu':
                $info = apcu_cache_info(false);
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    foreach ($info['cache_list'] as $item) {
                        if (preg_match($regex, $item[$key])) {
                            apcu_delete($item[$key]);
                        }
                    }
                }

                return true;
            case 'folder':
                $files = glob($this->cacheRef . $this->prefix . '*' . $suffix) ?: [];
                foreach ($files as $file) {
                    unlink($file);
                }

                return true;
            case 'memcached':
                $keys = preg_grep($regex, $this->cacheRef->getallkeys());
                foreach ($keys as $key) {
                    $this->cacheRef->delete($key);
                }

                return true;
            case 'redis':
                $keys = $this->cacheRef->keys($this->prefix . '*' . $suffix);
                foreach($keys as $key) {
                    $this->cacheRef->del($key);
                }

                return true;
            case 'wincache':
                $info = wincache_ucache_info();
                $keys = preg_grep($regex, array_column($info['ucache_entries'], 'key_name'));
                foreach ($keys as $key) {
                    wincache_ucache_delete($key);
                }

                return true;
            case 'xcache':
                xcache_unset_by_prefix($this->prefix . '.');

                return true;
            default:

                return true;
        }
    }

    /**
     * Get used cache
     *
     * @return array
     */
    public function def(): array
    {
        $this->load();

        return [$this->cache, $this->cacheRef];
    }

    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     * @return Cache
     */
    public function setPrefix(string $prefix): Cache
    {
        if ($prefix) {
            $this->prefix = $prefix;
        }

        return $this;
    }

    /**
     * Get dsn
     *
     * @return string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * Set dsn
     *
     * @param string $dsn
     * @return $this
     */
    public function setDsn(string $dsn): Cache
    {
        $this->dsn = trim($dsn);
        $this->cache = null;
        $this->cacheRef = null;

        return $this;
    }

    /**
     * Load cache by dsn
     *
     * @return void
     */
    protected function load(): void
    {
        $dsn = $this->dsn;

        if ($this->cache || !$dsn) {
            return;
        }

        $parts = array_map('trim', explode('=', $dsn) + [1 => '']);
        $auto = '/^(apc|apcu|wincache|xcache)/';
        $grep = preg_grep($auto, array_map('strtolower', get_loaded_extensions()));

        // Fallback to filesystem cache
        $fallback = 'folder';
        $folder = $this->temp;

        if ($parts[0] === 'redis' && $parts[1] && extension_loaded('redis')) {
            list($host, $port, $db) = explode(':', $parts[1]) + [1=>0, 2=>null];

            $this->cache = 'redis';
            $this->cacheRef = new \Redis();

            try {
                $this->cacheRef->connect($host, $port ?: 6379, 2);

                if ($db) {
                    $this->cacheRef->select($db);
                }
            } catch(\Throwable $e) {
                $this->cache = $fallback;
                $this->cacheRef = $folder;
            }
        } elseif ($parts[0] === 'memcached' && $parts[1] && extension_loaded('memcached')) {
            $servers = explode(';', $parts[1]);

            $this->cache = 'memcached';
            $this->cacheRef = new \Memcached();

            foreach ($servers as $server) {
                list($host, $port) = explode(':', $server) + [1=>11211];

                $this->cacheRef->addServer($host, $port);
            }
        } elseif ($parts[0] === 'folder' && $parts[1]) {
            $this->cache = 'folder';
            $this->cacheRef = $parts[1];
        } elseif (preg_match($auto, $dsn, $parts)) {
            $this->cache = $parts[1];
            $this->cacheRef = null;
        } elseif (strtolower($dsn) === 'auto' && $grep) {
            $this->cache = current($grep);
            $this->cacheRef = null;
        } else {
            $this->cache = $fallback;
            $this->cacheRef = $folder;
        }

        if ($fallback === $this->cache) {
            mkdir($this->cacheRef);
        }
    }

    /**
     * Compact cache content and time
     *
     * @param  mixed $content
     * @param  int    $time
     * @param  int    $ttl
     *
     * @return string
     */
    protected function compact($content, int $time, int $ttl): string
    {
        return serialize([$content, $time, $ttl]);
    }

    /**
     * Parse raw cache data
     *
     * @param  string $key
     * @param  string $raw
     *
     * @return void
     */
    protected function parse(string $key, string $raw): array
    {
        if ($raw) {
            list($val, $time, $ttl) = (array) unserialize($raw);

            if (0 === $ttl || $time+$ttl > microtime(true)) {
                return [$val, $time, $ttl];
            }

            $this->clear($key);
        }

        return [];
    }
}
