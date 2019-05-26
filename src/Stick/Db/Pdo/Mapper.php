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

namespace Fal\Stick\Db\Pdo;

use Fal\Stick\Magic;

/**
 * Database table mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper extends Magic implements \Iterator, \Countable
{
    // Pagination limit
    const PAGINATION_LIMIT = 10;

    // Events
    const EVENT_LOAD = 'mapper.load';
    const EVENT_SAVE = 'mapper.save';
    const EVENT_AFTER_SAVE = 'mapper.after_save';
    const EVENT_DELETE = 'mapper.delete';
    const EVENT_AFTER_DELETE = 'mapper.after_delete';

    /**
     * Row blueprint.
     *
     * @var Schema
     */
    public $schema;

    /**
     * @var Db
     */
    public $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Adhoc blueprint.
     *
     * @var array
     */
    protected $adhoc = array();

    /**
     * @var int
     */
    protected $ptr = 0;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var array
     */
    protected $changes = array();

    /**
     * @var array
     */
    protected $locker = array();

    /**
     * @var array
     */
    protected $rules = array();

    /**
     * @var array
     */
    protected $extraRules = array();

    /**
     * Class constructor.
     *
     * @param Db                $db
     * @param string|null       $table
     * @param string|array|null $fields
     * @param int               $ttl
     */
    public function __construct(Db $db, string $table = null, $fields = null, int $ttl = 60)
    {
        $use = $table ?? $this->table ?? $db->fw->snakeCase($db->fw->classname($this));

        $this->db = $db;
        $this->switchTable($use, $fields, $ttl);
    }

    /**
     * Clone schema.
     */
    public function __clone()
    {
        $this->schema = clone $this->schema;
    }

    /**
     * Handle custom method call.
     *
     * get{FieldName}
     * findBy{FieldName} => find
     * findOneBy{FieldName} => findOne
     */
    public function __call($method, $arguments)
    {
        $call = null;
        $argument = null;

        if (0 === strncasecmp('get', $method, 3)) {
            $call = 'get';
            $argument = $this->db->fw->snakeCase(substr($method, 3));
        } elseif (0 === strncasecmp('findby', $method, 6)) {
            $call = 'findOne';
            $argument = array($this->db->fw->snakeCase(substr($method, 6)) => array_shift($arguments));
        } elseif (0 === strncasecmp('findallby', $method, 9)) {
            $call = 'findAll';
            $argument = array($this->db->fw->snakeCase(substr($method, 9)) => array_shift($arguments));
        } else {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s.', static::class, $method));
        }

        return $this->$call($argument, ...$arguments);
    }

    /**
     * {inheritdoc}.
     */
    public function current()
    {
        if ($this->loaded) {
            $this->db->fw->dispatch(self::EVENT_LOAD, $this);
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
        return $this->loaded && isset($this->hive[$this->ptr]);
    }

    /**
     * {inheritdoc}.
     */
    public function count()
    {
        return count($this->hive);
    }

    /**
     * Next complement.
     */
    public function prev()
    {
        --$this->ptr;
    }

    /**
     * Returns true if mapper is new.
     *
     * @return bool
     */
    public function dry(): bool
    {
        return !$this->valid();
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    public function table(): string
    {
        return $this->table;
    }

    /**
     * Returns table alias.
     *
     * @return string|null
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

    /**
     * Switch table, supports alias declaration by dot notation format like below.
     *
     * Ex: table.alias
     *
     * @param string            $table
     * @param string|array|null $fields
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function switchTable(string $table, $fields = null, int $ttl = 60): Mapper
    {
        $this->reset();

        list($this->table, $this->alias) = explode('.', $table) + array(1 => $this->alias);
        $this->schema = $this->db->schema($this->table, $fields, $ttl);

        return $this;
    }

    /**
     * Lock field value.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return Mapper
     */
    public function lock(string $field, $value): Mapper
    {
        if (!isset($this->schema[$field])) {
            throw new \LogicException(sprintf('Cannot lock adhoc field: %s.', $field));
        }

        $this->locker[$field] = $value;
        $this->changes[$this->ptr][$field] = $value;

        return $this;
    }

    /**
     * Unlock field.
     *
     * @param string $field
     *
     * @return Mapper
     */
    public function unlock(string $field): Mapper
    {
        unset($this->locker[$field]);

        return $this;
    }

    /**
     * Returns true if field exists.
     *
     * @param string $field
     *
     * @return bool
     */
    public function has(string $field): bool
    {
        return isset($this->schema[$field]) || isset($this->adhoc[$field]);
    }

    /**
     * Returns field value.
     *
     * @param string $field
     * @param mixed  $default
     *
     * @return mixed
     */
    public function &get(string $field, $default = null)
    {
        if (isset($this->changes[$this->ptr]) && array_key_exists($field, $this->changes[$this->ptr])) {
            $val = $this->changes[$this->ptr][$field];
        } elseif (isset($this->hive[$this->ptr]) && array_key_exists($field, $this->hive[$this->ptr])) {
            $val = $this->hive[$this->ptr][$field];
        } elseif (isset($this->schema[$field])) {
            $val = $this->schema[$field]['default'];
        } elseif (isset($this->adhoc[$field]) && $call = $this->adhoc[$field]['call']) {
            $val = $this->hive[$this->ptr][$field] = $call($this);
        } elseif (method_exists($this, $field)) {
            $val = $this->hive[$this->ptr][$field] = $this->$field();
        } else {
            throw new \LogicException(sprintf('Field not exists: %s.', $field));
        }

        return $val;
    }

    /**
     * Assign field value.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return Magic
     */
    public function set(string $field, $value): Magic
    {
        if (isset($this->schema[$field])) {
            $this->changes[$this->ptr][$field] = $value;
        } elseif (isset($this->adhoc[$field])) {
            if (is_callable($value)) {
                $this->adhoc[$field]['call'] = $value;
                $this->adhoc[$field]['expr'] = null;

                unset($this->hive[$this->ptr][$field]);
            } else {
                $this->adhoc[$field]['call'] = null;
                $this->adhoc[$field]['expr'] = $value;
            }
        } elseif (is_callable($value)) {
            $this->adhoc[$field] = array(
                'call' => $value,
                'expr' => null,
            );
        } elseif (is_string($value)) {
            $this->adhoc[$field] = array(
                'call' => null,
                'expr' => $value,
            );
        } else {
            // set as raw adhoc
            $this->hive[$this->ptr][$field] = $value;
        }

        return $this;
    }

    /**
     * Clear field value.
     *
     * @param string $field
     *
     * @return Magic
     */
    public function rem(string $field): Magic
    {
        unset($this->changes[$this->ptr][$field], $this->hive[$this->ptr][$field], $this->adhoc[$field]);

        return $this;
    }

    /**
     * Reset mapper data.
     *
     * @return Magic
     */
    public function reset(): Magic
    {
        $this->hive = array();
        $this->changes = array();
        $this->loaded = false;
        $this->ptr = 0;

        if ($this->locker) {
            $this->changes[$this->ptr] = $this->locker;
        }

        return $this;
    }

    /**
     * Mapper initial.
     *
     * @return array
     */
    public function initial(): array
    {
        return $this->hive[$this->ptr] ?? array();
    }

    /**
     * Mapper changes.
     *
     * @return array
     */
    public function changes(): array
    {
        $changes = $this->changes[$this->ptr] ?? array();

        foreach ($this->initial() as $key => $value) {
            if (array_key_exists($key, $changes) && $changes[$key] === $value) {
                unset($changes[$key]);
            }
        }

        return $changes;
    }

    /**
     * Returns rows as array assoc.
     *
     * @return array
     */
    public function hive(): array
    {
        $result = $this->hive;

        foreach ($this->changes as $ptr => $item) {
            foreach ($item as $field => $value) {
                $result[$ptr][$field] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns initial rows as array assoc.
     *
     * @return array
     */
    public function rows(): array
    {
        return $this->hive;
    }

    /**
     * Returns mapper in array key, value pairs.
     *
     * @param bool $withAdhoc
     *
     * @return array
     */
    public function toArray(bool $withAdhoc = false): array
    {
        $result = array();
        $adhocs = $withAdhoc ? $this->adhoc : array();

        foreach ($this->schema->getSchema() + $adhocs as $key => $value) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Sets mapper from aray key, value pairs.
     *
     * @param array $values
     * @param bool  $withAdhoc
     *
     * @return Mapper
     */
    public function fromArray(array $values, bool $withAdhoc = false): Mapper
    {
        foreach ($values as $field => $value) {
            if (!isset($this->schema[$field]) && !$withAdhoc) {
                continue;
            }

            $this->set($field, $value);
        }

        return $this;
    }

    /**
     * Returns keys value.
     *
     * @return array
     */
    public function keys(): array
    {
        if (!$this->loaded) {
            throw new \LogicException('Invalid operation on an empty mapper.');
        }

        return array_intersect_key($this->hive[$this->ptr], array_fill_keys($this->schema->getKeys(), null));
    }

    /**
     * Returns concatenate fields.
     *
     * @return string
     */
    public function fields(): string
    {
        return implode(', ', array_map(array($this->db->driver, 'quote'), $this->schema->getFields()));
    }

    /**
     * Returns concatenate adhocs.
     *
     * @return string
     */
    public function adhocs(): string
    {
        $adhocs = '';

        if ($this->adhoc) {
            foreach ($this->adhoc as $field => $value) {
                if ($value['expr']) {
                    $adhocs .= ', ('.$value['expr'].') as '.$this->db->driver->quote($field);
                }
            }
        }

        return $adhocs;
    }

    /**
     * Returns validation rules.
     *
     * @param string|null $group
     *
     * @return array
     */
    public function rules(string $group = null): array
    {
        $rules = array();

        if (empty($this->rules)) {
            foreach ($this->schema as $field => $schema) {
                if ($schema['pkey'] && \PDO::PARAM_INT === $schema['pdo_type']) {
                    continue;
                }

                $rule = $schema['nullable'] ? array() : array('required');

                if (\PDO::PARAM_STR === $schema['pdo_type'] && is_numeric($schema['constraint'])) {
                    $rule[] = 'lenmax:'.$schema['constraint'];
                } elseif (0 === stripos($schema['data_type'], 'date')) {
                    $rule[] = $schema['data_type'];
                }

                $rules[$field] = implode('|', $rule);
            }

            return array_filter($this->extraRules + $rules);
        }

        foreach ($this->rules as $field => $ruleGroup) {
            list($rule, $grp) = ((array) $ruleGroup) + array(1 => 'default');

            if (!$group || $group === $grp) {
                $rules[$field] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Find matching records.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function findAll($filter = null, array $options = null, int $ttl = 0): Mapper
    {
        $this->reset();

        $cmd = $this->db->driver->sqlSelect($this->fields().$this->adhocs(), $this->table, $this->alias, $filter, $options);

        $this->hive = $this->db->exec($cmd[0], $cmd[1], $ttl);
        $this->loaded = (bool) $this->hive;

        return $this;
    }

    /**
     * Find one matching records.
     *
     * @param array|null        $filter
     * @param string|array|null $options
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function findOne($filter = null, array $options = null, int $ttl = 0): Mapper
    {
        $options['limit'] = 1;

        return $this->findAll($filter, $options, $ttl);
    }

    /**
     * Find matching records by keys.
     *
     * @param string|array $keys
     * @param int          $ttl
     *
     * @return Mapper
     */
    public function find($keys, int $ttl = 0): Mapper
    {
        $keys = $this->db->fw->split($keys);
        $pkeys = $this->schema->getKeys();

        if (0 === $pcount = count($pkeys)) {
            throw new \LogicException('Mapper has no key.');
        }

        if ($pcount !== $vcount = count($keys)) {
            throw new \LogicException(sprintf('Insufficient keys, expected exactly %d keys, %d given.', $pcount, $vcount));
        }

        return $this->findOne(array_combine($pkeys, $keys), null, $ttl);
    }

    /**
     * Returns records count.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return int
     */
    public function recordCount($filter = null, array $options = null, int $ttl = 0): int
    {
        list($sql, $arguments) = $this->db->driver->sqlCount($this->adhocs(), $this->table, $this->alias, $filter, $options);
        $result = $this->db->exec($sql, $arguments, $ttl);

        return (int) $result[0]['_rows'];
    }

    /**
     * Paginate result.
     *
     * @param int               $page
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return array
     */
    public function paginate(int $page = 1, $filter = null, array $options = null, int $ttl = 0): array
    {
        $limit = $options['limit'] ?? self::PAGINATION_LIMIT;
        unset($options['limit']);

        $subset = clone $this;
        $total = $subset->recordCount($filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $count = 0;
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $options['offset'] = ($page - 1) * $limit;
            $options['limit'] = $limit;

            if ($count = $subset->findAll($filter, $options, $ttl)->count()) {
                $start = $options['offset'] + 1;
                $end = $options['offset'] + $count;
            }
        }

        return compact('subset', 'total', 'count', 'pages', 'page', 'start', 'end');
    }

    /**
     * Save mapper.
     *
     * @return bool
     */
    public function save(): bool
    {
        $dispatch = $this->db->fw->dispatch(self::EVENT_SAVE, $this);
        // changes after dispatching
        $changes = $this->changes();

        if (empty($changes) || ($dispatch && false === $dispatch[0])) {
            return false;
        }

        if ($this->loaded) {
            list($sql, $arguments) = $this->db->driver->sqlUpdate($this->table, $this->schema, $changes, $this->keys());
            $result = 0 < $this->db->exec($sql, $arguments);
        } else {
            list($sql, $arguments, $inc) = $this->db->driver->sqlInsert($this->table, $this->schema, $changes);
            $result = 0 < $this->db->exec($sql, $arguments);

            if ($result) {
                if ($inc) {
                    $this->findOne(array($inc => $this->db->pdo()->lastInsertId()));
                } else {
                    // swap changes, add current adhoc if exists
                    $this->hive[$this->ptr] = $changes + $this->initial();
                    $this->changes = array();
                    $this->loaded = true;
                }
            }
        }

        $this->db->fw->dispatch(self::EVENT_AFTER_SAVE, $this, $result, $changes);

        return $result;
    }

    /**
     * Mapper delete.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $keys = $this->keys();
        $dispatch = $this->db->fw->dispatch(self::EVENT_DELETE, $this, $keys);

        if (empty($keys) || ($dispatch && false === $dispatch[0])) {
            return false;
        }

        list($sql, $arguments) = $this->db->driver->sqlDelete($this->table, $this->schema, $keys);
        $initial = $this->initial();
        $result = 0 < $this->db->exec($sql, $arguments);

        if ($result) {
            unset($this->hive[$this->ptr], $this->changes[$this->ptr]);

            // update loaded and load next map
            $this->loaded = (bool) $this->hive;
            $this->next();
            $this->dry() || $this->current();
        }

        $this->db->fw->dispatch(self::EVENT_AFTER_DELETE, $this, $result, $initial, $keys);

        return $result;
    }

    /**
     * Delete all.
     *
     * @param string|array $filters
     * @param bool         $dispatch
     *
     * @return int
     */
    public function deleteAll($filters, bool $dispatch = false): int
    {
        if ($dispatch) {
            $deleted = 0;

            foreach ($this->findAll($filters) as $self) {
                $deleted += (int) $self->delete();
            }

            return $deleted;
        }

        list($sql, $arguments) = $this->db->driver->sqlDeleteBatch($this->table, $filters);

        return $this->db->exec($sql, $arguments);
    }

    /**
     * Returns mapper to relate.
     *
     * @param string|object $mapper
     * @param string|null   $relations
     * @param bool          $belongsTo
     * @param bool          $findAll
     * @param mixed         $filters
     * @param array|null    $options
     *
     * @return Mapper
     */
    public function createRelation($mapper, string $relations = null, bool $belongsTo = false, bool $findAll = false, $filters = null, array $options = null): Mapper
    {
        if (!is_a($mapper, self::class, true) && (!is_string($mapper) || $aClass = class_exists($mapper))) {
            throw new \LogicException(sprintf('Mapper should be an instance of %s.', self::class));
        }

        if (isset($aClass)) {
            $mapper = new self($this->db, $mapper);
        } elseif (is_string($mapper)) {
            $mapper = new $mapper($this->db);
        }

        $keys = $belongsTo ? $mapper->schema->getKeys() : $this->schema->getKeys();

        if (!$relations && !$keys) {
            throw new \LogicException('No relation defined.');
        }

        $ctr = -1;
        $fw = $this->db->fw;
        $find = $findAll ? 'findAll' : 'findOne';
        $relationFilters = array(array());

        foreach ($fw->split($relations ?? ($belongsTo ? $mapper->table() : $this->table).'_id') as $relation) {
            ++$ctr;
            $pair = $fw->split($relation, '=');

            if ($belongsTo) {
                // first is localkey (fallback to the first primary key), last is foreignkey
                $relationFilters[0][$pair[1] ?? $keys[$ctr] ?? $keys[0]] = $this->get($pair[0]);
            } else {
                // first is foreignkey, last is localkey (fallback to the first primary key)
                $mapper->lock($pair[0], $relationFilters[0][$pair[0]] = $this->get($pair[1] ?? $keys[$ctr] ?? $keys[0]));
            }
        }

        if ($filters) {
            $relationFilters[] = is_callable($filters) ? $filters($this) : $filters;
        }

        return $mapper->$find($relationFilters, $options);
    }

    /**
     * One to one relationship.
     *
     * @param string|Mapper $mapper
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     *
     * @return Mapper
     */
    public function hasOne($mapper, string $relations = null, $filters = null, array $options = null): Mapper
    {
        return $this->createRelation($mapper, $relations, false, false, $filters, $options);
    }

    /**
     * One to many relationship.
     *
     * @param string|Mapper $mapper
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     *
     * @return Mapper
     */
    public function hasMany($mapper, string $relations = null, $filters = null, array $options = null): Mapper
    {
        return $this->createRelation($mapper, $relations, false, true, $filters, $options);
    }

    /**
     * One to one relationship (inverse).
     *
     * @param string|Mapper $mapper
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     *
     * @return Mapper
     */
    public function belongsTo($mapper, string $relations = null, $filters = null, array $options = null): Mapper
    {
        return $this->createRelation($mapper, $relations, true, false, $filters, $options);
    }

    /**
     * Many to one relationship.
     *
     * @param string|Mapper $mapper
     * @param string|Mapper $pivotMapper
     * @param string|null   $pivotField
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     * @param string|null   $mapperRelations
     * @param mixed         $mapperFilters
     * @param array|null    $mapperOptions
     *
     * @return Mapper
     */
    public function belongsToMany($mapper, $pivotMapper, string $pivotField = null, string $relations = null, $filters = null, array $options = null, string $mapperRelations = null, $mapperFilters = null, array $mapperOptions = null): Mapper
    {
        $pivot = $this->hasMany($pivotMapper, $relations, $filters, $options);

        if ($pivotField) {
            $pivot->set($pivotField, function ($pivot) use ($mapper, $mapperRelations, $mapperFilters, $mapperOptions) {
                return $pivot->belongsTo($mapper, $mapperRelations, $mapperFilters, $mapperOptions);
            });
        }

        return $pivot;
    }
}
