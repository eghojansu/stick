<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class ReqReqDateTimeClass
{
    public $rdt;

    public function __construct(ReqDateTimeClass $rdt)
    {
        $this->rdt = $rdt;
    }
}
