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
            'init' => array(
                array($this, 'init'),
                'Initialize project structure',
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
     * Initialize project structure.
     */
    public function init(): void
    {
        $start = microtime(true);
        $dir = $this->fw['GET']['dir'] ?? null;

        if (!$dir || !is_dir($dir)) {
            throw new \LogicException(sprintf('Destination directory not exists: "%s".', $dir));
        }

        $dir = rtrim($this->fw->fixslashes($dir), '/').'/';

        $this->fw->mkdir($dir.'app/db');
        $this->fw->mkdir($dir.'app/src/Controller');
        $this->fw->mkdir($dir.'app/src/Form');
        $this->fw->mkdir($dir.'app/src/Mapper');
        $this->fw->mkdir($dir.'app/template');
        $this->fw->mkdir($dir.'public/');

        touch($dir.'app/db/.gitkeep');
        touch($dir.'app/src/Controller/.gitkeep');
        touch($dir.'app/src/Form/.gitkeep');
        touch($dir.'app/src/Mapper/.gitkeep');
        touch($dir.'app/template/.gitkeep');

        file_put_contents($dir.'public/robots.txt', "User-agent: *\nDisallow: /");
        file_put_contents($dir.'public/index.php', "<?php\n\n".
            "require __DIR__.'/../vendor/autoload.php';\n\n".
            "Fal\\Stick\\Fw::createFromGlobals()\n".
            "    ->registerShutdownHandler()\n".
            "    ->config(__DIR__.'/../app/env.php')\n".
            "    ->run()\n".
            ";\n"
        );
        file_put_contents($dir.'app/.htaccess', 'Deny from all');
        file_put_contents($dir.'app/config.dist.php', "<?php\n\n".
            "return array(\n".
            "    'db' => array(\n".
            "        'dsn' => 'mysql:host=localhost;dbname=db_project',\n".
            "        'username' => 'root',\n".
            "        'password' => null,\n".
            "    ),\n".
            "    'cache' => 'auto',\n".
            "    'debug' => false,\n".
            "    'log' => dirname(__DIR__).'/var/log/',\n".
            "    'threshold' => 'error',\n".
            "    'temp' => dirname(__DIR__).'/var/',\n".
            ");\n"
        );
        file_put_contents($dir.'app/env.php', "<?php\n\n".
            "\$config = is_file(__DIR__.'/config.dist.php') ? require __DIR__.'/config.dist.php' : array();\n\n".
            "if (is_file(__DIR__.'/config.php')) {\n".
            "    \$config = array_replace_recursive(\$config, require __DIR__.'/config.php');\n".
            "}\n\n".
            "return array(\n".
            "    'APP_DIR' => __DIR__.'/',\n".
            "    'DB_DSN' => \$config['db']['dsn'] ?? null,\n".
            "    'DB_USERNAME' => \$config['db']['username'] ?? null,\n".
            "    'DB_PASSWORD' => \$config['db']['password'] ?? null,\n".
            "    'CACHE' => \$config['cache'] ?? null,\n".
            "    'DEBUG' => \$config['debug'] ?? false,\n".
            "    'LOG' => \$config['log'] ?? null,\n".
            "    'THRESHOLD' => \$config['threshold'] ?? 'error',\n".
            "    'TEMP' => \$config['temp'] ?? dirname(__DIR__).'/var/',\n".
            "    'controllers' => require __DIR__.'/controllers.php',\n".
            "    'events' => require __DIR__.'/events.php',\n".
            "    'routes' => require __DIR__.'/routes.php',\n".
            "    'rules' => require __DIR__.'/services.php',\n".
            ");\n"
        );
        file_put_contents($dir.'app/controllers.php', "<?php\n\n".
            "return array(\n".
            ");\n"
        );
        file_put_contents($dir.'app/events.php', "<?php\n\n".
            "return array(\n".
            ");\n"
        );
        file_put_contents($dir.'app/routes.php', "<?php\n\n".
            "return array(\n".
            "    array('GET home /', function() {\n".
            "        return 'Welcome home, Vanilla lover!';\n".
            "    }),\n".
            ");\n"
        );
        file_put_contents($dir.'app/services.php', "<?php\n\n".
            "return array(\n".
            "    array('Fal\\\\Stick\\\\Sql\\\\Connection', array(\n".
            "        'args' => array(\n".
            "            'fw' => '%fw%',\n".
            "            'dsn' => '%DB_DSN%',\n".
            "            'username' => '%DB_USERNAME%',\n".
            "            'password' => '%DB_PASSWORD%',\n".
            "        ),\n".
            "    )),\n".
            "    array('Fal\\\\Stick\\\\Template\\\\Template', array(\n".
            "        'args' => array(\n".
            "            'fw' => '%fw%',\n".
            "            'paths' => __DIR__.'/template/',\n".
            "        ),\n".
            "    )),\n".
            "    array('html', 'Fal\\\\Stick\\\\Html\\\\Html'),\n".
            ");\n"
        );
        file_put_contents($dir.'README.md', "README\n".
            "======\n\n".
            'Thank you.'
        );
        file_put_contents($dir.'.stick.dist', "<?php\n\n".
            "return array(\n".
            "    'commands' => array()\n".
            ");\n"
        );
        file_put_contents($dir.'.php_cs.dist', "<?php\n\n".
            "\$finder = PhpCsFixer\Finder::create()\n".
            "    ->in(__DIR__.'/app')\n".
            "    ->in(__DIR__.'/public')\n".
            "    ->notPath('template')\n".
            "    ->name('*.php')\n".
            ";\n\n".
            "return PhpCsFixer\Config::create()\n".
            "    ->setRules(array(\n".
            "        '@PSR2' => true,\n".
            "        '@Symfony' => true,\n".
            "        'array_syntax' => array('syntax' => 'long'),\n".
            "    ))\n".
            "    ->setFinder(\$finder)\n".
            ");\n"
        );
        file_put_contents($dir.'.editorconfig', "root = true\n\n".
            "[*]\n".
            "indent_style = space\n".
            "indent_size = 4\n".
            "end_of_line = lf\n".
            "charset = utf-8\n".
            "trim_trailing_whitespace = true\n".
            "\n".
            "[*.php]\n".
            "insert_final_newline = true\n".
            "\n".
            "[app/template/**/*.php]\n".
            "indent_size = 2\n".
            'insert_final_newline = false'
        );
        file_put_contents($dir.'.gitignore', "/node_modules/\n".
            "/xdev/\n".
            "/var/\n".
            "/vendor/\n".
            "/.php_cs.cache\n".
            '/app/config.php'
        );

        $composer = $dir.'composer.json';
        $json = is_file($composer) ? json_decode(file_get_contents($composer), true) : array();
        $json['autoload']['psr-4']['App\\'] = 'app/src/';

        file_put_contents($composer, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->cli->writeln('Project initialized in <comment>%f</comment> at <info>%s</info>', microtime(true) - $start, $dir);
    }

    /**
     * Build project command.
     *
     * @param array $config
     */
    public function build(array $config): void
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
