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

/**
 * Simple user provider that holds users as its data.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class InMemoryUserProvider implements UserProviderInterface
{
    /**
     * @var array
     */
    protected $users = array();

    /**
     * @var array
     */
    protected $maps = array();

    protected $userCreator;

    public function __construct(callable $userCreator = null)
    {
        $this->userCreator = $userCreator;
    }

    /**
     * Add user.
     */
    public function addUser(UserInterface $user): InMemoryUserProvider
    {
        $this->users[$user->getUsername()] = $user;
        $this->maps[$user->getId()] = $user->getUsername();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?UserInterface
    {
        return $this->users[$username] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?UserInterface
    {
        return $this->users[$this->maps[$id] ?? 'none'] ?? null;
    }

    public function fromArray(array $user): ?UserInterface
    {
        $creator = $this->userCreator;

        return $creator ? $creator($user) : null;
    }
}
