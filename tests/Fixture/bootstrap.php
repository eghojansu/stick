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

define('TEST_ROOT', strtr(dirname(__DIR__), '\\', '/'));
define('TEST_TEMP', strtr(dirname(TEST_ROOT), '\\', '/').'/var');
define('TEST_FIXTURE', strtr(__DIR__, '\\', '/'));

function testRemoveTemp(string $dir, string $pattern = '*')
{
    $tempDir = TEST_TEMP.'/'.trim(strtr($dir, '\\', '/'), '/').'/';

    if (is_dir($tempDir)) {
        array_map('unlink', glob($tempDir.$pattern));
        rmdir($tempDir);
    }
}

is_dir(TEST_TEMP) || mkdir(TEST_TEMP, 0755, true);
