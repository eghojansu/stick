<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 06, 2018 20:30
 */
require __DIR__.'/functions.php';
require __DIR__.'/Core.php';

return Fal\Stick\Core::createFromGlobals()->registerClassLoader();
