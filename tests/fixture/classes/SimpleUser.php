<?php

namespace Fixture;

use Fal\Stick\Security\UserInterface;

class SimpleUser implements UserInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $roles;

    /**
     * @var bool
     */
    private $credentialsExpired;

    /**
     * Class constructor.
     *
     * @param string $id
     * @param string $username
     * @param string $password
     * @param array  $roles
     * @param mixed  $credentialsExpired
     */
    public function __construct($id, $username, $password, array $roles = null, $credentialsExpired = false)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles ?: array('ROLE_ANONYMOUS');
        $this->credentialsExpired = (bool) $credentialsExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * Sets id.
     *
     * @param string $id
     *
     * @return SimpleUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets username.
     *
     * @param string $username
     *
     * @return SimpleUser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Sets password.
     *
     * @param string $password
     *
     * @return SimpleUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Sets roles.
     *
     * @param array $roles
     *
     * @return SimpleUser
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Sets credentialsExpired.
     *
     * @param bool $credentialsExpired
     *
     * @return SimpleUser
     */
    public function setCredentialsExpired($credentialsExpired)
    {
        $this->credentialsExpired = $credentialsExpired;

        return $this;
    }
}
