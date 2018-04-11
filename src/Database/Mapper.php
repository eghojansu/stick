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

use function Fal\Stick\camelcase;
use function Fal\Stick\classname;
use function Fal\Stick\pick;
use function Fal\Stick\snakecase;
use function Fal\Stick\istartswith;
use function Fal\Stick\icutafter;

class Mapper
{
    /** @var string */
    protected $map;

    /** @var Database */
    protected $db;

    /** @var string */
    protected $source;

    /** @var string Quoted table */
    protected $table;

    /** @var array */
    protected $fields = [];

    /** @var array */
    protected $pkeys = [];

    /** @var array */
    protected $trigger = [];

    /**
     * Class constructor
     *
     * @param Database     $db
     * @param string       $table
     * @param string|array $fields
     * @param int          $ttl
     */
    public function __construct(
        DatabaseInterface $db,
        string $table = null,
        $fields = null,
        int $ttl = 60
    ) {
        $this->db = $db;
        $this->setSource($this->source ?? $table ?? snakecase(classname($this)), $fields, $ttl);
    }

    /**
     * Clone with new source
     *
     * @param  string $source
     *
     * @return Mapper
     */
    public function withSource(string $source): Mapper
    {
        $clone = clone $this;
        $clone->setSource($source);

        return $clone;
    }

    /**
     * Set source (table)
     *
     * @param string       $source
     * @param string|array $fields
     * @param int          $ttl
     *
     * @return Mapper
     */
    public function setSource(string $source, $fields = null, int $ttl = 60): Mapper
    {
        if (!$source) {
            return $this;
        }

        $this->source = $source;
        $this->table = $this->db->quotekey($source);
        $this->fields = $this->db->getSchema($source, $fields, $ttl);
        $this->pkeys = [];

        foreach ($this->fields as $key => $value) {
            if ($value['pkey']) {
                $this->pkeys[] = $key;
            }
        }

        return $this;
    }

    /**
     * Get source (table)
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source ?? '';
    }

    /**
     * Expose db
     *
     * @return Database
     */
    public function getDb(): DatabaseInterface
    {
        return $this->db;
    }

    /**
     * Find by primary key
     *
     * @return mixed
     */
    public function find($id)
    {
        $pvals = (array) $id;
        $vcount = count($pvals);
        $pcount = count($this->pkeys);

        if ($vcount !== $pcount) {
            throw new \ArgumentCountError(
                __METHOD__ . ' expect exactly ' . $pcount .
                ' arguments, given ' . $vcount . ' arguments'
            );
        }

        return $this->findOne(array_combine($this->pkeys, $pvals));
    }

    /**
     * Run select query, set limit to 1 (one)
     *
     * @param  mixed $filter
     * @param  array $option
     * @param  int   $ttl
     *
     * @return mixed
     */
    public function findOne($filter = null, array $option = null, int $ttl = 0)
    {
        $res = $this->factory([
            $this->db->selectOne(
                $this->table,
                $this->objToFilter($filter),
                $option,
                $ttl
            )
        ]);

        return $res[0] ?? null;
    }

    /**
     * Run select query
     *
     * @param  mixed $filter
     * @param  array $option
     * @param  int   $ttl
     *
     * @return array
     */
    public function findAll($filter = null, array $option = null, int $ttl = 0): array
    {
        return $this->factory(
            $this->db->select(
                $this->table,
                $this->objToFilter($filter),
                $option,
                $ttl
            )
        );
    }

    /**
     * Paginate records
     *
     * @param  int        $page
     * @param  mixed      $filter
     * @param  array|null $option
     * @param  int        $ttl
     *
     * @return array
     */
    public function paginate(
        int $page = 1,
        $filter = null,
        array $option = null,
        int $ttl = 0
    ): array {
        $res = $this->db->paginate(
            $this->table,
            $page,
            $this->objToFilter($filter),
            $option,
            $ttl
        );
        $res['subset'] = $this->factory($res['subset']);

        return $res;
    }

    /**
     * Count table records
     *
     * @param  mixed  $filter
     * @param  array  $option
     * @param  int    $ttl
     *
     * @return int
     */
    public function count($filter = null, array $option = null, int $ttl = 0): int
    {
        return $this->db->count(
            $this->source,
            $this->objToFilter($filter),
            $option,
            $ttl
        );
    }

    /**
     * Insert data to table
     *
     * @param  mixed  $data
     *
     * @return mixed
     */
    public function insert($data)
    {
        $before = $this->trigger('beforeinsert', [$data]);

        if (!$before) {
            return false;
        }

        $use = is_bool($before) ? $data : $before;
        $id = $this->db->insert($this->source, is_object($use) ? $this->cast($use) : $use);

        $after = $this->trigger('afterinsert', [$use, $id]);

        return $after && !is_bool($after) ? $after : $id;
    }

    /**
     * Update data by filter
     *
     * @param  mixed  $data
     * @param  mixed  $filter
     *
     * @return mixed
     */
    public function update($data, $filter)
    {
        $id = $this->objToFilter($filter);
        $before = $this->trigger('beforeupdate', [$data, $id]);

        if (!$before) {
            return false;
        }

        $use = is_bool($before) ? $data : $before;
        $res = $this->db->update($this->source, is_object($use) ? $this->cast($use) : $use, $id);

        if (!$res) {
            return false;
        }

        $after = $this->trigger('afterupdate', [$use, $id]);

        return $after && !is_bool($after) ? $after : $res;
    }

    /**
     * Delete data by filter
     *
     * @param  mixed  $filter
     *
     * @return mixed
     */
    public function delete($filter)
    {
        $id = $this->objToFilter($filter);
        $before = $this->trigger('beforedelete', [$filter, $id]);

        if (!$before) {
            return false;
        }

        $use = is_bool($before) ? $id : $before;
        $res = $this->db->delete($this->source, $this->objToFilter($use));

        if (!$res) {
            return false;
        }

        $after = $this->trigger('afterdelete', [$filter, $id]);

        return $after && !is_bool($after) ? $after : $res;
    }

    /**
     * Define onload trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function onload(callable $func): Mapper
    {
        $this->trigger['load'] = $func;

        return $this;
    }

    /**
     * Define beforeinsert trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function beforeinsert(callable $func): Mapper
    {
        $this->trigger['beforeinsert'] = $func;

        return $this;
    }

    /**
     * Define afterinsert trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function afterinsert(callable $func): Mapper
    {
        $this->trigger['afterinsert'] = $func;

        return $this;
    }

    /**
     * Define oninsert trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function oninsert(callable $func): Mapper
    {
        return $this->afterinsert($func);
    }

    /**
     * Define beforeupdate trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function beforeupdate(callable $func): Mapper
    {
        $this->trigger['beforeupdate'] = $func;

        return $this;
    }

    /**
     * Define afterupdate trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function afterupdate(callable $func): Mapper
    {
        $this->trigger['afterupdate'] = $func;

        return $this;
    }

    /**
     * Define onupdate trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function onupdate(callable $func): Mapper
    {
        return $this->afterupdate($func);
    }

    /**
     * Define beforesave trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function beforesave(callable $func): Mapper
    {
        $this->trigger['beforeinsert'] = $func;
        $this->trigger['beforeupdate'] = $func;

        return $this;
    }

    /**
     * Define aftersave trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function aftersave(callable $func): Mapper
    {
        $this->trigger['afterinsert'] = $func;
        $this->trigger['afterupdate'] = $func;

        return $this;
    }

    /**
     * Define onsave trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function onsave(callable $func): Mapper
    {
        return $this->aftersave($func);
    }

    /**
     * Define beforedelete trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function beforedelete(callable $func): Mapper
    {
        $this->trigger['beforedelete'] = $func;

        return $this;
    }

    /**
     * Define afterdelete trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function afterdelete(callable $func): Mapper
    {
        $this->trigger['afterdelete'] = $func;

        return $this;
    }

    /**
     * Define ondelete trigger
     *
     * @param callable $func
     *
     * @return Mapper
     */
    public function ondelete(callable $func): Mapper
    {
        return $this->afterdelete($func);
    }

    /**
     * Do factory job
     *
     * @param  string       $data
     * @param  bool|boolean $one
     *
     * @return mixed
     */
    protected function factory($data)
    {
        $converted = [];

        foreach ($data as $record) {
            $use = $this->map ? $this->load($record) : $record;
            $loaded = $this->trigger('load', [$use, pick($this->pkeys, $record)]);

            if (!$loaded) {
                continue;
            }

            $converted[] = is_bool($loaded) ? $use : $loaded;
        }

        return $converted;
    }

    /**
     * Cast to filter
     *
     * @param  mixed $obj
     *
     * @return mixed
     */
    protected function objToFilter($obj)
    {
        return is_object($obj) ? pick($this->pkeys, $this->cast($obj), false) : $obj;
    }

    /**
     * Cast from object to array
     *
     * @param  object $obj
     *
     * @return array
     */
    protected function cast($obj): array
    {
        $record = [];

        foreach ($this->fields as $key => $value) {
            $name = camelcase($key);
            $get = 'get' . $name;
            $is = 'is' . $name;

            if (method_exists($obj, $get)) {
                $val = $obj->$get();
            } elseif (method_exists($obj, $is)) {
                $val = $obj->$is();
            }

            if ($val !== null) {
                $record[$key] = $val;
            }
        }

        return $record;
    }

    /**
     * Convert array to mapped class
     *
     * @param  array  $record
     *
     * @return object
     */
    protected function load(array $record)
    {
        $map = $this->map;
        $obj = new $map();

        foreach ($this->fields as $key => $value) {
            $set = 'set' . camelcase($key);

            if (method_exists($obj, $set) && array_key_exists($key, $record)) {
                $obj->$set($record[$key]);
            }
        }

        return $obj;
    }

    /**
     * Trigger event if exists
     *
     * @param  string $event
     * @param  array  $args
     *
     * @return mixed
     */
    protected function trigger(string $event, array $args = [])
    {
        if (!isset($this->trigger[$event])) {
            return true;
        }

        $res = call_user_func_array($this->trigger[$event], $args);

        return $res === null ? true : $res;
    }

    /**
     * Proxy to database method
     * Example:
     *   findOneUsername('foo') = findOne(['username'=>'foo'])
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

            if ($args) {
                $first = array_shift($args);
                array_unshift($args, [$field => $first]);
            }

            return $this->findOne(...$args);
        } elseif (istartswith('findby', $method)) {
            $field = snakecase(icutafter('findby', $method));

            if ($args) {
                $first = array_shift($args);
                array_unshift($args, [$field => $first]);
            }

            return $this->findAll(...$args);
        }

        throw new \BadMethodCallException(
            'Call to undefined method ' . static::class . '::' . $method
        );
    }
}
