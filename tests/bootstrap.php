<?php

require dirname(__DIR__).'/vendor/autoload.php';

define('TEST_ROOT', __DIR__.'/');
define('TEST_FIXTURE', __DIR__.'/fixture/');
define('TEST_TEMP', dirname(__DIR__).'/var/');

// ini_set('xdebug.var_display_max_data', '-1');
// ini_set('xdebug.var_display_max_children', '-1');
// ini_set('xdebug.var_display_max_depth', '-1');

if (!is_dir(TEST_TEMP)) {
    mkdir(TEST_TEMP, 0755, true);
}
