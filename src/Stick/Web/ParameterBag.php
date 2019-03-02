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

/**
 * Parameter bag.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ParameterBag
{
    /**
     * @var array
     */
    protected $data = array();

    /**
     * Class constructor.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->replace($data);
        }
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Replace data.
     *
     * @param array $data
     * @param bool  $replace
     * @param bool  $append
     *
     * @return ParameterBag
     */
    public function replace(array $data, bool $replace = true, bool $append = true): ParameterBag
    {
        if ($replace) {
            $this->data = array();
        }

        foreach ($data as $key => $value) {
            if ($append || !$this->exists($key)) {
                $this->set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Returns true if key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns key value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Assign key value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return ParameterBag
     */
    public function set(string $key, $value): ParameterBag
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Remove key value.
     *
     * @param string $key
     *
     * @return ParameterBag
     */
    public function clear(string $key): ParameterBag
    {
        unset($this->data[$key]);

        return $this;
    }
}
