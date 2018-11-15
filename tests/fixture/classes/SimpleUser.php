<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fixture;

use Fal\Stick\Security\UserInterface;

/**
 * Simple user class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class SimpleUser implements UserInterface
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
    public function __construct(string $id, string $username, string $password, array $roles = null, $credentialsExpired = false)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles ?? array('ROLE_ANONYMOUS');
        $this->credentialsExpired = (bool) $credentialsExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsExpired(): bool
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
    public function setId(string $id): SimpleUser
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
    public function setUsername(string $username): SimpleUser
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
    public function setPassword(string $password): SimpleUser
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
    public function setRoles(array $roles): SimpleUser
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
    public function setCredentialsExpired(bool $credentialsExpired): SimpleUser
    {
        $this->credentialsExpired = $credentialsExpired;

        return $this;
    }
}
