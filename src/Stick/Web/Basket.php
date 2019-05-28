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

namespace Fal\Stick\Web;

use Fal\Stick\Fw;
use Fal\Stick\Magic;

/**
 * Session based basket mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Basket extends Magic implements \Iterator, \Countable
{
    /** @var Fw */
    protected $fw;

    /** @var string */
    protected $key;

    /** @var int */
    protected $ptr = 0;

    /** @var array */
    protected $basket = array();

    /**
     * Class constructor.
     *
     * @param Fw     $fw
     * @param string $key
     */
    public function __construct(Fw $fw, string $key = 'basket')
    {
        $this->fw = $fw;
        $this->key = $key;
        $this->load();
    }

    /**
     * {inheritdoc}.
     */
    public function current()
    {
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
        return isset($this->basket[$this->ptr]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->basket);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return isset($this->basket[$this->ptr]) && array_key_exists($key, $this->basket[$this->ptr]);
    }

    /**
     * {@inheritdoc}
     */
    public function &get(string $key)
    {
        if (!$this->has($key)) {
            $this->basket[$this->ptr][$key] = null;
        }

        return $this->basket[$this->ptr][$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value): Magic
    {
        $this->basket[$this->ptr][$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rem(string $key): Magic
    {
        unset($this->basket[$this->ptr][$key]);

        return $this;
    }

    /**
     * Reset basket.
     *
     * @return Basket
     */
    public function reset(): Basket
    {
        $this->basket = array();
        $this->ptr = 0;

        return $this;
    }

    /**
     * Returns current row.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->valid() ? $this->basket[$this->ptr] : array();
    }

    /**
     * Sets row.
     *
     * @param array $row
     *
     * @return Basket
     */
    public function fromArray(array $row): Basket
    {
        $this->basket[$this->ptr] = $row;

        return $this;
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
     * Find rows by key.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $limit
     *
     * @return Basket
     */
    public function find(string $key, $value, int $limit = 0): Basket
    {
        $found = array();
        $ctr = 0;

        foreach ($this->load()->basket as $ptr => $row) {
            if (array_key_exists($key, $row) && $value == $row[$key]) {
                $found[] = $row;
                ++$ctr;

                if ($limit > 0 && $ctr >= $limit) {
                    break;
                }
            }
        }

        $this->basket = $found;

        return $this;
    }

    /**
     * Commit current basket to session.
     *
     * @return Basket
     */
    public function save(): Basket
    {
        foreach ($this->basket as $key => $row) {
            $_id = $row['_id'] ?? $key;

            $this->fw->set('SESSION.'.$this->key.'.'.$_id, compact('_id') + $row);
        }

        return $this;
    }

    /**
     * Remove current row.
     *
     * @return Basket
     */
    public function delete(): Basket
    {
        $this->basket = array_slice($this->basket, 0, $this->ptr, true) + array_slice($this->basket, $this->ptr + 1, null, true);
        $this->next();

        return $this->save();
    }

    /**
     * Load data from source.
     *
     * @return Basket
     */
    public function load(): Basket
    {
        $this->reset();

        foreach ($this->fw->get('SESSION.'.$this->key, array()) as $_id => $row) {
            $this->basket[] = compact('_id') + $row;
        }

        return $this;
    }

    /**
     * Drop basket.
     *
     * @return Basket
     */
    public function drop(): Basket
    {
        $this->reset()->fw->rem('SESSION.'.$this->key);

        return $this;
    }

    /**
     * Drop basket and returns current basket.
     *
     * @return array
     */
    public function checkout(): array
    {
        $basket = $this->basket;
        $this->drop();

        return $basket;
    }
}
