<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Event;

use Fal\Stick\Core;
use Fal\Stick\Event;

class RerouteEvent extends Event
{
    protected $core;
    protected $url;
    protected $permanent;

    public function __construct(Core $core, $url, $permanent)
    {
        $this->core = $core;
        $this->url = $url;
        $this->permanent = $permanent;
    }

    public function getCore()
    {
        return $this->core;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isPermanent()
    {
        return $this->permanent;
    }

    public function redirect($url, $permanent = false)
    {
        $this->core->reroute($url, $permanent);
        $this->stopPropagation();

        return $this;
    }
}
