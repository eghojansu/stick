<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class PureUserEntity
{
    private $id;
    private $first_name;
    private $last_name;
    private $foo;

    public function __construct($foo = null)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
