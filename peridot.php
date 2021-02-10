<?php

use Peridot\Console\Environment;
use Evenement\EventEmitterInterface;
use Peridot\Reporter\CodeCoverageReporters;
use Peridot\Reporter\CodeCoverage\AbstractCodeCoverageReporter;

define('STICK_PROJECT_DIR', __DIR__);
define('STICK_SPECS_DIR', STICK_PROJECT_DIR . '/specs');
define('STICK_TEMP_DIR', STICK_PROJECT_DIR . '/var');

is_dir(STICK_TEMP_DIR) || mkdir(STICK_TEMP_DIR, 0755, true);

error_reporting(-1);
ini_set('error_log', STICK_TEMP_DIR . '/' . date('Y-m-d') . '.log');

spl_autoload_register(function($class) {
    if (0 === strpos($class, 'Fixtures\\')) {
        require STICK_SPECS_DIR . '/fixtures/' . substr($class, 9) . '.php';
    }
});

return function (EventEmitterInterface $emitter) {
    $coverage = new CodeCoverageReporters($emitter);
    $coverage->register();

    // set the default path
    $emitter->on('peridot.start', function (Environment $environment) {
        $environment->getDefinition()->getArgument('path')->setDefault(STICK_SPECS_DIR);
        $environment->getDefinition()->getOption('reporter')->setDefault(array(
            'spec',
            'html-code-coverage',
        ));
    });

    $emitter->on('code-coverage.start', function (AbstractCodeCoverageReporter $reporter) {
        $reporter->addDirectoryToWhitelist(STICK_PROJECT_DIR . '/src');
    });
};
