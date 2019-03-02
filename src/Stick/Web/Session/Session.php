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
 * Session.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Session implements SessionInterface
{
    /**
     * @var array
     */
    protected $session = array();

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->start()->session);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name)
    {
        return $this->start()->session[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, $value): SessionInterface
    {
        $this->start()->session[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $name): SessionInterface
    {
        unset($this->start()->session[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flash(string $name)
    {
        $value = $this->get($name);
        $this->clear($name);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(): SessionInterface
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            $this->session = array();

            session_unset();
            session_destroy();
        }

        return $this;
    }

    /**
     * Start session if not started.
     *
     * @return Session
     */
    protected function start(): Session
    {
        if (!headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
            session_start();

            $this->session = &$GLOBALS['_SESSION'];
        }

        return $this;
    }
}
