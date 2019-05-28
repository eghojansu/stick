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

namespace Fal\Stick\Util;

use Fal\Stick\Magic;

/**
 * To store static value in json file format.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ValueStore extends Magic
{
    /** @var string Source file */
    protected $filename;

    /** @var string Save file */
    protected $saveAs;

    /** @var array Initial data */
    protected $initial;

    /** @var array Values */
    protected $values;

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
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * {inheritdoc}.
     */
    public function &get(string $key)
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        throw new \LogicException(sprintf('Key not found: %s.', $key));
    }

    /**
     * {inheritdoc}.
     */
    public function set(string $key, $value): Magic
    {
        $this->values[$key] = $value;

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function rem(string $key): Magic
    {
        unset($this->values[$key]);

        return $this;
    }

    /**
     * Returns values.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->values;
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
     * Merge data.
     *
     * @param array $data
     *
     * @return ValueStore
     */
    public function merge(array $data): ValueStore
    {
        $this->values = array_replace_recursive($this->values, $data);

        return $this;
    }

    /**
     * Replace data.
     *
     * @param array $data
     *
     * @return ValueStore
     */
    public function replace(array $data): ValueStore
    {
        $this->values = $data;

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
            $commit = $this->values;
        } else {
            $commit = array_intersect_key($this->values, $this->initial);
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
        $this->values = $this->initial = array();

        $content = file_exists($this->saveAs) ?
            file_get_contents($this->saveAs) :
            file_get_contents($this->filename);

        if ($content) {
            $this->values = $this->initial = json_decode($content, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \LogicException(sprintf(
                    'JSON error: %s.',
                    json_last_error_msg()
                ));
            }
        }

        return $this;
    }
}
