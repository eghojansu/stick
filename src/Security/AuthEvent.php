<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

use Fal\Stick\Event;

class AuthEvent extends Event
{
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function getAuth()
    {
        return $this->auth;
    }
}
