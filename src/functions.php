<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 03, 2018 10:59
 */

declare(strict_types=1);

namespace Fal\Stick;

/**
 * Include wrapper, ensure included file has no access to caller private scope.
 *
 * @param string $file
 *
 * @return mixed
 */
function includeFile(string $file)
{
    return include $file;
}

/**
 * Require wrapper, ensure included file has no access to caller private scope.
 *
 * @param string $file
 *
 * @return mixed
 */
function requireFile(string $file)
{
    return require $file;
}
