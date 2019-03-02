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
 * File bag.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class FileBag extends ParameterBag
{
    /**
     * @var array
     */
    protected static $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value): ParameterBag
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('An uploaded file must be an array.');
        }

        return parent::set($key, $this->convertFileInformation($value));
    }

    /**
     * Converts uploaded files to UploadedFile instances.
     *
     * @param array|UploadedFile $file A (multi-dimensional) array of uploaded file information
     *
     * @return array
     */
    protected function convertFileInformation(array $file): ?array
    {
        $file = $this->fixPhpFilesArray($file);

        if (is_array($file)) {
            $keys = array_keys($file);
            sort($keys);

            if ($keys == static::$fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = array(
                        'error' => $file['error'],
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'tmp_name' => $file['tmp_name'],
                        'size' => $file['size'],
                    );
                }
            } else {
                $file = array_map(array($this, 'convertFileInformation'), $file);

                if (array_keys($keys) === $keys) {
                    $file = array_filter($file);
                }
            }
        }

        return $file;
    }

    /**
     * Fixes a malformed PHP $_FILES array.
     *
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     *
     * This method fixes the array to look like the "normal" $_FILES array.
     *
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @return array
     */
    protected function fixPhpFilesArray(array $data): array
    {
        $keys = array_keys($data);
        sort($keys);

        if (static::$fileKeys != $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }

        $files = $data;

        foreach (static::$fileKeys as $k) {
            unset($files[$k]);
        }

        foreach ($data['name'] as $key => $name) {
            $files[$key] = $this->fixPhpFilesArray(array(
                'error' => $data['error'][$key],
                'name' => $name,
                'type' => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size' => $data['size'][$key],
            ));
        }

        return $files;
    }
}
