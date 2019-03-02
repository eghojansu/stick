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

        $this->loadData();
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
     * @param bool  $merge
     *
     * @return ValueStore
     */
    public function setData(array $data, bool $merge = true): ValueStore
    {
        if ($merge) {
            $data = array_merge($this->data, $data);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Save data to file.
     *
     * @return ValueStore
     */
    public function commit(): ValueStore
    {
        file_put_contents($this->saveAs, json_encode($this->data));

        return $this;
    }

    /**
     * Load json data.
     */
    protected function loadData(): void
    {
        $this->data = array();

        $content = file_exists($this->saveAs) ? file_get_contents($this->saveAs) : file_get_contents($this->filename);

        if ($content) {
            $this->data = json_decode($content, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \LogicException(sprintf('JSON error: %s.', json_last_error_msg()));
            }
        }
    }
}
