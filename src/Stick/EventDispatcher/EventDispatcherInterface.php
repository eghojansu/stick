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

namespace Fal\Stick\EventDispatcher;

/**
 * Event dispatcher.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface EventDispatcherInterface
{
    /**
     * Add event listener.
     *
     * @param string $eventName
     * @param mixed  $handler
     *
     * @return EventDispatcherInterface
     */
    public function on(string $eventName, $handler): EventDispatcherInterface;

    /**
     * Add event listener once.
     *
     * @param string $eventName
     * @param mixed  $handler
     *
     * @return EventDispatcherInterface
     */
    public function one(string $eventName, $handler): EventDispatcherInterface;

    /**
     * Remove event listener.
     *
     * @param string $eventName
     *
     * @return EventDispatcherInterface
     */
    public function off(string $eventName): EventDispatcherInterface;

    /**
     * Dispatch event.
     *
     * @param string $eventName
     * @param Event  $event
     * @param bool   $off
     *
     * @return EventDispatcherInterface
     */
    public function dispatch(string $eventName, Event $event, bool $off = false): EventDispatcherInterface;
}
