<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 11, 2019 14:24
 */

namespace Fal\Stick\Event;

use Fal\Stick\Core;
use Fal\Stick\Event;

class ShutdownEvent extends Event
{
    protected $core;
    protected $cwd;
    protected $error;

    public function __construct(Core $core, $cwd, $error)
    {
        $this->core = $core;
        $this->cwd = $cwd;
        $this->error = $error;
    }

    public function getCore()
    {
        return $this->core;
    }

    public function getCwd()
    {
        return $this->cwd;
    }

    public function getError()
    {
        return $this->error;
    }
}
