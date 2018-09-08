<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Allows to set a response for the return value of a controller.
 *
 * Call setResponse() to set the response that will be returned for the current request.
 * The propagation of this event is stopped as soon as a response is set.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseForControllerEvent extends GetResponseEvent
{
    /**
     * Controller return value.
     *
     * @var mixed
     */
    private $result;

    /**
     * Class constructor.
     *
     * @param mixed      $result
     * @param array|null $headers
     */
    public function __construct($result, array $headers = null)
    {
        parent::__construct(200, '', 0, $headers);

        $this->result = $result;
    }

    /**
     * Returns the return value of the controller.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the return value of the controller.
     *
     * @param mixed $result
     *
     * @return GetResponseForControllerEvent
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
