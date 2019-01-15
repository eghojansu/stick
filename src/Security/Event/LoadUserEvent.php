<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Security\Event;

use Fal\Stick\Security\AuthEvent;
use Fal\Stick\Security\UserInterface;

class LoadUserEvent extends AuthEvent
{
    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(UserInterface $user)
    {
        $this->user = $user;
        $this->stopPropagation();

        return $this;
    }
}
