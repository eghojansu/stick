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

use Fal\Stick\Event;

/**
 * Auth event data.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class AuthEvent extends Event
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * Class constructor.
     *
     * @param UserInterface|null $user
     */
    public function __construct(UserInterface $user = null)
    {
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

    /**
     * Sets user.
     *
     * @param UserInterface $user
     *
     * @return AuthEvent
     */
    public function setUser(UserInterface $user): AuthEvent
    {
        $this->user = $user;
        $this->stopPropagation();

        return $this;
    }
}
