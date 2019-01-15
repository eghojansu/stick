<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 10, 2019 10:17
 */

namespace Fal\Stick\Event;

use Fal\Stick\Core;

class FilterControllerResultEvent extends GetResponseEvent
{
    protected $result;

    public function __construct(Core $core, $result)
    {
        parent::__construct($core);

        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
