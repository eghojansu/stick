<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class DepDepAIndB
{
    public $ts;
    public $depa;
    public $indb;
    public $foo;

    /**
     * Class constructor
     *
     */
    public function __construct(DepA $depa, IndB $indb, string $foo)
    {
        $this->ts = microtime();
        $this->depa = $depa;
        $this->indb = $indb;
        $this->foo = $foo;
    }
}
