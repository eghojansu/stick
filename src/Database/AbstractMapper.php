<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Database;

use function Fal\Stick\icutafter;
use function Fal\Stick\istartswith;
use function Fal\Stick\snakecase;

abstract class AbstractMapper implements MapperInterface
{
    /** Pagination records perpage */
    const PERPAGE = 10;

    /** @var string */
    protected $table;

    /** @var bool */
    protected $loaded = false;

    /** @var array */
    protected $trigger = [];

    /**
     * {@inheritdoc}
     */
    public function findOne($filter = null, array $option = null, int $ttl = 0): ?MapperInterface
    {
        $res = $this->find($filter, ['limit'=>1] + (array) $option, $ttl);

        return $res[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(): MapperInterface
    {
        return $this->loaded ? $this->update() : $this->insert();
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function setTable(string $table = null): MapperInterface
    {
        $this->table = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $page = 1, $filter = null, array $option = null, int $ttl = 0): array
    {
        $use = (array) $option;
        $limit = $use['perpage'] ?? static::PERPAGE;
        $total = $this->count($filter, $option, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = [];
        $start = 0;
        $end = 0;
        $count = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->find($filter, compact('limit', 'offset') + $use, $ttl);
            $count = count($subset);
            $start = $offset + 1;
            $end = $offset + $count;
        }

        return compact('subset', 'total', 'count', 'pages', 'page', 'start', 'end');
    }

    /**
     * {@inheritdoc}
     */
    public function unloaded(): bool
    {
        return !$this->loaded;
    }

    /**
     * {@inheritdoc}
     */
    public function loaded(): bool
    {
        return $this->loaded;
    }

    /**
     * {@inheritdoc}
     */
    public function addTrigger(string $name, callable $func, bool $first = false): MapperInterface
    {
        if ($first && isset($this->trigger[$name])) {
            array_unshift($this->trigger[$name], $func);
        } else {
            $this->trigger[$name][] = $func;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onload(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_LOAD, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeinsert(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_BEFOREINSERT, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function afterinsert(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_AFTERINSERT, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function oninsert(callable $func, bool $first = false): MapperInterface
    {
        return $this->afterinsert($func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeupdate(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_BEFOREUPDATE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function afterupdate(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_AFTERUPDATE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function onupdate(callable $func, bool $first = false): MapperInterface
    {
        return $this->afterupdate($func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function beforesave(callable $func, bool $first = false): MapperInterface
    {
        return $this
            ->addTrigger(MapperInterface::EVENT_BEFOREINSERT, $func, $first)
            ->addTrigger(MapperInterface::EVENT_BEFOREUPDATE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function aftersave(callable $func, bool $first = false): MapperInterface
    {
        return $this
            ->addTrigger(MapperInterface::EVENT_AFTERINSERT, $func, $first)
            ->addTrigger(MapperInterface::EVENT_AFTERUPDATE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function onsave(callable $func, bool $first = false): MapperInterface
    {
        return $this->aftersave($func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function beforedelete(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_BEFOREDELETE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function afterdelete(callable $func, bool $first = false): MapperInterface
    {
        return $this->addTrigger(MapperInterface::EVENT_AFTERDELETE, $func, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function ondelete(callable $func, bool $first = false): MapperInterface
    {
        return $this->afterdelete($func, $first);
    }

    /**
     * Trigger event if exists
     *
     * Boolean true needs to be returned if you need to give control back to the caller.
     *
     * @param  string $event
     * @param  array  $args
     *
     * @return bool
     */
    public function trigger(string $event, array $args = []): bool
    {
        if (!isset($this->trigger[$event])) {
            return false;
        }

        foreach ($this->trigger[$event] as $func) {
            if (call_user_func_array($func, $args) === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Shift field args
     *
     * @param  string $field
     * @param  array  $args
     *
     * @return array
     */
    protected function fieldArgs(string $field, array $args): array
    {
        if ($args) {
            $first = array_shift($args);
            array_unshift($args, [$field => $first]);
        }

        return $args;
    }

    /**
     * Convenience method for checking property value
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Convenience method for retrieving property value
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $val =& $this->get($offset);

        return $val;
    }

    /**
     * Convenience method for assigning property value
     *
     * @param  string $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method for removing property value
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * Alias of __offsetExists
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Alias of __offsetGet
     */
    public function &__get($name)
    {
        $val =& $this->offsetGet($name);

        return $val;
    }

    /**
     * Alias of __offsetSet
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Alias of __offsetUnset
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * Proxy to mapper method
     * Example:
     *   findOneByUsername('foo') = findOne(['username'=>'foo'])
     *   findByUsername('foo') = findAll(['username'=>'foo'])
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        if (istartswith('findoneby', $method)) {
            $field = snakecase(icutafter('findoneby', $method));
            $args = $this->fieldArgs($field, $args);

            return $this->findOne(...$args);
        } elseif (istartswith('findby', $method)) {
            $field = snakecase(icutafter('findby', $method));
            $args = $this->fieldArgs($field, $args);

            return $this->find(...$args);
        } elseif (istartswith('loadby', $method)) {
            $field = snakecase(icutafter('loadby', $method));
            $args = $this->fieldArgs($field, $args);

            return $this->load(...$args);
        }

        throw new \BadMethodCallException(
            'Call to undefined method ' . static::class . '::' . $method
        );
    }
}
