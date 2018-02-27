<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class PureUserEntity
{
    private $first_name;
    private $last_name;

    public function getName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
