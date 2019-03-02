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
interface RequestStackInterface extends \Countable
{
    /**
     * Push request to stack.
     *
     * @param Request $request
     *
     * @return RequestStackInterface
     */
    public function push(Request $request): RequestStackInterface;

    /**
     * Pop latest request from stack.
     *
     * @return Request
     *
     * @throws LogicException If stack empty
     */
    public function pop(): Request;

    /**
     * Returns latest request.
     *
     * @return Request
     *
     * @throws LogicException If stack empty
     */
    public function getCurrentRequest(): Request;

    /**
     * Returns master request.
     *
     * @return Request
     *
     * @throws LogicException If stack empty
     */
    public function getMasterRequest(): Request;
}
