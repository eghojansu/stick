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

namespace Fal\Stick\TestSuite\Provider\Security;

class AuthRuleProvider
{
    public function validatePassword()
    {
        return array(
            array(null, 'bar'),
            array('1', 'bar'),
            array('2', 'baz'),
            array('1', 'baz', false),
        );
    }
}
