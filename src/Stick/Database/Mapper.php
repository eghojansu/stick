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

namespace Fal\Stick\Database;

use Fal\Stick\Database\Event\AfterDeleteEvent;
use Fal\Stick\Database\Event\AfterSaveEvent;
use Fal\Stick\Database\Event\BeforeDeleteEvent;
use Fal\Stick\Database\Event\BeforeSaveEvent;
use Fal\Stick\Database\Event\LoadEvent;
use Fal\Stick\EventDispatcher\EventDispatcherInterface;
use Fal\Stick\Util;

/**
 * Database mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper implements \ArrayAccess, \Iterator, \Countable
{
    const ON_BEFORESAVE = 'mapper.beforesave';
    const ON_AFTERSAVE = 'mapper.aftersave';
    const ON_BEFOREDELETE = 'mapper.beforedelete';
    const ON_AFTERDELETE = 'mapper.afterdelete';
    const ON_LOAD = 'mapper.load';
    const PERPAGE = 10;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    public $options;

    /**
     * @var int
     */
    public $ttl = 60;

    /**
     * @var Row
     */
    protected $row;

    /**
     * @var Row[]
     */
    protected $rows = array();

    /**
     * @var int
     */
    protected $ptr = 0;

    /**
     * Class constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param DriverInterface          $driver
     * @param string|null              $name
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, DriverInterface $driver, string $name = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->driver = $driver;

        $this->setName($name ?? $this->name ?? Util::snakeCase(Util::className($this)));
        $this->initialize();
    }

    /**
     * {inheritdoc}.
     */
    public function __clone()
    {
        if ($this->rows) {
            $this->rows = array_map(function ($row) {
                return clone $row;
            }, $this->rows);
            $this->current();
        } else {
            $this->row = clone $this->row;
        }
    }

    /**
     * Handle custom method call.
     *
     * get{FieldName}
     * loadBy{FieldName} => load
     * findBy{FieldName} => first
     */
    public function __call($method, $arguments)
    {
        $call = null;
        $argument = null;
        $lmethod = strtolower($method);

        if ('get' === substr($lmethod, 0, 3)) {
            $call = 'get';
            $argument = Util::snakeCase(substr($method, 3));
        } elseif ('loadby' === substr($lmethod, 0, 6)) {
            $call = 'load';
            $argument = array(Util::snakeCase(substr($method, 6)) => array_shift($arguments));
        } elseif ('findby' === substr($lmethod, 0, 6)) {
            $call = 'first';
            $argument = array(Util::snakeCase(substr($method, 6)) => array_shift($arguments));
        } else {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s.', static::class, $method));
        }

        return $this->$call($argument, ...$arguments);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetUnset($key)
    {
        $this->clear($key);
    }

    /**
     * {inheritdoc}.
     */
    public function current()
    {
        $this->row = $this->rows[$this->ptr];

        if (!$this->row->loaded) {
            $this->row->loaded = true;

            $event = new LoadEvent($this);
            $this->eventDispatcher->dispatch(static::ON_LOAD, $event);
        }

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function key()
    {
        return $this->ptr;
    }

    /**
     * {inheritdoc}.
     */
    public function next()
    {
        ++$this->ptr;
    }

    /**
     * {inheritdoc}.
     */
    public function rewind()
    {
        $this->ptr = 0;
    }

    /**
     * {inheritdoc}.
     */
    public function valid()
    {
        return isset($this->rows[$this->ptr]);
    }

    /**
     * {inheritdoc}.
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Returns mapper name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets mapper name.
     *
     * @param string      $name
     * @param string|null $alias
     * @param array|null  $options
     * @param int|null    $ttl
     *
     * @return Mapper
     */
    public function setName(string $name, string $alias = null, array $options = null, int $ttl = null): Mapper
    {
        $this->row = $this->driver->schema($name, $options ?? $this->options, $ttl ?? $this->ttl);
        $this->row->alias = $alias ?? $this->alias;
        $this->name = $name;
        $this->reset();

        return $this;
    }

    /**
     * Returns mapper schema.
     *
     * @return Row
     */
    public function getSchema(): Row
    {
        return $this->row;
    }

    /**
     * Load mapper by finding match by clause.
     *
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return Mapper
     */
    public function load(array $clause = null, array $options = null, int $ttl = 0): Mapper
    {
        $this->reset();

        if ($rows = $this->driver->find($this->row, $clause, $options, $ttl)) {
            $this->rows = $rows;
            $this->current();
        }

        return $this;
    }

    /**
     * Load mapper by finding first match by clause.
     *
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return Mapper
     */
    public function first(array $clause = null, array $options = null, int $ttl = 0): Mapper
    {
        $this->reset();

        if ($row = $this->driver->first($this->row, $clause, $options, $ttl)) {
            $this->rows[] = $row;
            $this->current();
        }

        return $this;
    }

    /**
     * Load mapper by primary keys.
     *
     * @param mixed ...$values
     *
     * @return Mapper
     */
    public function find(...$values): Mapper
    {
        $keys = $this->row->getKeys();
        $vcount = count($values);
        $pcount = count($keys);

        if (0 === $pcount) {
            throw new \LogicException('Mapper has no key.');
        }

        if ($vcount !== $pcount) {
            throw new \LogicException(sprintf('Insufficient keys value. Expected exactly %d parameters, %d given.', $pcount, $vcount));
        }

        return $this->first(array_combine($keys, $values));
    }

    /**
     * Returns mapper pagination.
     *
     * @param int        $page
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return array
     */
    public function paginate(int $page, array $clause = null, array $options = null, int $ttl = 0): array
    {
        $limit = $options['perpage'] ?? static::PERPAGE;
        $pagination = $this->driver->paginate($this->row, $page, $limit, $clause, $options, $ttl);
        $pagination['mapper'] = clone $this;
        $pagination['mapper']->reset();

        if ($pagination['subset']) {
            $pagination['mapper']->rows = $pagination['subset'];
            $pagination['mapper']->current();
        }

        unset($pagination['subset']);

        return $pagination;
    }

    /**
     * Returns true if mapper saved successfully.
     *
     * @return bool
     */
    public function save(): bool
    {
        $event = new BeforeSaveEvent($this);
        $this->eventDispatcher->dispatch(static::ON_BEFORESAVE, $event);

        if ($event->isPropagationStopped()) {
            return false;
        }

        if ($this->valid()) {
            $result = $this->driver->update($this->row);
        } elseif ($row = $this->driver->insert($this->row)) {
            $result = true;
            $this->rows[] = $row;
            $this->current();
        } else {
            $result = false;
        }

        $event = new AfterSaveEvent($this);
        $this->eventDispatcher->dispatch(static::ON_AFTERSAVE, $event);

        return $result;
    }

    /**
     * Delete mapper and returns affected rows.
     *
     * @param array|null $clause
     *
     * @return int
     */
    public function delete(array $clause = null): int
    {
        if ($clause) {
            $this->reset();

            return $this->driver->deleteByClause($this->row, $clause);
        }

        if (!$this->valid()) {
            throw new \LogicException('Unable to delete unloaded mapper.');
        }

        $event = new BeforeDeleteEvent($this);
        $this->eventDispatcher->dispatch(static::ON_BEFOREDELETE, $event);

        if ($event->isPropagationStopped()) {
            return 0;
        }

        $row = null;
        $result = $this->driver->delete($this->row);

        if ($result) {
            $row = clone $this->row;
            $this->rows = array_slice($this->rows, 0, $this->ptr, true) + array_slice($this->rows, $this->ptr + 1, null, true);
            $this->next();

            if ($this->valid()) {
                $this->current();
            }
        }

        $event = new AfterDeleteEvent($this, $row);
        $this->eventDispatcher->dispatch(static::ON_AFTERDELETE, $event);

        return (int) $result;
    }

    /**
     * Returns true if field exists.
     *
     * @param string $field
     *
     * @return bool
     */
    public function exists(string $field): bool
    {
        return $this->row->exists($field);
    }

    /**
     * Returns field value.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function get(string $field)
    {
        if ($this->row->exists($field)) {
            return $this->row->get($field);
        }

        if (method_exists($this, $field)) {
            $this->row->set($field, $value = $this->$field());

            return $value;
        }

        throw new \LogicException(sprintf('Field not exists: %s.', $field));
    }

    /**
     * Assign field value.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return Mapper
     */
    public function set(string $field, $value): Mapper
    {
        $this->row->set($field, $value);

        return $this;
    }

    /**
     * Clear field value.
     *
     * @param string $field
     *
     * @return Mapper
     */
    public function clear(string $field): Mapper
    {
        $this->row->clear($field);

        return $this;
    }

    /**
     * Remove field.
     *
     * @param string $field
     *
     * @return Mapper
     */
    public function remove(string $field): Mapper
    {
        $this->row->remove($field);

        return $this;
    }

    /**
     * Returns mapper in array key, value pairs.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->row->toArray();
    }

    /**
     * Sets mapper from aray key, value pairs.
     *
     * @param array $values
     *
     * @return Mapper
     */
    public function fromArray(array $values): Mapper
    {
        $this->row->fromArray($values);

        return $this;
    }

    /**
     * Reset mapper data.
     *
     * @return Mapper
     */
    public function reset(): Mapper
    {
        $this->row->reset();
        $this->rows = array();
        $this->rewind();

        return $this;
    }

    /**
     * Returns number of record.
     *
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return int
     */
    public function countRows(array $clause = null, array $options = null, int $ttl = 0): int
    {
        return $this->driver->count($this->row, $clause, $options, $ttl);
    }

    /**
     * To override by children.
     */
    protected function initialize()
    {
        // to override by children
    }
}
