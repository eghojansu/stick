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
        $res = $this->factory(
            [$this->db->selectOne($this->table, $filter, $option, $ttl)]
        );

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
        return $this->factory($this->db->select($this->table, $filter, $option, $ttl));
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
        $res = $this->db->paginate($this->table, $page, $filter, $option, $ttl);
        $res['subset'] = $this->factory($res['subset']);

        return $res;
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
        if (!$this->map) {
            return $data;
        }

        $converted = [];
        foreach ($data as $record) {
            $converted[] = $this->arrToMap($record);
        }

        return $converted;
    }

    /**
     * Convert array to mapped class
     *
     * @param  array  $record
     *
     * @return object
     */
    protected function arrToMap(array $record)
    {
        $map = $this->map;
        $obj = new $map();

        foreach ($this->fields as $key => $value) {
            $set = 'set' . camelcase($key);

            if (method_exists($obj, $set) && isset($record[$key])) {
                $obj->$set($record[$key]);
            }
        }

        return $obj;
    }

    /**
     * Proxy to database method
     * Example:
     *     findOneUsername('foo') = findOne(['username'=>'foo'])
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $map = [
            'insert',
            'update',
            'delete',
            'count',
        ];

        if (in_array($method, $map)) {
            return $this->db->$method($this->source, ...$args);
        } elseif (istartswith('findoneby', $method)) {
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
