<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 01, 2018 00:33
 */

namespace Fal\Stick;

/**
 * Event subscribers.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface EventSubscriberInterface
{
    /**
     * Returns events handler.
     *
     * @return array
     */
    public static function getEvents(): array;
}
