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

use Fal\Stick\EventDispatcher\Event;
use Fal\Stick\Web\Security\Auth;

/**
 * Auth event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class AuthEvent extends Event
{
    /**
     * @var Auth
     */
    protected $auth;

    /**
     * Class constructor.
     *
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Returns auth instance.
     *
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }
}
