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

use Fal\Stick\Web\Security\UserInterface;

/**
 * Load user event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class LoadUserEvent extends AuthEvent
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * Returns true if user is not null.
     *
     * @return bool
     */
    public function hasUser(): bool
    {
        return null !== $this->user;
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

    /**
     * Assign user.
     *
     * @param UserInterface $user
     *
     * @return LoadUserEvent
     */
    public function setUser(UserInterface $user): LoadUserEvent
    {
        $this->user = $user;

        return $this;
    }
}
