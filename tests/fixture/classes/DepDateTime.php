<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

class DepDateTime
{
    public $ts;
    public $dt;

    /**
     * Class constructor
     *
     */
    public function __construct(\DateTime $dt)
    {
        $this->ts = microtime();
        $this->dt = $dt;
    }
}
