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
 * Interface for User class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserInterface
{
    /**
     * Returns id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns username.
     *
     * @return string
     */
    public function getUsername(): string;

    /**
     * Returns password.
     *
     * @return string
     */
    public function getPassword(): string;

    /**
     * Returns roles.
     *
     * @return array
     */
    public function getRoles(): array;

    /**
     * Returns credential expired status.
     *
     * @return bool
     */
    public function isCredentialsExpired(): bool;
}
