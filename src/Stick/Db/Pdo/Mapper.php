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

/**
 * Database table mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable
{
    /** @var int Pagination limit */
    const PAGINATION_LIMIT = 10;

    // Events
    const EVENT_LOAD = 'mapper.load';
    const EVENT_SAVE = 'mapper.save';
    const EVENT_AFTER_SAVE = 'mapper.after_save';
    const EVENT_DELETE = 'mapper.delete';
    const EVENT_AFTER_DELETE = 'mapper.after_delete';

    /** @var Schema Row blueprint */
    public $schema;

    /** @var Db Database instance */
    public $db;

    /** @var bool Adhoc set value flag, for internal usage */
    protected $adhocSetValue = false;

    /** @var bool Resolve rule flag */
    protected $resolveRule = true;

    /** @var array User defined rules */
    protected $rules = array();

    /** @var array Loaded mappers */
    protected $rows = array();

    /** @var int Current row pointer */
    protected $ptr = 0;

    /** @var array Current row data */
    protected $row = array();

    /** @var array Current row adhoc */
    protected $adhoc = array();

    /** @var string Table name */
    protected $table;

    /** @var string Table alias */
    protected $alias;

    /**
     * Class constructor.
     *
     * @param Db                $db
     * @param string|null       $table
     * @param string|array|null $fields
     * @param int               $ttl
     */
    public function __construct(
        Db $db,
        string $table = null,
        $fields = null,
        int $ttl = 60
    ) {
        $this->db = $db;
        $this->switchTable(
            $table ?? $this->table ?? $db->fw->snakeCase($db->fw->classname($this)),
            $fields,
            $ttl
        );
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
        if (0 === strncasecmp('get', $method, 3)) {
            $field = $this->db->fw->snakeCase(substr($method, 3));

            return $this->get($field);
        }

        if (0 === strncasecmp('findby', $method, 6)) {
            $filter = array(
                $this->db->fw->snakeCase(substr($method, 6)) => array_shift($arguments),
            );

            return $this->findOne($filter, ...$arguments);
        }

        if (0 === strncasecmp('findallby', $method, 9)) {
            $filter = array(
                $this->db->fw->snakeCase(substr($method, 9)) => array_shift($arguments),
            );

            return $this->findAll($filter, ...$arguments);
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s.',
            static::class,
            $method
        ));
    }

    /**
     * {inheritdoc}.
     */
    public function __isset($offset)
    {
        return $this->has($offset);
    }

    /**
     * {inheritdoc}.
     */
    public function &__get($offset)
    {
        $var = &$this->get($offset);

        return $var;
    }

    /**
     * {inheritdoc}.
     */
    public function __set($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {inheritdoc}.
     */
    public function __unset($offset)
    {
        $this->rem($offset);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {inheritdoc}.
     */
    public function &offsetGet($offset)
    {
        $var = &$this->get($offset);

        return $var;
    }

    /**
     * {inheritdoc}.
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetUnset($offset)
    {
        $this->rem($offset);
    }

    /**
     * {inheritdoc}.
     */
    public function current()
    {
        $this->row = $this->rows[$this->ptr]->row;
        $this->adhoc = $this->rows[$this->ptr]->adhoc;

        return $this->rows[$this->ptr];
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
     * {inheritdoc}.
     */
    public function jsonSerialize()
    {
        $rows = array();

        foreach ($this->rows as $row) {
            $rows[] = $row->toArray(true);
        }

        return $rows;
    }

    /**
     * Next complement.
     */
    public function prev(): void
    {
        --$this->ptr;
    }

    /**
     * Move row pointer by hand.
     *
     * @param int $ptr
     *
     * @return Mapper
     */
    public function moveTo(int $ptr): Mapper
    {
        if (!isset($this->rows[$ptr])) {
            throw new \LogicException(sprintf('Invalid pointer: %s.', $ptr));
        }

        $this->ptr = $ptr;

        return $this->current();
    }

    /**
     * Valid complement.
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
     * Switch mapper table.
     *
     * Supports alias declaration by dot notation format like below:
     *
     *   Ex: table.alias
     *
     * @param string            $table
     * @param string|array|null $fields
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function switchTable(string $table, $fields = null, int $ttl = 60): Mapper
    {
        $maps = explode('.', $table);

        $this->table = $maps[0];
        $this->alias = $maps[1] ?? $this->alias;
        $this->schema = $this->db->schema($this->table, $fields, $ttl);

        return $this->reset();
    }

    /**
     * Create mapper from source row.
     *
     * @param array $row
     *
     * @return Mapper
     */
    public function factory(array $row): Mapper
    {
        $mapper = (clone $this)->reset()->fromArray($row, true);
        $mapper->row = $mapper->commitRow();
        $mapper->rows = array(clone $mapper);

        $this->db->fw->dispatch(self::EVENT_LOAD, $mapper);

        return $mapper;
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
        if (!isset($this->row[$field])) {
            throw new \LogicException(sprintf(
                'Cannot lock adhoc field: %s.',
                $field
            ));
        }

        $this->set($field, $value)->row[$field]['locked'] = true;

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
        $this->row[$field]['locked'] = false;

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
        return isset($this->row[$field]) || isset($this->adhoc[$field]);
    }

    /**
     * Returns field value.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function &get(string $field)
    {
        if (isset($this->row[$field])) {
            return $this->row[$field]['value'];
        }

        if (isset($this->adhoc[$field])) {
            $adhoc = &$this->adhoc[$field];

            if ($adhoc['raw']) {
                $adhoc['raw'] = false;

                if ($call = $adhoc['call']) {
                    $adhoc['value'] = $call($this);
                } else {
                    $adhoc['value'] = $adhoc['expr'];
                }
            }

            return $adhoc['value'];
        }

        if (method_exists($this, $field)) {
            $this->set($field, null)->adhoc[$field]['value'] = $this->$field();

            return $this->adhoc[$field]['value'];
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
        if (isset($this->row[$field])) {
            if ($this->row[$field]['locked']) {
                return $this;
            }

            $this->row[$field]['changed'] = $value !== $this->row[$field]['value'];

            if ($this->row[$field]['changed']) {
                $this->row[$field]['value'] = $value;
            }

            return $this;
        }

        $adhoc = $this->adhoc[$field] ?? array(
            'call' => null,
            'expr' => null,
            'raw' => null,
            'value' => null,
        );
        $adhoc['raw'] = true;

        if (is_callable($value)) {
            $adhoc['call'] = $value;
        } elseif (isset($this->adhoc[$field])) {
            // use adhoc set value flag
            if ($this->adhocSetValue) {
                $adhoc['value'] = $value;
                $adhoc['raw'] = false;
            } else {
                $adhoc['expr'] = $value;
            }
        } elseif (is_string($value)) {
            $adhoc['expr'] = $value;
        } else {
            $adhoc['value'] = $value;
            $adhoc['raw'] = false;
        }

        $this->adhoc[$field] = $adhoc;

        return $this;
    }

    /**
     * Clear field value.
     *
     * @param string $field
     *
     * @return Mapper
     */
    public function rem(string $field): Mapper
    {
        if (isset($this->row[$field])) {
            $this->row[$field]['changed'] = false;
            $this->row[$field]['value'] = $this->row[$field]['initial'];
        } elseif (isset($this->adhoc[$field])) {
            $this->adhoc[$field]['raw'] = true;
        }

        return $this;
    }

    /**
     * Reset mapper data.
     *
     * @param bool $unlock
     *
     * @return Mapper
     */
    public function reset(bool $unlock = false): Mapper
    {
        $this->row = $this->commitRow(array(), $unlock);
        $this->adhoc = array_map(function ($adhoc) {
            $adhoc['raw'] = true;

            return $adhoc;
        }, $this->adhoc);
        $this->rows = array();
        $this->ptr = 0;

        return $this;
    }

    /**
     * Returns mapper in array key, value pairs.
     *
     * @param bool $adhoc
     *
     * @return array
     */
    public function toArray(bool $adhoc = false): array
    {
        $result = $this->values();

        foreach ($adhoc ? $this->adhoc : array() as $key => $value) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Sets mapper from aray key, value pairs.
     *
     * @param array $values
     * @param bool  $adhoc
     *
     * @return Mapper
     */
    public function fromArray(array $values, bool $adhoc = false): Mapper
    {
        $this->adhocSetValue = true;

        foreach ($values as $field => $value) {
            if (!isset($this->row[$field]) && !$adhoc) {
                continue;
            }

            $this->set($field, $value);
        }

        $this->adhocSetValue = false;

        return $this;
    }

    /**
     * Row to json.
     *
     * @param bool $adhoc
     * @param int  $options
     *
     * @return string
     */
    public function toJson(bool $adhoc = false, int $options = 0): string
    {
        return json_encode($this->toArray($adhoc), $options);
    }

    /**
     * Json to mapper.
     *
     * @param string $json
     * @param bool   $adhoc
     * @param int    $options
     *
     * @return Mapper
     */
    public function fromJson(string $json, bool $adhoc = false, int $options = 0): Mapper
    {
        return $this->fromArray(json_decode($json, true, 512, $options), $adhoc);
    }

    /**
     * Returns true if mapper or field value changed.
     *
     * @param string|null $field
     *
     * @return bool
     */
    public function changed(string $field = null): bool
    {
        return $field ? ($this->row[$field]['changed'] ?? false) :
            (bool) array_filter($this->changes());
    }

    /**
     * Returns true if field locked.
     *
     * @param string $field
     *
     * @return bool
     */
    public function locked(string $field): bool
    {
        return $this->row[$field]['locked'] ?? false;
    }

    /**
     * Mapper initial data.
     *
     * @return array
     */
    public function initial(): array
    {
        return $this->db->fw->arrColumn($this->row, 'initial');
    }

    /**
     * Mapper values.
     *
     * @return array
     */
    public function values(): array
    {
        return $this->db->fw->arrColumn($this->row, 'value');
    }

    /**
     * Returns changed value.
     *
     * @return array
     */
    public function changes(): array
    {
        return array_intersect_key(
            $this->values(),
            $this->db->fw->arrColumn($this->row, 'changed', false)
        );
    }

    /**
     * Returns keys value.
     *
     * @return array
     */
    public function keys(): array
    {
        return array_intersect_key(
            $this->initial(),
            array_fill_keys($this->schema->getKeys(), null)
        );
    }

    /**
     * Returns current row state.
     *
     * @return array
     */
    public function row(): array
    {
        return $this->row;
    }

    /**
     * Returns loaded mappers.
     *
     * @return array
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * Returns concatenate fields.
     *
     * @return string
     */
    public function concatFields(): string
    {
        return implode(', ', array_map(
            array($this->db->driver, 'quote'),
            $this->schema->getFields()
        ));
    }

    /**
     * Returns concatenate adhoc.
     *
     * @return string
     */
    public function concatAdhoc(): string
    {
        $str = '';

        foreach ($this->adhoc as $field => $adhoc) {
            if ($adhoc['expr']) {
                $str .= ', ('.$adhoc['expr'].') as '.$this->db->driver->quote($field);
            }
        }

        return $str;
    }

    /**
     * Returns defined adhoc.
     *
     * @return array
     */
    public function adhoc(): array
    {
        return $this->adhoc;
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

        if ($this->resolveRule) {
            foreach ($this->schema as $field => $schema) {
                if (
                    isset($this->rules[$field]) ||
                    ($schema['pkey'] && \PDO::PARAM_INT === $schema['pdo_type'])
                ) {
                    continue;
                }

                $rule = $schema['nullable'] ? array() : array('required');

                if (
                    \PDO::PARAM_STR === $schema['pdo_type'] &&
                    is_numeric($schema['constraint'])
                ) {
                    $rule[] = 'lenmax:'.$schema['constraint'];
                } elseif (0 === stripos($schema['data_type'], 'date')) {
                    $rule[] = $schema['data_type'];
                }

                $rules[$field] = implode('|', $rule);
            }
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
     * Find matching records and returns rows.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return array
     */
    public function select($filter = null, array $options = null, int $ttl = 0): array
    {
        list($sql, $arguments) = $this->db->driver->sqlSelect(
            $this->concatFields().$this->concatAdhoc(),
            $this->table,
            $this->alias,
            $filter,
            $options
        );

        return $this->db->exec($sql, $arguments, $ttl);
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
        if ($rows = $this->reset()->select($filter, $options, $ttl)) {
            $this->rows = array_map(array($this, 'factory'), $rows);
            $this->current();
        }

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
            throw new \LogicException(sprintf(
                'Insufficient keys, expected exactly %d keys, %d given.',
                $pcount,
                $vcount
            ));
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
    public function countRow($filter = null, array $options = null, int $ttl = 0): int
    {
        list($sql, $arguments) = $this->db->driver->sqlCount(
            $this->concatAdhoc(),
            $this->table,
            $this->alias,
            $filter,
            $options
        );
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
    public function paginate(
        int $page = 1,
        $filter = null,
        array $options = null,
        int $ttl = 0
    ): array {
        $limit = $options['limit'] ?? self::PAGINATION_LIMIT;
        unset($options['limit']);

        $subset = clone $this;
        $total = $subset->countRow($filter, $options, $ttl);
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

        return compact(
            'count',
            'end',
            'limit',
            'page',
            'pages',
            'start',
            'subset',
            'total'
        );
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

        if ($this->valid()) {
            list($sql, $arguments) = $this->db->driver->sqlUpdate(
                $this->table,
                $this->schema,
                $changes,
                $this->keys()
            );
            $result = 0 < $this->db->exec($sql, $arguments);
        } else {
            list($sql, $arguments, $inc) = $this->db->driver->sqlInsert(
                $this->table,
                $this->schema,
                $changes
            );
            $result = 0 < $this->db->exec($sql, $arguments);

            if ($result) {
                if ($inc) {
                    $this->findOne(array($inc => $this->db->pdo()->lastInsertId()));
                } else {
                    // commit changes
                    $this->row = $this->commitRow();
                    $this->rows[$this->ptr] = $this->row;
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

        list($sql, $arguments) = $this->db->driver->sqlDelete(
            $this->table,
            $this->schema,
            $keys
        );
        $initial = $this->initial();
        $result = 0 < $this->db->exec($sql, $arguments);

        if ($result) {
            unset($this->rows[$this->ptr]);

            // load next row
            $this->next();

            if ($this->valid()) {
                $this->current();
            } else {
                $this->row = $this->commitRow(array());
            }
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
     * One to one relationship.
     *
     * @param string|Mapper $relate
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     * @param int           $ttl
     * @param bool          $lock
     *
     * @return Mapper
     */
    public function hasOne(
        $relate,
        string $relations = null,
        $filters = null,
        array $options = null,
        int $ttl = 0,
        bool $lock = true
    ): Mapper {
        $mapper = $this->prepareRelation($relate);
        $filters = $this->filterRelation(
            $mapper,
            $relations ?? $this->table.'_id',
            'id',
            $filters,
            $lock
        );

        return $mapper->findOne($filters, $options, $ttl);
    }

    /**
     * One to many relationship.
     *
     * @param string|Mapper $relate
     * @param string|null   $relations
     * @param mixed         $filters
     * @param array|null    $options
     * @param int           $ttl
     * @param bool          $lock
     *
     * @return Mapper
     */
    public function hasMany(
        $relate,
        string $relations = null,
        $filters = null,
        array $options = null,
        int $ttl = 0,
        bool $lock = true
    ): Mapper {
        $mapper = $this->prepareRelation($relate);
        $filters = $this->filterRelation(
            $mapper,
            $relations ?? $this->table.'_id',
            'id',
            $filters,
            $lock
        );

        return $mapper->findAll($filters, $options, $ttl);
    }

    /**
     * One to one relationship (inverse).
     *
     * @param string|Mapper $relate
     * @param string|null   $relations
     * @param mixed         $filters
     * @param int           $ttl
     * @param array|null    $options
     *
     * @return Mapper
     */
    public function belongsTo(
        $relate,
        string $relations = null,
        $filters = null,
        array $options = null,
        int $ttl = 0
    ): Mapper {
        $mapper = $this->prepareRelation($relate);
        $filters = $this->filterRelation(
            $mapper,
            $relations ?? 'id',
            $mapper->table().'_id',
            $filters
        );

        return $mapper->findOne($filters, $options, $ttl);
    }

    /**
     * Many to one relationship.
     *
     * @param string|Mapper $relate
     * @param string|Mapper $table
     * @param string|null   $relations
     * @param string|null   $mapperRelations
     * @param mixed         $filters
     * @param array|null    $options
     * @param mixed         $mapperFilters
     * @param array|null    $mapperOptions
     * @param string|null   $pivotField
     * @param int           $ttl
     *
     * @return Mapper
     */
    public function belongsToMany(
        $relate,
        $table,
        string $relations = null,
        $filters = null,
        array $options = null,
        string $pivotRelation = null,
        $pivotFilters = null,
        array $pivotOptions = null,
        string $pivotField = 'pivot',
        int $ttl = 0
    ): Mapper {
        // load pivot first
        $pivot = $this->hasMany($table, $pivotRelation, $pivotFilters, $pivotOptions, $ttl, true);
        // expected mapper
        $mapper = $this->prepareRelation($relate);

        $fw = $this->db->fw;
        $relations = array_reduce(
            $fw->split($relations ?? $mapper->table().'_id'),
            function ($carry, $relation) {
                // localKey = foreignKey
                $pair = $this->db->fw->split($relation, '=');

                return $carry + array($pair[0] => $pair[1] ?? 'id');
            },
            array()
        );
        $relationFilters = array();

        if (1 === count($relations)) {
            $key = reset($relations);
            $pickKey = key($relations);
            $rowKey = $key.' []';
            $relationFilters[$rowKey] = array_map(function ($row) use (
                $pickKey
            ) {
                return $row->get($pickKey);
            }, $pivot->rows());
            $pivotMap = array_flip($relationFilters[$rowKey]);
            $mapper->set($pivotField, function ($mapper) use (
                $pivot,
                $pivotMap,
                $key
            ) {
                return $pivot->moveTo($pivotMap[$mapper->get($key)]);
            });
        } else {
            $pivotMap = array();
            $pickKeys = array_flip($relations);

            foreach ($pivot as $key => $row) {
                $rowKey = $key > 0 ? '| #'.$key : '#'.$key;
                $rowFilter = array_combine(
                    $relations,
                    array_intersect_key($row->initial(), $relations)
                );
                $relationFilters[$rowKey] = $rowFilter;
                $pivotMap[implode(',', $rowFilter)] = $key;
            }

            $mapper->set($pivotField, function ($mapper) use (
                $pivot,
                $pivotMap,
                $pickKeys
            ) {
                $key = implode(',', array_intersect_key($mapper->initial(), $pickKeys));

                return $pivot->moveTo($pivotMap[$key]);
            });
        }

        $finalFilter = array($relationFilters);

        if ($filters) {
            $finalFilter[] = $filters;
        }

        return $mapper->findAll($finalFilter, $options, $ttl);
    }

    /**
     * Returns mapper to relate.
     *
     * @param string|object $mapper
     *
     * @return Mapper
     */
    protected function prepareRelation($mapper): Mapper
    {
        if (!is_a($mapper, self::class, true) && (!is_string($mapper) || $aClass = class_exists($mapper))) {
            throw new \LogicException(sprintf('Mapper should be an instance of %s.', self::class));
        }

        if (isset($aClass)) {
            return new self($this->db, $mapper);
        }

        if (is_string($mapper)) {
            return new $mapper($this->db);
        }

        return $mapper;
    }

    /**
     * Returns relation filter.
     *
     * @param Mapper $mapper
     * @param string $relations
     * @param string $fallback
     * @param mixed  $relationsFilter
     * @param bool   $lock
     *
     * @return array
     */
    protected function filterRelation(
        Mapper $mapper,
        string $relations,
        string $fallback,
        $relationFilters = null,
        bool $lock = false
    ): array {
        $fw = $this->db->fw;
        $filters = array();

        foreach ($fw->split($relations) as $relation) {
            // foreignKey = localKey
            $pair = $fw->split($relation, '=');

            $filters[$pair[0]] = $this->get($pair[1] ?? $fallback);

            if ($lock) {
                $mapper->lock($pair[0], $filters[$pair[0]]);
            }
        }

        if (empty($filters)) {
            throw new \LogicException('No relation defined.');
        }

        $result = array($filters);

        if ($relationFilters) {
            $result[] = $relationFilters;
        }

        return $result;
    }

    /**
     * Commit row data.
     *
     * @param array|null $data
     * @param bool       $reset
     *
     * @return array
     */
    protected function commitRow(array $data = null, bool $reset = false): array
    {
        $row = array();

        foreach ($this->schema as $name => $schema) {
            $locked = $this->row[$name]['locked'] ?? false;
            $changed = false;

            if (!$reset && $locked) {
                $value = $this->row[$name]['value'];
                $changed = true;
            } elseif (null === $data) {
                $value = isset($this->row[$name]) ?
                    $this->row[$name]['value'] : $schema['default'];
            } else {
                $value = array_key_exists($name, $data) ?
                    $data[$name] : $schema['default'];
            }

            $initial = $value;
            $row[$name] = compact('changed', 'initial', 'locked', 'value');
        }

        return $row;
    }
}
