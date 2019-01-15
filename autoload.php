<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 12, 2019 17:55
 */

require __DIR__.'/src/ClassLoader.php';

$loader = new Fal\Stick\ClassLoader(array(
    'Fal\\Stick\\' => __DIR__.'/src',
));
$loader->register();

return $loader;
