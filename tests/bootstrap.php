<?php

require dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', __DIR__ . '/');
define('FIXTURE', __DIR__ . '/fixture/');
define('TEMP', dirname(__DIR__) . '/var/');

// ini_set('xdebug.var_display_max_data', '-1');
// ini_set('xdebug.var_display_max_children', '-1');
// ini_set('xdebug.var_display_max_depth', '-1');

if (!is_dir(TEMP)) {
    mkdir(TEMP, 0755, TRUE);
}
