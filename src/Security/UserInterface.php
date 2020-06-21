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
 * Interface for User class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserInterface
{
    /**
     * Returns id.
     */
    public function getId(): string;

    /**
     * Returns username.
     */
    public function getUsername(): string;

    /**
     * Returns password.
     */
    public function getPassword(): string;

    /**
     * Returns roles.
     */
    public function getRoles(): array;

    /**
     * Returns true if credentials is expired.
     */
    public function isExpired(): bool;

    /**
     * Returns true if credentials is disabled.
     */
    public function isDisabled(): bool;
}
