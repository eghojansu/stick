<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 08:40
 */

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
    public function addUser(UserInterface $user)
    {
        $this->users[$user->getUsername()] = $user;
        $this->mapId[$user->getId()] = $user->getUsername();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername($username)
    {
        return $this->users[$username] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        return $this->users[$this->mapId[$id] ?? 'none'] ?? null;
    }
}
