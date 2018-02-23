<?php

require dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', __DIR__ . '/');
define('FIXTURE', __DIR__ . '/fixture/');
define('TEMP', dirname(__DIR__) . '/var/');

if (!is_dir(TEMP)) {
    mkdir(TEMP, 0755, true);
}
