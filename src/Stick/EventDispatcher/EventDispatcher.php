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

use Fal\Stick\Container\Container;

/**
 * Event dispatcher.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $events = array();

    /**
     * @var array
     */
    protected $eventsOnce = array();

    /**
     * Class constructor.
     *
     * @param Container  $container
     * @param array|null $events
     */
    public function __construct(Container $container, array $events = null)
    {
        $this->container = $container;

        foreach ($events ?? array() as $key => $value) {
            $this->on($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function on(string $eventName, $handler): EventDispatcherInterface
    {
        $this->events[$eventName] = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function one(string $eventName, $handler): EventDispatcherInterface
    {
        $this->eventsOnce[$eventName] = true;

        return $this->on($eventName, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function off(string $eventName): EventDispatcherInterface
    {
        unset($this->events[$eventName], $this->eventsOnce[$eventName]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $eventName, Event $event, bool $off = false): EventDispatcherInterface
    {
        if (!isset($this->events[$eventName])) {
            return $this;
        }

        $dispatcher = $this->events[$eventName];
        $once = $this->eventsOnce[$eventName] ?? false;

        if ($off || $once) {
            $this->off($eventName);
        }

        if (is_string($dispatcher)) {
            $dispatcher = $this->container->grab($dispatcher);
        }

        if (!is_callable($dispatcher)) {
            throw new \LogicException(sprintf('Handler is not callable: "%s".', $eventName));
        }

        $this->container->call($dispatcher, array($event));

        return $this;
    }

    /**
     * Returns registered events listener.
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
