<?php

namespace Fixtures;

class StdDateTimeConsumer
{
    public $date;
    public $std;

    public function __construct(\DateTime $date, \stdClass $std)
    {
        $this->date = $date;
        $this->std = $std;
    }

    public function addStdProperty(string $name = 'foo', $value = 'bar')
    {
        $this->std->$name = $value;

        return $this;
    }

    public static function createStd()
    {
        return new \stdClass();
    }
}
