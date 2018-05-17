<?php

declare(strict_types=1);

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
 * Interface for password encoder.
 */
interface PasswordEncoderInterface
{
    /**
     * Hash password.
     *
     * @param string $plain
     *
     * @return string
     */
    public function hash(string $plain): string;

    /**
     * Verify password.
     *
     * @param string $plain
     * @param string $hash
     *
     * @return bool
     */
    public function verify(string $plain, string $hash): bool;
}
