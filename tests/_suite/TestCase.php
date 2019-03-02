<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 06, 2019 09:57
 */

declare(strict_types=1);

namespace Fal\Stick\TestSuite;

use Fal\Stick\Cache\Filesystem;
use Fal\Stick\Cache\NoCache;
use Fal\Stick\Container\Container;
use Fal\Stick\Container\Definition;
use Fal\Stick\Database\Driver\PDOSqlite\Driver;
use Fal\Stick\Database\Mapper;
use Fal\Stick\Util;
use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $container;
    protected $driver;
    protected $cache;

    protected function prepare()
    {
        $this->container = new Container(array(
            'requestStack' => new Definition('Fal\\Stick\\Web\\RequestStackInterface', 'Fal\\Stick\\Web\\RequestStack'),
            'eventDispatcher' => new Definition('Fal\\Stick\\EventDispatcher\\EventDispatcherInterface', 'Fal\\Stick\\EventDispatcher\\EventDispatcher'),
            'session' => new Definition('Fal\\Stick\\Web\\Session\\SessionInterface', 'Fal\\Stick\\Web\\Session\\Session'),
            'router' => new Definition('Fal\\Stick\\Web\\Router\\RouterInterface', 'Fal\\Stick\\Web\\Router\\Router'),
            'logger' => new Definition('Fal\\Stick\\Logging\\LoggerInterface', 'Fal\\Stick\\Logging\\Logger'),
            'urlGenerator' => new Definition('Fal\\Stick\\Web\\UrlGeneratorInterface', 'Fal\\Stick\\Web\\UrlGenerator'),
            'translator' => new Definition('Fal\\Stick\\Translation\\TranslatorInterface', 'Fal\\Stick\\Translation\\Translator'),
            'userProvider' => new Definition('Fal\\Stick\\Web\\Security\\UserProviderInterface', 'Fal\\Stick\\Web\\Security\\InMemoryUserProvider'),
            'passwordEncoder' => new Definition('Fal\\Stick\\Web\\Security\\PasswordEncoderInterface', 'Fal\\Stick\\Web\\Security\\PlainPasswordEncoder'),
            'auth' => new Definition('Fal\\Stick\\Web\\Security\\Auth'),
        ));

        $this->requestStack->push(Request::create('/'));

        return $this;
    }

    protected function connect($cache = null)
    {
        if (!$cache) {
            $cache = new NoCache();
        }

        $this->cache = $cache;
        $this->driver = new Driver($this->cache, $this->logger);
        $this->container->set('cache', new Definition('Fal\\Stick\\Cache\\CacheInterface', $this->cache));
        $this->container->set('driver', new Definition('Fal\\Stick\\Database\\DriverInterface', $this->driver));

        return $this;
    }

    protected function connectWithCache()
    {
        $cache = new Filesystem(TEST_TEMP.'db-cache/');
        $cache->reset();

        return $this->connect($cache);
    }

    protected function buildSchema()
    {
        $this->driver->pdo()->exec(file_get_contents(TEST_FIXTURE.'files/schema_sqlite.sql'));

        return $this;
    }

    protected function initUser()
    {
        $this->driver->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');

        return $this;
    }

    protected function initFriends()
    {
        $this->driver->pdo()->exec('insert into friends (user_id, friend_id, level) values (1, 2, 3), (2, 3, 4)');

        return $this;
    }

    protected function fetch($sql)
    {
        return $this->driver->pdo()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }

    protected function mapper($table = null)
    {
        if (!$this->driver) {
            $this->prepare()->connect()->buildSchema();
        }

        return new Mapper($this->eventDispatcher, $this->driver, $table);
    }

    public function __get($name)
    {
        return $this->container->get($name);
    }
}
