<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

interface UserProviderInterface
{
    /**
     * Find user by username
     *
     * @param  string $username
     *
     * @return UserInterface|null
     */
    public function findByUsername(string $username): ?UserInterface;

    /**
     * Find user by id
     *
     * @param  string $id
     *
     * @return UserInterface|null
     */
    public function findById(string $id): ?UserInterface;
}
