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

namespace Fal\Stick\Security;

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
    protected $mapId = array();

    /**
     * Add user.
     *
     * @param UserInterface $user
     *
     * @return InMemoryUserProvider
     */
    public function addUser(UserInterface $user): InMemoryUserProvider
    {
        $this->users[$user->getUsername()] = $user;
        $this->mapId[$user->getId()] = $user->getUsername();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername($username): ?UserInterface
    {
        return isset($this->users[$username]) ? $this->users[$username] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id): ?UserInterface
    {
        $key = isset($this->mapId[$id]) ? $this->mapId[$id] : 'none';

        return isset($this->users[$key]) ? $this->users[$key] : null;
    }
}
