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
 * Password encoder using bcrypt algorithm.
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
