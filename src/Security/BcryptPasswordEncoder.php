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
 * Password encoder using bcrypt algorithm.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class BcryptPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash($plainText)
    {
        return password_hash($plainText, PASSWORD_BCRYPT);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($plainText, $hash)
    {
        return password_verify($plainText, $hash);
    }
}
