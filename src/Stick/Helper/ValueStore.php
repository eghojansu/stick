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

namespace Fal\Stick\Helper;

/**
 * To store static value in json file format.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ValueStore implements \ArrayAccess
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $saveAs;

    /**
     * @var array
     */
    protected $initial;

    /**
     * @var array
     */
    protected $data;

    /**
     * Class constructor.
     *
     * @param string      $filename
     * @param string|null $saveAs
     */
    public function __construct(string $filename, string $saveAs = null)
    {
        $this->filename = $filename;
        $this->saveAs = $saveAs ?? $filename;

        $this->reload();
    }

    /**
     * {inheritdoc}.
     */
    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetGet($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * {inheritdoc}.
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * {inheritdoc}.
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Returns original filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Returns temporary/saving filename.
     *
     * @return string
     */
    public function getSaveAs(): string
    {
        return $this->saveAs;
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Assign data.
     *
     * @param array $data
     *
     * @return ValueStore
     */
    public function setData(array $data): ValueStore
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Save data to file.
     *
     * @param bool $replace
     *
     * @return ValueStore
     */
    public function commit(bool $replace = false): ValueStore
    {
        if ($replace) {
            $commit = $this->data;
        } else {
            $commit = array_intersect_key($this->data, $this->initial);
        }

        file_put_contents($this->saveAs, json_encode($commit));

        return $this;
    }

    /**
     * Load json data.
     *
     * @return ValueStore
     */
    public function reload(): ValueStore
    {
        $this->data = $this->initial = array();

        $content = file_exists($this->saveAs) ? file_get_contents($this->saveAs) : file_get_contents($this->filename);

        if ($content) {
            $this->data = $this->initial = json_decode($content, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \LogicException(sprintf('JSON error: %s.', json_last_error_msg()));
            }
        }

        return $this;
    }
}
