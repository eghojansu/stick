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

use function Fal\Stick\picktoargs;

class SimpleUserTransformer implements UserTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $args): UserInterface
    {
        $use = picktoargs($args, ['id','username','password','roles','expired']);

        return new SimpleUser(...$use);
    }
}
