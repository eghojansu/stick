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

namespace Fal\Stick\Library\Security;

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
     * @var UserTransformerInterface
     */
    protected $transformer;

    /**
     * Class constructor.
     *
     * @param UserTransformerInterface $transformer
     */
    public function __construct(UserTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Add user.
     *
     * @param string $username
     * @param string $password
     * @param array  $extra
     *
     * @return InMemoryUserProvider
     */
    public function addUser(string $username, string $password, array $extra = null): InMemoryUserProvider
    {
        $id = array('id' => $username);
        $this->users[$username] = compact('username', 'password') + ((array) $extra) + $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?UserInterface
    {
        return array_key_exists($username, $this->users) ? $this->transformer->transform($this->users[$username]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?UserInterface
    {
        $found = array_column($this->users, 'username', 'id');

        return isset($found[$id]) ? $this->findByUsername($found[$id]) : null;
    }
}
