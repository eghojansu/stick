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
 * Header bag.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class HeaderBag extends ParameterBag
{
    /**
     * Returns first value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function first(string $key)
    {
        return $this->data[$key][0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value): ParameterBag
    {
        $mKey = $key === strtoupper($key) ? str_replace('_', '-', ucwords(strtolower($key), '_')) : $key;

        if (empty($this->data[$mKey])) {
            $this->data[$mKey] = array();
        }

        if (is_array($value)) {
            array_push($this->data[$mKey], ...array_values($value));
        } else {
            $this->data[$mKey][] = $value;
        }

        return $this;
    }

    /**
     * Update key value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return HeaderBag
     */
    public function update(string $key, $value): HeaderBag
    {
        $mKey = $key === strtoupper($key) ? str_replace('_', '-', ucwords(strtolower($key), '_')) : $key;
        $this->data[$mKey] = array();

        return $this->set($mKey, $value);
    }
}
