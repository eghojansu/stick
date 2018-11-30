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
 * Extensible console command wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Console
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
     * Registered commands.
     *
     * @var array
     */
    public $commands;

    /**
     * Class constructor.
     *
     * @param Fw  $fw
     * @param Cli $cli
     */
    public function __construct(Fw $fw, Cli $cli)
    {
        if (!isset($fw['RULES']['Fal\\Stick\\Console'])) {
            $fw->setRule('Fal\\Stick\\Console', $this);
        }

        if (!isset($fw['RULES']['Fal\\Stick\\Util\\Cli'])) {
            $fw->setRule('Fal\\Stick\\Util\\Cli', $cli);
        }

        $this->fw = $fw;
        $this->cli = $cli;

        $this->addCommand('help', array(
            'run' => 'Fal\\Stick\\Console::helpCommand',
            'desc' => 'Display this help message',
            'options' => array(
                array('version', 'v', false, 'Display this application version'),
            ),
            'args' => array(
                array('command', null, 'The command name <comment>[default: "help"]</comment>'),
            ),
        ));
        $this->addCommand('init', array(
            'run' => 'Fal\\Stick\\Console::initCommand',
            'desc' => 'Initialize project structure',
            'options' => array(
                array('working-dir', 'd', null, 'Working directory'),
            ),
        ));
        $this->addCommand('build', array(
            'run' => 'Fal\\Stick\\Console::buildCommand',
            'desc' => 'Build project and compress as zip',
            'options' => array(
                array('working-dir', 'd', null, 'Working directory <comment>default=$CWD</comment>'),
                array('destination', 'i', null, 'Destination directory'),
                array('version', 'v', 'dev', 'Build as version'),
                array('checkout', 'o', false, 'Checkout given git version'),
                array('add', null, 'app/**;public/**;vendor/**;.editorconfig;.gitignore;composer.json;composer.lock;LICENSE;README.md', 'Add patterns'),
                array('excludes', null, '.git;vendor/**/tests/**', 'Exclude patterns'),
                array('merge', null, null, 'Merge with defaults add patterns'),
                array('merge_excludes', null, null, 'Merge with defaults exclude patterns'),
            ),
            'help' => 'Options <comment>add</comment>, <comment>excludes</comment>, <comment>merge</comment>, and <comment>merge_excludes</comment> can be separated by comma, semi-colon or pipeline.',
        ));
        $this->addCommand('setup', array(
            'run' => 'Fal\\Stick\\Console::setupCommand',
            'desc' => 'Run project setup',
            'options' => array(
                array('file', null, 'VERSION', 'File to save installed version'),
                array('versions', null, null, 'List of versions install instructions'),
            ),
            'help' => '<comment>Note:</comment> Better to save install instruction in configuration file.',
        ));
    }

    /**
     * Register route handler.
     *
     * @param Fw $fw
     */
    public static function register(Fw $fw): void
    {
        $fw->controller('Fal\\Stick\\Console', array(
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
            $options = $this->fw['GET'] ?? array();
            $config = $this->findConfiguration($options['config'] ?? null);
            $default = (string) ($config['default_command'] ?? 'help');
            $envFile = (string) ($config['env_file'] ?? null);
            unset($options['config']);

            // load given environment file
            $this->fw->config($envFile);

            foreach ((array) ($config['commands'] ?? null) as $name => $definition) {
                $this->addCommand($name, $definition);
            }

            $this->doRun($command ?? $default, (array) ($config[$command] ?? null), $options, $args);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Add command.
     *
     * @param string $name
     * @param mixed  $definition
     * @param array  $commands
     *
     * @return Console
     */
    public function addCommand(string $name, $definition): Console
    {
        $command = array_merge(array(
            'name' => $name,
            'run' => $definition,
            'desc' => 'Command '.$name,
            'args' => null,
            'options' => null,
            'help' => null,
        ), is_array($definition) ? $definition : array());

        if (is_string($command['run'])) {
            $command['run'] = $this->fw->grab($command['run']);
        }

        if (!is_callable($command['run'])) {
            throw new \LogicException(sprintf('Command "%s" is not callable.', $name));
        }

        if (!is_string($command['desc'])) {
            $command['desc'] = (string) $command['desc'];
        }

        if (!is_array($command['args'])) {
            $command['args'] = (array) $command['args'];
        }

        if (!is_array($command['options'])) {
            $command['options'] = (array) $command['options'];
        }

        if (!is_array($command['help'])) {
            $command['help'] = (array) $command['help'];
        }

        foreach ($command['args'] as $key => &$args) {
            if (!is_array($args) || 3 !== count($args)) {
                throw new \LogicException(sprintf('Command "%s", definition of argument-%s should be array with 3 elements.', $name, $key));
            }

            $args = array_values($args);
            unset($args);
        }

        foreach ($command['options'] as $key => &$options) {
            if (!is_array($options) || 4 !== count($options)) {
                throw new \LogicException(sprintf('Command "%s", definition of option-%s should be array with 4 elements.', $name, $key));
            }

            $options = array_values($options);
            unset($options);
        }

        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * Do run.
     *
     * @param string $name
     * @param array  $config
     * @param array  $options
     * @param array  $args
     */
    protected function doRun(string $name, array $config, array $options, array $args): void
    {
        if (!isset($this->commands[$name])) {
            throw new \LogicException(sprintf('Command "%s" is not defined.', $name));
        }

        if (isset($options['help'])) {
            unset($options['help']);
            array_unshift($args, $name);

            $name = 'help';
        }

        $this->fw->call($this->commands[$name]['run'], array(
            $this->resolveOptions($this->commands[$name]['options'], $options, $config),
            $this->resolveArgs($this->commands[$name]['args'], $args),
            array(
                'name' => $name,
                'args' => $this->commands[$name]['args'],
                'options' => $this->commands[$name]['options'],
            ),
        ));
    }

    /**
     * Resolve options.
     *
     * @param array $definitions
     * @param array $options
     * @param array $config
     *
     * @return array
     */
    protected function resolveOptions(array $definitions, array $options, array $config): array
    {
        $result = array();

        foreach ($definitions as list($name, $alias, $default)) {
            $result[$name] = $options[$name] ?? $options[$alias] ?? $config[$name] ?? $default;
        }

        return $result;
    }

    /**
     * Resolve args.
     *
     * @param array $definitions
     * @param array $args
     *
     * @return array
     */
    protected function resolveArgs(array $definitions, array $args): array
    {
        $ptr = 0;
        $result = array();

        foreach ($definitions as list($name, $default)) {
            $result[$name] = $args[$ptr++] ?? $default;
        }

        return $result;
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
     * Find configuration.
     *
     * @param string|null $configuration
     *
     * @return array
     */
    protected function findConfiguration(string $configuration = null): array
    {
        $cwd = getcwd().'/';
        $files = array(
            $configuration,
            $configuration ? $cwd.$configuration : null,
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

    /**
     * Show help message.
     *
     * @param Fw      $fw
     * @param Console $console
     * @param Cli     $cli
     * @param array   $options
     * @param array   $args
     * @param array   $def
     */
    public static function helpCommand(Fw $fw, Console $console, Cli $cli, array $options, array $args, array $def): void
    {
        $command = $args['command'] ?? $def['name'];

        if ($self = $command === $def['name']) {
            $console->cli->writeln('<info>%s</info> version <comment>%s</comment>', $fw['PACKAGE'], $fw['VERSION']);

            if (false !== $options['version']) {
                return;
            }

            $cli->writeln();
        }

        $reducer = function ($carry, $item) { return max($carry, strlen($item[0]) + 2); };
        $maxArg = array_reduce($console->commands[$command]['args'], $reducer, 0);
        $maxOption = array_reduce($console->commands[$command]['options'], $reducer, 9);

        $cli->writeln('<comment>Usage:</comment>');
        $cli->writeln('  %s %s %s', $self ? 'command' : $command, $maxOption ? '[options]' : null, $maxArg ? '[arguments]' : null);

        if ($maxArg) {
            $cli->writeln();
            $cli->writeln('<comment>Arguments:</comment>');

            foreach ($console->commands[$command]['args'] as list($name, $default, $desc)) {
                $suffix = '';

                if (null !== $default && is_scalar($default)) {
                    $suffix = ' <comment>default='.(is_string($default) ? $default : var_export($default, true)).'</comment>';
                }

                $cli->writeln("  <info>%-{$maxArg}s</info> %s%s", $name, $desc, $suffix);
            }
        }

        $cli->writeln();
        $cli->writeln('<comment>Options:</comment>');

        foreach ($console->commands[$command]['options'] as list($name, $alias, $default, $desc)) {
            if ('config' === $name) {
                continue;
            }

            if ($alias) {
                $alias = '-'.$alias.',';
            }

            $suffix = '';

            if (null !== $default && is_scalar($default)) {
                $suffix = ' <comment>default='.(is_string($default) ? $default : var_export($default, true)).'</comment>';
            }

            $cli->writeln("  <info>%-3s %-{$maxOption}s</info> %s%s", $alias, '--'.$name, $desc, $suffix);
        }

        // globals options
        $cli->writeln("%6s<info>%-{$maxOption}s</info> Configuration file to load", '', '--config');

        if ($console->commands[$command]['help']) {
            $cli->writeln();

            foreach ($console->commands[$command]['help'] as $help) {
                $cli->writeln($help);
            }
        }

        if ($self) {
            $maxCommand = max(array_map('strlen', array_keys($console->commands)));

            $cli->writeln();
            $cli->writeln('<comment>Commands:</comment>');

            foreach ($console->commands as $def) {
                $cli->writeln("  <info>%-{$maxCommand}s</info>  %s", $def['name'], $def['desc']);
            }
        }
    }

    /**
     * Initialize project structure.
     *
     * @param Fw    $fw
     * @param Cli   $cli
     * @param array $options
     */
    public static function initCommand(Fw $fw, Cli $cli, array $options): void
    {
        $fw->mark();

        if (!$options['working-dir'] || !is_dir($options['working-dir'])) {
            throw new \LogicException(sprintf('Destination directory not exists: "%s".', $options['working-dir']));
        }

        $wd = rtrim($fw->fixslashes($options['working-dir']), '/').'/';

        $dirs = array(
            'app/src/Controller',
            'app/src/Form',
            'app/src/Mapper',
            'app/template',
            'public/',
        );
        $files = array(
            'app/src/Controller/.gitkeep',
            'app/src/Form/.gitkeep',
            'app/src/Mapper/.gitkeep',
            'app/template/.gitkeep',
        );
        $contents = array(
            'public/robots.txt' => "User-agent: *\nDisallow: /",
            'public/index.php' => "<?php\n\n".
                "require __DIR__.'/../vendor/autoload.php';\n\n".
                "Fal\\Stick\\Fw::createFromGlobals()\n".
                "    ->registerShutdownHandler()\n".
                "    ->config(__DIR__.'/../app/env.php')\n".
                "    ->run()\n".
                ";\n",
            'app/config.dist.php' => "<?php\n\n".
                "return array(\n".
                "    'db' => array(\n".
                "        'dsn' => 'mysql:host=localhost;dbname=db_project',\n".
                "        'username' => 'root',\n".
                "        'password' => null,\n".
                "    ),\n".
                "    'cache' => 'fallback',\n".
                "    'debug' => false,\n".
                "    'log' => dirname(__DIR__).'/var/log/',\n".
                "    'threshold' => 'error',\n".
                "    'temp' => dirname(__DIR__).'/var/',\n".
                ");\n",
            'app/env.php' => "<?php\n\n".
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
                ");\n",
            'app/controllers.php' => "<?php\n\n".
                "return array(\n".
                ");\n",
            'app/events.php' => "<?php\n\n".
                "return array(\n".
                ");\n",
            'app/routes.php' => "<?php\n\n".
                "return array(\n".
                "    array('GET home /', function() {\n".
                "        return 'Welcome home, Vanilla lover!';\n".
                "    }),\n".
                ");\n",
            'app/services.php' => "<?php\n\n".
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
                ");\n",
            'README.md' => "README\n".
                "======\n\n".
                'Thank you.',
            '.stick.dist' => "<?php\n\n".
                "return array(\n".
                "    'commands' => array()\n".
                ");\n",
            '.php_cs.dist' => "<?php\n\n".
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
                ");\n",
            '.editorconfig' => "root = true\n\n".
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
                'insert_final_newline = false',
            '.gitignore' => "/coverage-*\n".
                "/node_modules/\n".
                "/xdev/\n".
                "/var/\n".
                "/vendor/\n".
                "/.php_cs.cache\n".
                '/app/config.php',
        );

        foreach ($dirs as $dir) {
            $fw->mkdir($wd.$dir);
        }

        foreach ($files as $file) {
            if (!is_file($wd.$file)) {
                touch($wd.$file);
            }
        }

        foreach ($contents as $file => $content) {
            if (!is_file($wd.$file)) {
                $fw->write($wd.$file, $content);
            }
        }

        $composer = $wd.'composer.json';
        $json = is_file($composer) ? json_decode($fw->read($composer), true) : array();
        $json['autoload']['psr-4']['App\\'] = 'app/src/';

        $fw->write($composer, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $cli->writeln('Project initialized in <comment>%fms</comment> at <info>%s</info>', $fw->ellapsed(), realpath($wd));
    }

    /**
     * Build project command.
     *
     * @param Fw    $fw
     * @param Cli   $cli
     * @param array $options
     */
    public static function buildCommand(Fw $fw, Cli $cli, array $options): void
    {
        $fw->mark();

        if ($options['working-dir'] && !is_dir($options['working-dir'])) {
            throw new \LogicException(sprintf('Working directory not exists: "%s".', $options['working-dir']));
        }

        if (!$options['destination'] || !is_dir($options['destination'])) {
            throw new \LogicException(sprintf('Destination directory not exists: "%s".', $options['destination']));
        }

        if (empty($options['version'])) {
            throw new \LogicException('Please provide release version.');
        }

        $cwd = getcwd();

        if ($options['working-dir']) {
            chdir($options['working-dir']);
        }

        // @codeCoverageIgnoreStart
        if ($options['checkout']) {
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

            $checkout = `git checkout $options[version]`;

            if (preg_match($error, $checkout)) {
                throw new \LogicException($checkout);
            }
        }
        // @codeCoverageIgnoreEnd

        $destination = rtrim($fw->fixslashes(realpath($options['destination'])), '/').'/';
        $dir = rtrim($fw->fixslashes($options['working-dir']), '/').'/';
        $projectDir = basename($dir);
        $file = sprintf('%s%s-%s.zip', $destination, $projectDir, $options['version']);
        $patterns = $fw->split($options['add']);
        $excludes = $fw->split($options['excludes']);

        if ($options['merge']) {
            array_push($patterns, ...$fw->split($options['merge']));
        }

        if ($options['merge_excludes']) {
            array_push($excludes, ...$fw->split($options['merge_excludes']));
        }

        $fw->delete($file);

        Zip::create($file, 'create', $projectDir)
            ->add($dir, $patterns, $excludes)
            ->close()
        ;

        // @codeCoverageIgnoreStart
        if ($options['checkout'] && isset($currentBranch)) {
            `git checkout $currentBranch`;
        }
        // @codeCoverageIgnoreEnd

        if ($options['working-dir']) {
            chdir($cwd);
        }

        $cli->writeln("Build complete in <comment>%fms</comment>.\n  Output: <info>%s</info>", $fw->ellapsed(), $file);
    }

    /**
     * Setup project.
     *
     * @param Fw    $fw
     * @param Cli   $cli
     * @param array $options
     */
    public static function setupCommand(Fw $fw, Cli $cli, array $options): void
    {
        $fw->mark();

        $file = $fw['TEMP'].$options['file'];
        $installedVersion = ($content = $fw->read($file, true)) ? strstr($content, "\n", true) : '0.0.0';

        $installers = (array) $options['versions'];
        $versions = array_keys($installers);
        usort($versions, 'version_compare');
        $latestVersion = end($versions) ?: $installedVersion;

        if ($installedVersion === $latestVersion) {
            $cli->writeln('  Already in latest version (<comment>%s</comment>).', $latestVersion);

            return;
        }

        $fw->cache('reset');

        foreach ($versions as $version) {
            $schemas = $installers[$version]['schemas'] ?? null;
            $installer = $installers[$version]['run'] ?? null;

            if ($schemas) {
                $pdo = $fw->service('Fal\\Stick\\Sql\\Connection')->getPdo();

                foreach ($fw->split($schemas) as $schema) {
                    if ($content = $fw->read($schema)) {
                        $pdo->exec($content);
                    }
                }
            }

            if (is_callable($installer)) {
                $fw->call($installer, array($latestVersion, $installedVersion, $installers));
            }
        }

        $fw->write($file, $latestVersion."\ninstallation complete at ".date('Y-m-d G:i:s.u'));
        $cli->writeln('Setup to version "<info>%s</info>"" complete in <comment>%fms</comment>.', $latestVersion, $fw->ellapsed());
    }
}
