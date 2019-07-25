<?php

define('TEST_ROOT', __DIR__);
define('TEST_TEMP', dirname(__DIR__).'/var');
define('TEST_FIXTURE', __DIR__.'/_suite');


is_dir(TEST_TEMP) || mkdir(TEST_TEMP, 0755, true);
