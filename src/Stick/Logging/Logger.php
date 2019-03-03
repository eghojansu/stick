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

namespace Fal\Stick\Logging;

/**
 * Logger utility.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Logger implements LoggerInterface
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var int
     */
    protected $logLevelThreshold;

    /**
     * Class constructor.
     *
     * @param string|null $directory
     * @param int|null    $logLevelThreshold
     */
    public function __construct(string $directory = null, int $logLevelThreshold = null)
    {
        $this->setDirectory($directory ?? '');
        $this->setLogLevelThreshold($logLevelThreshold ?? LogLevel::ERROR);
    }

    /**
     * {inheritdoc}.
     */
    public function log(int $level, string $message): LoggerInterface
    {
        if ($this->isLoggable($level)) {
            $prefix = $this->directory.'log_';
            $suffix = '.log';
            $files = glob($prefix.date('Y-m').'*'.$suffix);

            $file = $files[0] ?? $prefix.date('Y-m-d').$suffix;
            $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

            file_put_contents($file, $content, FILE_APPEND);
        }

        return $this;
    }

    /**
     * Returns log directory.
     *
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Assign log directory path.
     *
     * @param string $directory
     *
     * @return Logger
     */
    public function setDirectory(string $directory): Logger
    {
        $this->directory = $directory;

        if ($directory && !is_dir($directory)) {
            mkdir($directory);
        }

        return $this;
    }

    /**
     * Returns log level threshold.
     *
     * @return int
     */
    public function getLogLevelThreshold(): int
    {
        return $this->logLevelThreshold;
    }

    /**
     * Assign log level threshold.
     *
     * @param int $logLevelThreshold
     *
     * @return Logger
     */
    public function setLogLevelThreshold(int $logLevelThreshold): Logger
    {
        if ($logLevelThreshold < LogLevel::EMERGENCY || $logLevelThreshold > LogLevel::DEBUG) {
            throw new \LogicException('Invalid log level.');
        }

        $this->logLevelThreshold = $logLevelThreshold;

        return $this;
    }

    /**
     * Returns log files.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return Logger
     */
    public function logFiles(string $from = null, string $to = null): array
    {
        if (!$this->directory) {
            return array();
        }

        $pattern = $this->directory.'log_*.log';
        $files = glob($pattern);

        if (!$from) {
            return $files;
        }

        $fromTime = strtotime($from);
        $toTime = $to ? strtotime($to) : $fromTime;

        if (!$fromTime || !$toTime) {
            return array();
        }

        $filteredFiles = array();
        $start = 4;
        $end = 10;

        foreach ($files as $key => $file) {
            $fileTime = strtotime(substr(basename($file), $start, $end));

            if ($fileTime && ($fileTime >= $fromTime && $fileTime <= $toTime)) {
                $filteredFiles[] = $file;
            }
        }

        return $filteredFiles;
    }

    /**
     * Returns true if given level is loggable.
     *
     * @param int $level
     *
     * @return bool
     */
    protected function isLoggable(int $level): bool
    {
        return $this->directory && is_dir($this->directory) && $level <= $this->logLevelThreshold;
    }
}
