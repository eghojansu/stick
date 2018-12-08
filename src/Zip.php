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
final class Zip
{
    /**
     * @var ZipArchive
     */
    private $archive;

    /**
     * Path prefix.
     *
     * @var string
     */
    private $prefix;

    /**
     * Class constructor.
     *
     * @param string      $file
     * @param string|null $flags
     * @param string|null $prefix
     */
    public function __construct(string $file, string $flags = null, string $prefix = null)
    {
        $this->prefix = $prefix;
        $this->open($file, $flags);
    }

    /**
     * Create instance.
     *
     * @param string      $file
     * @param string|null $flags
     * @param string|null $prefix
     *
     * @return Zip
     */
    public static function create(string $file, string $flags = null, string $prefix = null): Zip
    {
        return new self($file, $flags, $prefix);
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
     * Returns ZipArchive.
     *
     * @return ZipArchive
     */
    public function getArchive(): \ZipArchive
    {
        return $this->archive;
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
        $mDir = rtrim(str_replace('\\', '/', $dir), '/').'/';
        $directoryIterator = new \RecursiveDirectoryIterator($mDir);
        $iteratorIterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iteratorIterator, '~^'.$mDir.'.*~');
        $dotFiles = array('.', '..');
        $remove = '#^'.$mDir.'#';
        $prefix = ltrim($this->prefix.'/', '/');

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $localname = preg_replace($remove, '', $path);

            if (in_array($file->getFilename(), $dotFiles) ||
                ($patterns && !$this->isMatch($localname, $patterns)) ||
                ($excludes && $this->isMatch($localname, $excludes))) {
                continue;
            }

            $this->archive->addFile($path, $prefix.$localname);
        }

        return $this;
    }

    /**
     * Open file.
     *
     * @param string      $file
     * @param string|null $flags
     *
     * @return Zip
     */
    public function open(string $file, string $flags = null): Zip
    {
        if ($this->archive) {
            $this->archive->close();
        }

        $this->archive = new \ZipArchive();
        $open = $this->archive->open($file, self::flags($flags));

        if ($error = self::error($open)) {
            throw new \LogicException($error);
        }

        return $this;
    }

    /**
     * Returns number of file.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->archive->numFiles;
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
     * Convert glob to regexp.
     *
     * @param string $glob
     *
     * @return string
     */
    private function regexify(string $glob): string
    {
        $pattern = '/(\*\*)|(\*)|(\.)/';
        $prefix = $glob && '/' === $glob[0] ? '^' : '';

        return '~'.$prefix.preg_replace_callback($pattern, function ($m) {
            if ($m[1]) {
                return '(.*)';
            }

            if ($m[2]) {
                return '([^\\/]+)';
            }

            return '(\.)';
        }, trim($glob, '/')).'~';
    }

    /**
     * Convert string flags to ZipArchive flags.
     *
     * @param string|null $flags
     *
     * @return int
     */
    private static function flags(string $flags = null): int
    {
        $out = 0;

        foreach (explode('|', strtoupper((string) $flags)) as $flag) {
            if (in_array($flag, array('OVERWRITE', 'CREATE', 'EXCL', 'CHECKONS'))) {
                $out |= constant('ZipArchive::'.$flag);
            }
        }

        return $out;
    }

    /**
     * Returns error message, null if no error.
     *
     * @param mixed $code
     *
     * @return string|null
     *
     * @codeCoverageIgnore
     */
    private static function error($code): ?string
    {
        if (true === $code) {
            return null;
        }

        switch ($code) {
            case \ZipArchive::ER_EXISTS:
                return 'File already exists.';

            case \ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent.';

            case \ZipArchive::ER_INVAL:
                return 'Invalid argument.';

            case \ZipArchive::ER_MEMORY:
                return 'Malloc failure.';

            case \ZipArchive::ER_NOENT:
                return 'No such file.';

            case \ZipArchive::ER_NOZIP:
                return 'Not a zip archive.';

            case \ZipArchive::ER_OPEN:
                return 'Can\'t open file.';

            case \ZipArchive::ER_READ:
                return 'Read error.';

            case \ZipArchive::ER_SEEK:
                return 'Seek error.';

            default:
                return null;
        }
    }

    /**
     * Proxy to ZipArchive method.
     *
     * @param string $method
     * @param args   $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->archive, $method)) {
            throw new \LogicException(sprintf('Call to undefined method ZipArchive::%s.', $method));
        }

        return $this->archive->$method(...$args);
    }
}
