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

namespace Ekok\Stick\Security;

use Ekok\Stick\Fw;

/**
 * Simple user class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class SimpleUser implements UserInterface
{
    protected $id;
    protected $username;
    protected $password;
    protected $roles;
    protected $expired;
    protected $disabled;

    public function __construct(string $id, string $username, string $password = null, $roles = null, bool $expired = false, bool $disabled = false)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password ?? '';
        $this->roles = Fw::split($roles);
        $this->expired = $expired;
        $this->disabled = $disabled;
    }

    public static function fromArray(array $user): SimpleUser
    {
        $arguments = array(
            $user['id'] ?? '',
            $user['username'] ?? '',
            $user['password'] ?? '',
            $user['roles'] ?? array(),
            $user['expired'] ?? false,
            $user['disabled'] ?? false,
        );

        return new static(...$arguments);
    }

    public function toArray(): array
    {
        return array(
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'roles' => $this->getRoles(),
            'expired' => $this->isExpired(),
            'disabled' => $this->isDisabled(),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
