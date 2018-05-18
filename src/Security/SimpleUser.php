<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

use Fal\Stick\Helper;

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
    private $expired;

    /**
     * Class constructor.
     *
     * @param string $id
     * @param string $username
     * @param string $password
     * @param mixed  $roles
     * @param mixed  $expired
     */
    public function __construct(string $id, string $username, string $password, $roles = ['ROLE_ANONYMOUS'], $expired = false)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->roles = Helper::reqarr($roles);
        $this->expired = (bool) $expired;
    }

    /**
     * Set id.
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
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set username.
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
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set password.
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
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set roles.
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
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Set expired.
     *
     * @param bool $expired
     *
     * @return SimpleUser
     */
    public function setExpired(bool $expired): SimpleUser
    {
        $this->expired = $expired;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired(): bool
    {
        return $this->expired;
    }
}
