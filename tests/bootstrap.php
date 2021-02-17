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
define('TEST_TEMP', strtr(dirname(__DIR__), '\\', '/') . '/var');
define('TEST_FIXTURE', TEST_ROOT . '/tests/fixtures');

function testRemoveTemp(string $dir, string $pattern = '*')
{
    $tempDir = TEST_TEMP . '/' . trim(strtr($dir, '\\', '/'), '/') . '/';

    if (is_dir($tempDir)) {
        foreach (glob($tempDir . $pattern) as $file) {
            @unlink($file);
        }

        if (!glob($tempDir . $pattern)) {
            rmdir($tempDir);
        }
    }
}

is_dir(TEST_TEMP) || mkdir(TEST_TEMP, 0755, true);

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Fixtures\\')) {
        require TEST_FIXTURE . '/' . substr($class, 9) . '.php';
    }
});
