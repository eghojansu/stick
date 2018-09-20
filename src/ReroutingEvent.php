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

namespace Fal\Stick;

/**
 * Events data when reroute event raised.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ReroutingEvent extends Event
{
    /**
     * Url rerouted to.
     *
     * @var string
     */
    private $url;

    /**
     * Rerouted status.
     *
     * @var bool
     */
    private $permanent;

    /**
     * Class constructor.
     *
     * @param string $url
     * @param bool   $permanent
     */
    public function __construct(string $url, bool $permanent)
    {
        $this->url = $url;
        $this->permanent = $permanent;
    }

    /**
     * Returns url rerouted to.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Returns rerouted status.
     *
     * @return bool
     */
    public function isPermanent(): bool
    {
        return $this->permanent;
    }
}
