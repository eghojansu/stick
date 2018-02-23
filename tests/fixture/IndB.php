<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class IndB
{
    public $ts;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        $this->ts = microtime();
    }
}
