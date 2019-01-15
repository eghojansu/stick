<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 01:35
 */

namespace Fal\Stick\Security;

class PlainPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash($plainText)
    {
        return $plainText;
    }

    /**
     * {@inheritdoc}
     */
    public function verify($plainText, $hash)
    {
        return 0 === strcmp($plainText, $hash);
    }
}
