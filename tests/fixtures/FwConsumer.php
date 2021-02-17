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

use Ekok\Stick\Fw;

class FwConsumer
{
    public $fw;

    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
    }
}
