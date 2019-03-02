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

namespace Fal\Stick\Web\Session;

/**
 * Session interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface SessionInterface
{
    /**
     * Returns true if session exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool;

    /**
     * Returns session value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name);

    /**
     * Sets session value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return SessionInterface
     */
    public function set(string $name, $value): SessionInterface;

    /**
     * Remove session.
     *
     * @param string $name
     *
     * @return SessionInterface
     */
    public function clear(string $name): SessionInterface;

    /**
     * Returns session value and remove.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function flash(string $name);

    /**
     * Destroy session.
     *
     * @return SessionInterface
     */
    public function destroy(): SessionInterface;
}
