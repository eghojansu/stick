<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class DepDepAIndB
{
    public $ts;
    public $depa;
    public $indb;

    /**
     * Class constructor
     *
     */
    public function __construct(DepA $depa, IndB $indb)
    {
        $this->ts = microtime();
        $this->depa = $depa;
        $this->indb = $indb;
    }
}
