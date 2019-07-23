<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Util;

/**
 * Common utils.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Common
{
    /**
     * Returns CamelCase to snake_case.
     *
     * @param string $text
     *
     * @return string
     */
    public static function snakeCase(string $text): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $text));
    }

    /**
     * Returns snake_case to camelCase.
     *
     * @param string $text
     *
     * @return string
     */
    public static function camelCase(string $text): string
    {
        return lcfirst(self::pascalCase($text));
    }

    /**
     * Returns snake_case to PascalCase.
     *
     * @param string $text
     *
     * @return string
     */
    public static function pascalCase(string $text): string
    {
        return str_replace('_', '', ucwords(str_replace('-', '_', $text), '_'));
    }

    /**
     * Returns camelCase to "Title Case".
     *
     * @param string $text
     *
     * @return string
     */
    public static function titleCase(string $text): string
    {
        return ucwords(str_replace('_', ' ', self::snakeCase($text)));
    }

    /**
     * Returns UPPER_SNAKE_CASE to Dash-Case.
     *
     * @param string $text
     *
     * @return string
     */
    public static function dashCase(string $text): string
    {
        return ucwords(str_replace('_', '-', strtolower($text)), '-');
    }

    /**
     * Returns class name from full namespace or instance of class.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function classname($class): string
    {
        return ltrim(
            strrchr('\\'.(is_object($class) ? get_class($class) : $class), '\\'),
            '\\'
        );
    }

    /**
     * Extending array_column functionality, using self index for every row.
     *
     * @param array $input
     * @param mixed $key
     * @param bool  $raw   No filter
     *
     * @return array
     */
    public static function arrColumn(array $input, $key, bool $raw = true): array
    {
        $result = array();

        foreach ($input as $id => $value) {
            if ($raw || $value[$key]) {
                $result[$id] = $value[$key];
            }
        }

        return $result;
    }

    /**
     * Remove trailing space.
     *
     * @param string $text
     *
     * @return string
     */
    public static function trimTrailingSpace(string $text): string
    {
        return preg_replace('/^\h+$/m', '', $text);
    }

    /**
     * Returns directory content recursively.
     *
     * @param string $dir
     *
     * @return RecursiveIteratorIterator
     */
    public static function files(string $dir): \RecursiveIteratorIterator
    {
        if (!is_dir($dir)) {
            throw new \LogicException(sprintf('Directory not exists: %s.', $dir));
        }

        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * Normalize line feed with new line.
     *
     * @param string $text
     *
     * @return string
     */
    public static function fixLinefeed(string $text): string
    {
        return preg_replace('/\r\n|\r/', "\n", $text);
    }

    /**
     * Returns file content with normalized line feed if needed.
     *
     * @param string $file
     * @param bool   $lf   Normalize linefeed
     *
     * @return string
     */
    public static function read(string $file, bool $lf = false): string
    {
        $content = is_file($file) ? file_get_contents($file) : '';

        return $lf && $content ? self::fixLinefeed($content) : $content;
    }

    /**
     * Write content to specified file.
     *
     * @param string $file
     * @param string $content
     * @param int    $append
     *
     * @return int Returns -1 if failed
     */
    public static function write(string $file, string $content, bool $append = false): int
    {
        $result = file_put_contents(
            $file,
            $content,
            LOCK_EX | ((int) $append * FILE_APPEND)
        );

        return false === $result ? -1 : $result;
    }

    /**
     * Returns true if file deleted successfully.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function delete(string $file): bool
    {
        return is_file($file) ? unlink($file) : false;
    }
}
