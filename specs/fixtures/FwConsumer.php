<?php

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
