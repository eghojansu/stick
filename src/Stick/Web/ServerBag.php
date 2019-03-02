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
 * Server bag.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ServerBag extends ParameterBag
{
    /**
     * Returns normalized request headers from server.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = array();
        $contents = array(
            'CONTENT_TYPE' => true,
            'CONTENT_LENGTH' => true,
        );

        foreach ($this->data as $key => $value) {
            if (isset($contents[$key])) {
                $headers[$key] = $value;
            } elseif (0 === strpos($key, 'HTTP_') && $name = substr($key, 5)) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
