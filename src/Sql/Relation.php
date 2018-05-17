<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Sql;

/**
 * Mapper Relation, simple class which act like array, proxy to Mapper.
 */
final class Relation implements \Iterator, \Countable, \ArrayAccess
{
    /** @var Mapper */
    private $ref;

    /** @var Mapper */
    private $target;

    /** @var string */
    private $refId;

    /** @var string */
    private $targetId;

    /** @var array */
    private $pivot;

    /** @var array */
    private $options;

    /** @var array */
    private $query = [];

    /** @var int */
    private $count = 0;

    /** @var int */
    private $ptr = 0;

    /** @var bool */
    private $loaded = false;

    /**
     * Class constructor.
     *
     * @param Mapper       $ref
     * @param Mapper       $target
     * @param string       $targetId
     * @param string       $refId
     * @param string|array $pivot
     * @param bool         $one
     * @param array        $options
     */
    public function __construct(Mapper $ref, Mapper $target = null, string $targetId = null, string $refId = null, $pivot = null, bool $one = null, array $options = null)
    {
        $this->ref = $ref;
        $this->target = $target ?? (clone $ref)->reset();
        $this->targetId = $targetId ?? $ref->getTable().'_id';
        $this->refId = $refId ?? 'id';
        $this->pivot = $pivot;
        $this->options = ((array) $options) + [
            'filter' => [],
            'options' => [],
            'ttl' => 0,
        ];
        $limit = $one ?? !$pivot;
        $this->options['options']['limit'] = $limit ? 1 : null;
    }

    /**
     * Move pointer to specified offset.
     *
     * @param int $offset
     *
     * @return Mapper|null
     */
    public function skip(int $offset = 1): ?Mapper
    {
        $this->load()->ptr += $offset;

        return $this->query[$this->ptr] ?? null;
    }

    /**
     * Rewind alias.
     *
     * @return Mapper|null
     */
    public function first(): ?Mapper
    {
        return $this->rewind();
    }

    /**
     * Move pointer to last offset.
     *
     * @return Mapper|null
     */
    public function last(): ?Mapper
    {
        return $this->skip($this->count() - $this->ptr - 1);
    }

    /**
     * Next complement.
     *
     * @return Mapper|null
     */
    public function prev(): ?Mapper
    {
        return $this->skip(-1);
    }

    /**
     * Load related mapper.
     *
     * @return Relation
     */
    private function load(): Relation
    {
        if ($this->loaded) {
            return $this;
        }

        $this->query = [];
        $this->count = 0;
        $this->ptr = 0;
        $this->loaded = true;

        if ($this->ref->dry()) {
            return $this;
        }

        $this->query = $this->target->findAll(array_merge($this->options['filter'], $this->buildFilter()), $this->options['options'], $this->options['ttl']);
        $this->count = count($this->query);

        return $this;
    }

    /**
     * Build filter based on current options.
     *
     * @return array
     */
    private function buildFilter(): array
    {
        if (!$this->pivot) {
            return [$this->targetId => $this->ref->get($this->refId)];
        }

        $use = (array) $this->pivot;
        $pivot = array_shift($use);
        $refId = $use[0] ?? $this->ref->getTable().'_id';
        $targetId = $use[1] ?? $this->target->getTable().'_id';
        $ids = [];

        foreach ($this->ref->withTable($pivot)->findAll([$refId => $this->ref->get($this->refId)]) as $ref) {
            $ids[] = $ref->get($targetId);
        }

        return [$this->refId.' []' => $ids];
    }

    /**
     * Ensure mapper is used.
     *
     * @return Mapper
     */
    private function map(): Mapper
    {
        return $this->skip(0) ?? $this->target->set($this->targetId, $this->ref->get($this->refId));
    }

    /**
     * Get current related mapper count.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->load()->count;
    }

    /**
     * Return the current element.
     *
     * @return Mapper|null
     */
    public function current()
    {
        return $this->skip(0);
    }

    /**
     * Move forward to next element.
     *
     * @return Mapper|null
     */
    public function next()
    {
        return $this->skip();
    }

    /**
     * Return the key of the current element.
     *
     * @return int
     */
    public function key()
    {
        return $this->ptr;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->load()->query[$this->ptr]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return Mapper|null
     */
    public function rewind()
    {
        return $this->skip(-$this->ptr);
    }

    /**
     * Map to MapperInterface::exists.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->map()->exists($offset);
    }

    /**
     * Map to MapperInterface::get.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->map()->get($offset);
    }

    /**
     * Map to MapperInterface::set.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->map()->set($offset, $value);
    }

    /**
     * Map to MapperInterface::clear.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->map()->clear($offset);
    }

    /**
     * Proxy to mapper method.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        return $this->map()->$method(...$args);
    }
}
