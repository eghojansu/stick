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

namespace Fixtures;

class ArgumentsConsumer
{
    public $collected;

    public function __construct(?\stdClass $std, string $name, ?string $name2, string $name3 = null, ...$rest)
    {
        $this->collected = compact('std', 'name', 'name2', 'name3', 'rest');
    }
}
