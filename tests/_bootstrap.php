<?php

define('TEST_ROOT', dirname(__DIR__).'/');
define('TEST_TEMP', dirname(__DIR__).'/var/');
define('TEST_FIXTURE', __DIR__.'/_fixture/');

is_dir(TEST_TEMP) || mkdir(TEST_TEMP, 0755, true);
