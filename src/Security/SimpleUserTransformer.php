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
 * Simple user transformer.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class SimpleUserTransformer implements UserTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $args)
    {
        $default = array(
            'id' => '',
            'username' => '',
            'password' => '',
            'roles' => 'ROLE_ANONYMOUS',
            'credentialsExpired' => false,
        );
        $fix = $args + $default;

        return new SimpleUser($fix['id'], $fix['username'], $fix['password'], $fix['roles'], $fix['credentialsExpired']);
    }
}
