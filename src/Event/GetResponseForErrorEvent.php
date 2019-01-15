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

class GetResponseForErrorEvent extends GetResponseEvent
{
    protected $error;

    public function __construct(Core $core, array $error)
    {
        parent::__construct($core);

        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}
