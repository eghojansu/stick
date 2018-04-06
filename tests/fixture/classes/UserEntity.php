<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

class UserEntity
{
    private $id;
    private $firstName;
    private $lastName;

    /**
     * Get id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param mixed $id
     * @return UserEntity
     */
    public function setId($id): UserEntity
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set firstName
     *
     * @param mixed $firstName
     * @return UserEntity
     */
    public function setFirstName($firstName): UserEntity
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set lastName
     *
     * @param mixed $lastName
     * @return UserEntity
     */
    public function setLastName($lastName): UserEntity
    {
        $this->lastName = $lastName;

        return $this;
    }
}
