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

class PlainPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $plain): string
    {
        return $plain;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $plain, string $hash): bool
    {
        return $plain === $hash;
    }
}
