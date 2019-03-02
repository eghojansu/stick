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

namespace Fal\Stick\Validation\Rules;

use Fal\Stick\Validation\RuleTrait;
use Fal\Stick\Validation\RuleInterface;

/**
 * Url validation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class UrlRule implements RuleInterface
{
    use RuleTrait;

    /**
     * Return true if string is a valid URL.
     *
     * @param string $str
     *
     * @return bool
     */
    protected function _url($str)
    {
        return is_string(filter_var($str, FILTER_VALIDATE_URL));
    }

    /**
     * Check email.
     *
     * Return true if string is a valid e-mail address.
     *
     * Check DNS MX records if specified.
     *
     * @param string $str
     * @param bool   $mx
     *
     * @return bool
     */
    protected function _email($str, $mx = false)
    {
        $hosts = array();

        return is_string(filter_var($str, FILTER_VALIDATE_EMAIL)) && (!$mx || getmxrr(substr($str, strrpos($str, '@') + 1), $hosts));
    }

    /**
     * Return true if string is a valid IPV4 address.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _ipv4($addr)
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Return true if string is a valid IPV6 address.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _ipv6($addr)
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Return true if IP address is within private range.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _isPrivate($addr)
    {
        return !(bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE);
    }

    /**
     * Return true if IP address is within reserved range.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _isReserved($addr)
    {
        return !(bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Return true if IP address is neither private nor reserved.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _isPublic($addr)
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
