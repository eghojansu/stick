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

namespace Fal\Stick\Web;

/**
 * Request stack.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class RequestStack implements RequestStackInterface
{
    /**
     * @var array
     */
    protected $stack = array();

    /**
     * {@inheritdoc}
     */
    public function push(Request $request): RequestStackInterface
    {
        $this->stack[] = $request;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function pop(): Request
    {
        if (!$this->stack) {
            throw new \LogicException('Request stack empty!');
        }

        return array_pop($this->stack);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentRequest(): Request
    {
        if (!$this->stack) {
            throw new \LogicException('Request stack empty!');
        }

        return end($this->stack);
    }

    /**
     * {@inheritdoc}
     */
    public function getMasterRequest(): Request
    {
        if (!$this->stack) {
            throw new \LogicException('Request stack empty!');
        }

        return reset($this->stack);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->stack);
    }
}
