<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 10:19
 */

namespace Fal\Stick\Event;

use Fal\Stick\Core;
use Fal\Stick\Event;

class PostResponseEvent extends Event
{
    protected $core;

    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    public function getCore()
    {
        return $this->core;
    }
}
