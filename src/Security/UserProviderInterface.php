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
 * Interface for user provider.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserProviderInterface
{
    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?UserInterface;

    /**
     * Find user by id.
     */
    public function findById(string $id): ?UserInterface;

    /**
     * Create user from array.
     */
    public function fromArray(array $user): ?UserInterface;
}
