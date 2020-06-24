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
class SimpleUser implements UserInterface, \ArrayAccess
{
    protected $_data;
    protected $_info;

    public function __construct(string $id, string $username, string $password = null, $roles = null, bool $expired = false, bool $disabled = false, array $info = null)
    {
        $roles = Fw::split($roles);

        $this->_data = compact('id', 'username', 'password', 'roles', 'expired', 'disabled');
        $this->_info = $info ?? array();
    }

    public function __isset($key)
    {
        return isset($this->_data[$key]) || isset($this->_info[$key]) || array_key_exists($key, $this->_info);
    }

    public function __set($key, $value)
    {
        if (isset($this->_data[$key]) && method_exists($this, $set = 'set'.$key)) {
            $this->{$set}($value);
        } else {
            $this->addInfo($key, $value);
        }
    }

    public function __unset($key)
    {
        $this->remInfo($key);
    }

    public function &__get($key)
    {
        $ref = $this->_data[$key] ?? $this->_info[$key] ?? null;

        return $ref;
    }

    public function offsetExists($key)
    {
        return isset($this->{$key});
    }

    public function &offsetGet($key)
    {
        return $this->{$key};
    }

    public function offsetSet($key, $value)
    {
        $this->{$key} = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->{$key});
    }

    public static function fromArray(array $user): SimpleUser
    {
        $arguments = array(
            (string) ($user['id'] ?? null),
            (string) ($user['username'] ?? null),
            (string) ($user['password'] ?? null),
            Fw::split($user['roles'] ?? null),
            (bool) ($user['expired'] ?? false),
            (bool) ($user['disabled'] ?? false),
            (array) ($user['info'] ?? null),
        );

        return new static(...$arguments);
    }

    public function toArray(bool $info = true): array
    {
        return $info ? array_merge($this->_data, $this->_info) : $this->_data;
    }

    public function getId(): string
    {
        return $this->_data['id'];
    }

    public function getUsername(): string
    {
        return $this->_data['username'];
    }

    public function setUsername(string $username): SimpleUser
    {
        $this->_data['username'] = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->_data['password'];
    }

    public function setPassword(string $password): SimpleUser
    {
        $this->_data['password'] = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->_data['roles'];
    }

    public function setRoles(array $roles): SimpleUser
    {
        $this->_data['roles'] = $roles;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->_data['expired'];
    }

    public function setExpired(bool $expired): SimpleUser
    {
        $this->_data['expired'] = $expired;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->_data['disabled'];
    }

    public function setDisabled(bool $disabled): SimpleUser
    {
        $this->_data['disabled'] = $disabled;

        return $this;
    }

    public function getInfo(): array
    {
        return $this->_info;
    }

    public function info(string $key)
    {
        return $this->_info[$key] ?? null;
    }

    public function addInfo(string $key, $value): SimpleUser
    {
        $this->_info[$key] = $value;

        return $this;
    }

    public function remInfo(string $key): SimpleUser
    {
        unset($this->_info[$key]);

        return $this;
    }
}
