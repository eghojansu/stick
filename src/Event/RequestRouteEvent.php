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

namespace Ekok\Stick\Event;

class RequestRouteEvent extends RequestEvent
{
    private $route;

    public function __construct(array $route)
    {
        $this->route = $route;
    }

    public function getRoute(): array
    {
        return $this->route;
    }
}
