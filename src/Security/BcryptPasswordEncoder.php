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
 * Password encoder using bcrypt algorithm.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class BcryptPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $plainText): string
    {
        return password_hash($plainText, PASSWORD_BCRYPT);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $plainText, string $hash): bool
    {
        return password_verify($plainText, $hash);
    }
}
