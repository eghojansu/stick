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
 * Interface for password encoder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface PasswordEncoderInterface
{
    /**
     * Hash password.
     *
     * @param string $plainText
     *
     * @return string
     */
    public function hash(string $plainText): string;

    /**
     * Verify password.
     *
     * @param string $plainText
     * @param string $hash
     *
     * @return bool
     */
    public function verify(string $plainText, string $hash): bool;
}
