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

namespace Fal\Stick\Web\Security;

/**
 * Interface for user provider.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserProviderInterface
{
    /**
     * Find user by username.
     *
     * @param mixed $username
     *
     * @return UserInterface|null
     */
    public function findByUsername($username): ?UserInterface;

    /**
     * Find user by id.
     *
     * @param mixed $id
     *
     * @return UserInterface|null
     */
    public function findById($id): ?UserInterface;
}
