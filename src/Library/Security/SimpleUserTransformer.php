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
 * Simple user transformer.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class SimpleUserTransformer implements UserTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $args): UserInterface
    {
        $default = array(
            'id' => '',
            'username' => '',
            'password' => '',
            'roles' => array('ROLE_ANONYMOUS'),
            'credentialsExpired' => false,
        );
        $fix = $args + $default;

        return new SimpleUser($fix['id'], $fix['username'], $fix['password'], $fix['roles'], $fix['credentialsExpired']);
    }
}
