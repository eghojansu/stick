<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

class DepA
{
    public $ts;
    public $inda;

    /**
     * Class constructor
     *
     */
    public function __construct(IndA $inda)
    {
        $this->ts = microtime();
        $this->inda = $inda;
    }
}
