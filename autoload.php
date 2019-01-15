<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

require __DIR__.'/src/ClassLoader.php';

$loader = new Fal\Stick\ClassLoader([
    'Fal\\Stick\\' => __DIR__.'/src',
]);
$loader->register();

return $loader;
