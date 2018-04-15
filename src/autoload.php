<?php

use Fal\Stick\App;

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/functions.php';
require __DIR__ . '/App.php';

// To use this library without composer you can include this file in your bootstrap file.
// Merge "NAMESPACE" variable with your namespace configuration (just like Composer Psr4 Autoloader).
//
// If you need to define some "autoloaded" file, ie: file containing functions
// You can copy this file content, and before call App::registerAutoloader,
// define "AUTOLOAD" variable with your file list.
// Your namespaces can be directly defined in variable "NAMESPACE" declaration too.
// Just remember the "Fal\Stick" namespace should be adjusted too.

$app = new App;
$app['NAMESPACE'] = [
    'Fal\\Stick\\' => __DIR__ . '/',
];
$app->registerAutoloader();

return $app;
