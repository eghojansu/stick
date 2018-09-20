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
 * Event is the base class for classes containing event data.
 *
 * This class contains no event data.
 * It is used by events that do not pass state information to an event handler when an event is raised.
 *
 * You can call the method stopPropagation() to abort the execution of further listeners in your event listener.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Event
{
    /**
     * Propagation state.
     *
     * @var bool
     */
    private $propagationStopped = false;

    /**
     * Returns whether further event listeners should be triggered.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * @return Event
     */
    public function stopPropagation(): Event
    {
        $this->propagationStopped = true;

        return $this;
    }
}
