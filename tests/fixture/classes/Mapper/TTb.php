<?php

declare(strict_types=1);

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fixture\Mapper;

use Fal\Stick\Database\Sql\Mapper;

class TTb extends Mapper
{
    protected $table = 'tb';
    protected $adhoc = [
        'inc_id' => ['expr' => '(tb.id + 1)', 'value' => null],
    ];
}
