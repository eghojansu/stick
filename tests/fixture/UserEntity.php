<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

class UserEntity
{
    private $id;
    private $firstName;
    private $lastName;

    public function __construct($id, $firstName, $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getName()
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
}
