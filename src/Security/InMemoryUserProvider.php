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
    private $users;

    /**
     * @var UserTransformerInterface
     */
    private $transformer;

    /**
     * Class constructor.
     *
     * @param array                    $users
     * @param UserTransformerInterface $transformer
     */
    public function __construct(array $users, UserTransformerInterface $transformer)
    {
        $this->users = $users;
        $this->transformer = $transformer;
    }

    /**
     * Add user.
     *
     * @param string $username
     * @param string $password
     *
     * @return InMemoryUserProvider
     */
    public function addUser(string $username, string $password): InMemoryUserProvider
    {
        $this->users[$username] = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?UserInterface
    {
        return array_key_exists($username, $this->users) ?
            $this->transformer->transform([
                'id' => $username,
                'username' => $username,
                'password' => $this->users[$username],
            ]) : null
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?UserInterface
    {
        return $this->findByUsername($id);
    }
}
