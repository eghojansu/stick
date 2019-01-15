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

class GetResponseEvent extends Event
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

    public function getResponse()
    {
        return [
            'code'    => $this->core->get('CODE'),
            'headers' => $this->core->get('RESPONSE'),
            'content' => $this->core->get('OUTPUT'),
        ];
    }

    public function setResponse($content, $code = 200, array $headers = null)
    {
        $this->core->status($code);
        $this->core->set('OUTPUT', $content);
        $this->core->set('RESPONSE', $headers);
        $this->stopPropagation();

        return $this;
    }
}
