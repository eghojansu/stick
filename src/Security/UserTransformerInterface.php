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
 * Interface for user transformer.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UserTransformerInterface
{
    /**
     * Transform assocation array to UserInterface.
     *
     * @param array $args
     *
     * @return UserInterface
     */
    public function transform(array $args): UserInterface;
}
