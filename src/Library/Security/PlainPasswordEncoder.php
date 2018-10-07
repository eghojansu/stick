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

namespace Fal\Stick\Library\Security;

/**
 * Plain password encoder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class PlainPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $plainText): string
    {
        return $plainText;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $plainText, string $hash): bool
    {
        return 0 === strcmp($hash, $plainText);
    }
}
