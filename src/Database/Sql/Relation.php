<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Database\Sql;

class Relation implements \Iterator, \Countable, \ArrayAccess
{
    /** @var Mapper */
    protected $ref;

    /** @var string */
    protected $refId;

    /** @var Mapper */
    protected $target;

    /** @var string */
    protected $targetId;

    /** @var array */
    protected $option;

    /** @var array */
    protected $query = [];

    /** @var int */
    protected $ptr = 0;

    /** @var bool */
    protected $loaded = false;

    /**
     * Class constructor
     *
     * @param Mapper $ref
     * @param string $refId
     * @param Mapper $target
     * @param string $targetId
     * @param string $option
     */
    public function __construct(Mapper $ref, string $refId = 'id', Mapper $target = null, string $targetId = null, array $option = null)
    {
        $this->ref = $ref;
        $this->refId = $refId;
        $this->target = $target ?? (clone $ref)->reset();
        $this->targetId = $targetId ?? $ref->getTable() . '_id';
        $this->option = ((array) $option) + [
            'lookup' => null,
            'one' => $option['one'] ?? !isset($option['lookup']),
            'filter' => [],
            'option' => [],
            'ttl' => 0,
            'refId' => $ref->getTable() . '_' . $refId,
            'targetId' => $option['targetId'] ?? $this->target->getTable() . '_id',
        ];
        if ($this->option['one']) {
            $this->option['option']['limit'] = 1;
        }
    }

    /**
     * Load related mapper
     *
     * @param bool $reload
     *
     * @return Relation
     */
    public function load(bool $reload = false): Relation
    {
        if ($this->loaded && !$reload) {
            return $this;
        }

        $this->query = [];
        $this->ptr = 0;
        $this->loaded = true;

        if ($this->ref->unloaded()) {
            return $this;
        }

        $filter = array_merge(
            $this->option['filter'],
            $this->option['lookup'] ?
                [$this->targetId . ' []' => $this->getTargetId()] :
                [$this->targetId => $this->ref->get($this->refId)]
        );
        $this->query = $this->target->find(
            $filter,
            $this->option['option'],
            $this->option['ttl']
        );

        return $this;
    }

    /**
     * Valid complement
     *
     * @return bool
     */
    public function invalid(): bool
    {
        return !$this->valid();
    }

    /**
     * Move pointer to specified offset
     *
     * @param  int $offset
     *
     * @return Mapper|null
     */
    public function skip(int $offset = 1): ?Mapper
    {
        $this->load()->ptr += $offset;

        return $this->query[$this->ptr] ?? null;
    }

    /**
     * Rewind alias
     *
     * @return Mapper|null
     */
    public function first(): ?Mapper
    {
        return $this->rewind();
    }

    /**
     * Move pointer to last offset
     *
     * @return Mapper|null
     */
    public function last(): ?Mapper
    {
        return $this->skip($this->count() - $this->ptr - 1);
    }

    /**
     * Next complement
     *
     * @return Mapper|null
     */
    public function prev(): ?Mapper
    {
        return $this->skip(-1);
    }

    /**
     * Get many-to-many target ids
     *
     * @return array
     */
    protected function getTargetId(): array
    {
        $res = [];
        $filter = [$this->option['refId'] => $this->ref->get($this->refId)];
        $all = $this->ref->withTable($this->option['lookup'])->find($filter);

        foreach ($all as $ref) {
            $res[] = $ref->get($this->option['targetId']);
        }

        return $res;
    }

    /**
     * Get current related mapper count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->load()->query);
    }

    /**
     * Return the current element
     *
     * @return Mapper|null
     */
    public function current()
    {
        return $this->skip(0);
    }

    /**
     * Move forward to next element
     *
     * @return Mapper|null
     */
    public function next()
    {
        return $this->skip();
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->ptr;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->load()->query[$this->ptr]);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return Mapper|null
     */
    public function rewind()
    {
        return $this->skip(-$this->ptr);
    }

    /**
     * Map to MapperInterface::exists
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        $current = $this->skip(0);

        return $current ? $current->exists($offset) : false;
    }

    /**
     * Map to MapperInterface::get
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $current = $this->skip(0);

        return $current ? $current->get($offset) : null;
    }

    /**
     * Map to MapperInterface::set
     *
     * @param  string $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $current = $this->skip(0);

        if ($current) {
            $current->set($offset, $value);
        }
    }

    /**
     * Map to MapperInterface::clear
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $current = $this->skip(0);

        if ($current) {
            $current->clear($offset);
        }
    }

    /**
     * Offsetexists alias
     *
     * @param  string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Offsetget alias
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Offsetset alias
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Offsetunset alias
     *
     * @param  string $name
     *
     * @return void
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }
}
