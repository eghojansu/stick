<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\EventDispatcher;

/**
 * Event data.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Event
{
    /**
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * Returns true if propagation stopped.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stop propagation.
     *
     * @return Event
     */
    public function stopPropagation(): Event
    {
        $this->propagationStopped = true;

        return $this;
    }
}
