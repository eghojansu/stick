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
 * Login event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class LoginEvent extends AuthEvent
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var bool
     */
    protected $remember;

    /**
     * Class constructor.
     *
     * @param Auth          $auth
     * @param UserInterface $user
     * @param bool          $remember
     */
    public function __construct(Auth $auth, UserInterface $user, bool $remember)
    {
        parent::__construct($auth);

        $this->user = $user;
        $this->remember = $remember;
    }

    /**
     * Returns user.
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * Returns true if wants to remember user.
     *
     * @return bool
     */
    public function isRemember(): bool
    {
        return $this->remember;
    }
}
