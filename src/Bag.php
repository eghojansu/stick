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

namespace Fal\Stick;

/**
 * Magic class implementation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Bag extends Magic
{
    /**
     * @var array
     */
    protected $_data;

    /**
     * Class constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        $this->_data = (array) $data;
    }

    /**
     * Returns all data.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->_data;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $ref = $this->ref($key, false);

        return isset($ref);
    }

    /**
     * {@inheritdoc}
     */
    public function &get(string $key)
    {
        $ref = &$this->ref($key);

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $val): Magic
    {
        $ref = &$this->ref($key);
        $ref = $val;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): Magic
    {
        $this->unref($key);

        return $this;
    }

    /**
     * Returns variables reference.
     *
     * It allows you to use dot notation to access member of an array.
     *
     * @param string $key
     * @param bool   $add
     *
     * @return mixed
     */
    protected function &ref(string $key, bool $add = true)
    {
        $null = null;
        $parts = explode('.', $key);

        if ($add) {
            $var = &$this->_data;
        } else {
            $var = $this->_data;
        }

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = array();
            }

            if ($add || array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                $var = $null;
                break;
            }
        }

        return $var;
    }

    /**
     * Remove member of variables.
     *
     * It allows you to use dot notation to remove member of an array.
     *
     * @param string $key
     */
    protected function unref(string $key): void
    {
        $parts = explode('.', $key);
        $last = array_pop($parts);
        $first = $parts[0] ?? $last;
        $end = count($parts) - 1;
        $var = &$this->_data;

        foreach ($parts as $part) {
            if ($var && array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                break;
            }
        }

        unset($var[$last]);
    }
}
