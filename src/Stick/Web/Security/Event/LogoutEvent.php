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

namespace Fal\Stick\Web\Security\Event;

use Fal\Stick\Web\Security\Auth;
use Fal\Stick\Web\Security\UserInterface;

/**
 * Logout event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class LogoutEvent extends AuthEvent
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * Class constructor.
     *
     * @param Auth               $auth
     * @param UserInterface|null $user
     */
    public function __construct(Auth $auth, UserInterface $user = null)
    {
        parent::__construct($auth);

        $this->user = $user;
    }

    /**
     * Returns user.
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
