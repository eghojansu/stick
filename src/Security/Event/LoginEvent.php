<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 09:31
 */

namespace Fal\Stick\Security\Event;

use Fal\Stick\Security\Auth;
use Fal\Stick\Security\UserInterface;

class LoginEvent extends LoadUserEvent
{
    public function __construct(Auth $auth, UserInterface $user)
    {
        parent::__construct($auth);

        $this->user = $user;
    }
}
