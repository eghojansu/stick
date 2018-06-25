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
 * Interface for User class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserInterface
{
    /**
     * Get id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername(): string;

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword(): string;

    /**
     * Get roles.
     *
     * @return array
     */
    public function getRoles(): array;

    /**
     * Is expired.
     *
     * @return bool
     */
    public function isExpired(): bool;
}
