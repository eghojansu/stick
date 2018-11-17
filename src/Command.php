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

use Fal\Stick\Util\Cli;
use Fal\Stick\Util\Zip;

/**
 * Command helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Command
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var Cli
     */
    protected $cli;

    /**
     * @var array
     */
    protected $commands;

    /**
     * Class constructor.
     *
     * @param Fw  $fw
     * @param Cli $cli
     */
    public function __construct(Fw $fw, Cli $cli)
    {
        $this->fw = $fw;
        $this->cli = $cli;
        $this->commands = array(
            'build' => array(
                array($this, 'build'),
                'Build project',
            ),
            'help' => array(
                array($this, 'help'),
                'Display help',
            ),
            'version' => array(
                array($this, 'version'),
                'Display version',
            ),
        );
    }

    /**
     * Register route handler.
     *
     * @param Fw $fw
     */
    public static function register(Fw $fw): void
    {
        $fw->controller('Fal\\Stick\\Command', array(
            'GET / cli' => 'run',
            'GET /@command cli' => 'run',
            'GET /@command/* cli' => 'run',
        ));
    }

    /**
     * Handle command.
     *
     * @param string|null $command
     * @param mixed       ...$args
     */
    public function run($command = null, ...$args): void
    {
        try {
            $config = $this->findConfiguration();

            $this->addCommands($config['commands'] ?? array());
            $this->doRun($command ?? 'help', $config[$command] ?? array(), $args);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Help command.
     */
    public function help(): void
    {
        $max = max(array_map('strlen', array_keys($this->commands)));

        $this->cli->writeln('<comment>Usage:</comment>');
        $this->cli->writeln('  commands [options]');
        $this->cli->writeln();

        $this->cli->writeln('<comment>Commands:</comment>');

        foreach ($this->commands as $command => list($method, $desc)) {
            $this->cli->writeln("  <info>%-{$max}s</info>  %s", $command, $desc);
        }
    }

    /**
     * Version command.
     */
    public function version(): void
    {
        $this->cli->writeln('%s %s', $this->fw['PACKAGE'], $this->fw['VERSION']);
    }

    /**
     * Build project command.
     *
     * @param array $config
     */
    public function build(array $config)
    {
        $start = microtime(true);
        $cwd = getcwd();
        $dir = $this->fw['GET']['dir'] ?? $config['dir'] ?? $cwd;
        $destination = $this->fw['GET']['destination'] ?? $config['destination'] ?? null;
        $version = $this->fw['GET']['version'] ?? $config['version'] ?? 'dev';
        $checkout = isset($this->fw['GET']['checkout']) ?: ($config['checkout'] ?? false);

        if (!$dir || !is_dir($dir)) {
            throw new \LogicException(sprintf('Working directory not exists: "%s".', $dir));
        }

        if (!$destination || !is_dir($destination)) {
            throw new \LogicException(sprintf('Destination directory not exists: "%s".', $destination));
        }

        if (empty($version)) {
            throw new \LogicException('Please provide release version.');
        }

        chdir($dir);

        // @codeCoverageIgnoreStart
        if ($checkout) {
            $error = '/^(fatal|error)/i';
            $status = `git status`;

            if ($status && preg_match($error, $status)) {
                throw new \LogicException($status);
            }

            if ($status) {
                throw new \LogicException('Stage is not clean.');
            }

            $currentBranch = `git rev-parse --abbrev-ref HEAD`;

            if (preg_match($error, $currentBranch)) {
                throw new \LogicException($currentBranch);
            }

            $tmp = `git checkout $version`;

            if (preg_match($error, $tmp)) {
                throw new \LogicException($tmp);
            }
        }
        // @codeCoverageIgnoreEnd

        $destination = rtrim($this->fw->fixslashes(realpath($destination)), '/').'/';
        $dir = rtrim($this->fw->fixslashes($dir), '/').'/';
        $projectDir = basename($dir);
        $file = sprintf('%s%s-%s.zip', $destination, $projectDir, $version);
        $patterns = (array) ($config['add'] ?? array(
            'app/**',
            'public/**',
            'vendor/**',
            '.editorconfig',
            '.gitignore',
            'composer.json',
            'composer.lock',
            'LICENSE',
            'README.md',
        ));
        $excludes = (array) ($config['excludes'] ?? array(
            '.git',
            'vendor/**/tests/**',
        ));
        $merge = (array) ($config['merge'] ?? null);
        $mergeExcludes = (array) ($config['merge_excludes'] ?? null);

        if ($merge) {
            array_push($patterns, ...$merge);
        }

        if ($mergeExcludes) {
            array_push($excludes, ...$mergeExcludes);
        }

        $this->fw->delete($file);

        Zip::create($file, 'create', $projectDir)
            ->add($dir, $patterns, $excludes)
            ->close()
        ;

        // @codeCoverageIgnoreStart
        if ($checkout && isset($currentBranch)) {
            `git checkout $currentBranch`;
        }
        // @codeCoverageIgnoreEnd

        chdir($cwd);

        $this->cli->writeln("Build complete in <comment>%f</comment> ms.\n  Output: <info>%s</info>", microtime(true) - $start, $file);
    }

    /**
     * Do run.
     *
     * @param string $command
     * @param array  $config
     * @param array  $args
     */
    protected function doRun(string $command, array $config, array $args): void
    {
        if ('help' === $command && (isset($this->fw['GET']['version']) || isset($this->fw['GET']['v']))) {
            $command = 'version';
        }

        if (!isset($this->commands[$command])) {
            throw new \LogicException(sprintf('Command "%s" is not defined.', $command));
        }

        $this->fw->call($this->commands[$command][0], array($config, $args));
    }

    /**
     * Handle exception.
     *
     * @param Throwable $e
     */
    protected function handleException(\Throwable $e): void
    {
        $this->cli->writeln("<error>  %s  </error>\n  <comment>%s</comment>", get_class($e), $e->getMessage());
    }

    /**
     * Add commands from config.
     *
     * @param array $commands
     */
    protected function addCommands(array $commands): void
    {
        foreach ($commands as $command => $definition) {
            if (is_array($definition)) {
                $handler = reset($definition);
                $desc = next($definition);
            } else {
                $handler = $definition;
                $desc = null;
            }

            $grabbed = is_string($handler) ? $this->fw->grab($handler) : $handler;

            if (!is_callable($grabbed)) {
                throw new \LogicException(sprintf('Command "%s" handler is not callable.', $command));
            }

            $this->commands[$command] = array($grabbed, $desc ?: 'Command '.$command);
        }
    }

    /**
     * Find configuration.
     *
     * @return array
     */
    protected function findConfiguration(): array
    {
        $cwd = getcwd().'/';
        $implicit = $this->fw['GET']['config'] ?? null;
        $files = array(
            $implicit,
            $implicit ? $cwd.$implicit : null,
            $cwd.'.stick.dist',
            $cwd.'.stick',
        );

        foreach ($files as $i => $file) {
            if ($file && is_file($file)) {
                return (array) Fw::requireFile($file);
            }

            if ($i <= 1 && null != $file) {
                throw new \LogicException(sprintf('Configuration file is not found: "%s".', $file));
            }
        }

        return array();
    }
}
