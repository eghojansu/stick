<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

class ProfileObj
{
    public $name;

    /**
     * Class constructor
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
