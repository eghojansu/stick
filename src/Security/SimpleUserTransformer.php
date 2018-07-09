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
 * Simple user transformer.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class SimpleUserTransformer implements UserTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $args): UserInterface
    {
        $default = [
            'id' => '',
            'username' => '',
            'password' => '',
            'roles' => ['ROLE_ANONYMOUS'],
            'expired' => false,
        ];
        $use = array_values(array_replace($default, $args));

        return new SimpleUser(...$use);
    }
}
