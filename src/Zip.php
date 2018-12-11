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
 * Zip helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Zip extends \ZipArchive
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $caseless;

    /**
     * Class constructor.
     *
     * @param string      $filepath
     * @param string      $flags
     * @param string|null $prefix
     * @param bool        $caseless
     */
    public function __construct(string $filepath, int $flags = 0, string $prefix = null, bool $caseless = true)
    {
        $this->prefix = $prefix;
        $this->caseless = $caseless;
        $this->open($filepath, $flags);
    }

    /**
     * Returns prefix.
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Sets prefix.
     *
     * @param string $prefix
     *
     * @return Zip
     */
    public function setPrefix(string $prefix): Zip
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Returns match case.
     *
     * @return bool
     */
    public function isCaseless(): bool
    {
        return $this->caseless;
    }

    /**
     * Sets match case.
     *
     * @param bool $caseless
     *
     * @return Zip
     */
    public function setCaseless(bool $caseless): Zip
    {
        $this->caseless = $caseless;

        return $this;
    }

    /**
     * Add directory with patterns and excludes in glob format.
     *
     * @param string     $dir
     * @param array|null $patterns
     * @param array|null $excludes
     *
     * @return Zip
     */
    public function add(string $dir, array $patterns = null, array $excludes = null): Zip
    {
        $realpath = realpath($dir);
        $cut = strlen($realpath);
        $prefix = rtrim($this->prefix.'/', '/');
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_FILEINFO;
        $directoryIterator = new \RecursiveDirectoryIterator($realpath, $flags);
        $files = new \RecursiveIteratorIterator($directoryIterator);

        foreach ($files as $file) {
            $filepath = $file->getRealPath();
            $localname = substr($filepath, $cut);

            if (($patterns && !$this->isMatch($localname, $patterns)) ||
                ($excludes && $this->isMatch($localname, $excludes))) {
                continue;
            }

            $this->addFile($filepath, $prefix.$localname);
        }

        return $this;
    }

    /**
     * Check if path is match patterns.
     *
     * @param string $path
     * @param array  $patterns
     *
     * @return bool
     */
    private function isMatch(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($this->regexify($pattern), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert glob expression to regexp.
     *
     * Code from github.com/fitzgen/glob-to-regexp.
     *
     * @param string $glob
     *
     * @return string
     */
    private function regexify(string $glob): string
    {
        $inGroup = false;
        $wild = '';

        for ($i = 0, $len = strlen($glob); $i < $len; ++$i) {
            $char = $glob[$i];

            if (false !== strpos('/$^+.()=!|', $char)) {
                $wild .= '\\'.$char;
            } elseif ('?' === $char) {
                $wild .= '.';
            } elseif ('{' === $char) {
                $wild .= '(';
                $inGroup = true;
            } elseif ('}' === $char) {
                $wild .= ')';
                $inGroup = false;
            } elseif (',' === $char) {
                if ($inGroup) {
                    $wild .= '|';
                } else {
                    $wild .= '\\,';
                }
            } elseif ('*' === $char) {
                $prevChar = $glob[$i - 1] ?? '';
                $starCount = 1;

                while (($glob[$i + 1] ?? '') === '*') {
                    ++$starCount;
                    ++$i;
                }

                $nextChar = $glob[$i + 1] ?? '';
                $isGlobstar = $starCount > 1 && '/' === $prevChar && '/' === $nextChar;

                if ($isGlobstar) {
                    $wild .= '((?:[^\/]*(?:\/|$))*)';
                    ++$i;
                } else {
                    $wild .= '([^\/]*)';
                }
            } else {
                $wild .= $char;
            }
        }

        return '/^'.$wild.'$/'.($this->caseless ? 'i' : '');
    }
}
