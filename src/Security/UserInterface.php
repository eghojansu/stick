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
    public function getId();

    /**
     * Returns username.
     *
     * @return string
     */
    public function getUsername();

    /**
     * Returns password.
     *
     * @return string
     */
    public function getPassword();

    /**
     * Returns roles.
     *
     * @return array
     */
    public function getRoles();

    /**
     * Returns credential expired status.
     *
     * @return bool
     */
    public function isCredentialsExpired();
}
