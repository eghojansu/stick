<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    protected $users;

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
    public function addUser($username, $password, array $extra = null)
    {
        $id = array('id' => $username);
        $this->users[$username] = compact('username', 'password') + ((array) $extra) + $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername($username)
    {
        return array_key_exists($username, $this->users) ? $this->transformer->transform($this->users[$username]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        $found = array_column($this->users, 'username', 'id');

        return isset($found[$id]) ? $this->findByUsername($found[$id]) : null;
    }
}
