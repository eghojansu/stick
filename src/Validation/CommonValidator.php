<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Validation;

/**
 * Common validation rules.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class CommonValidator extends AbstractValidator
{
    /**
     * Proxy to trim, prevent trimming null value.
     *
     * @param mixed  $val
     * @param string $chars
     *
     * @return string
     */
    protected function _trim($val, $chars = " \t\n\r\0\x0B")
    {
        return trim((string) $val, $chars);
    }

    /**
     * Proxy to ltrim, prevent trimming null value.
     *
     * @param mixed  $val
     * @param string $chars
     *
     * @return string
     */
    protected function _ltrim($val, $chars = " \t\n\r\0\x0B")
    {
        return ltrim((string) $val, $chars);
    }

    /**
     * Proxy to rtrim, prevent trimming null value.
     *
     * @param mixed  $val
     * @param string $chars
     *
     * @return string
     */
    protected function _rtrim($val, $chars = " \t\n\r\0\x0B")
    {
        return rtrim((string) $val, $chars);
    }

    /**
     * Returns true if val exists and not an empty string.
     *
     * @param mixed $val
     *
     * @return bool
     */
    protected function _required($val)
    {
        return isset($val) && '' !== $val;
    }

    /**
     * Returns true if variabel type is same as expected type.
     *
     * @param mixed  $val
     * @param string $type
     *
     * @return bool
     */
    protected function _type($val, $type)
    {
        return gettype($val) === $type;
    }

    /**
     * Equal to other field.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _equalField($val, $compared)
    {
        if (isset($this->data['validated'][$compared])) {
            $compare = $this->data['validated'][$compared];
        } elseif (isset($this->data['raw'][$compared])) {
            $compare = $this->data['raw'][$compared];
        } else {
            return false;
        }

        return $compare === $val;
    }

    /**
     * Not equal to other field.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _notEqualField($val, $compared)
    {
        if (isset($this->data['validated'][$compared])) {
            $compare = $this->data['validated'][$compared];
        } elseif (isset($this->data['raw'][$compared])) {
            $compare = $this->data['raw'][$compared];
        } else {
            return true;
        }

        return $compare !== $val;
    }

    /**
     * Equal to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _equal($val, $compared)
    {
        return $val == $compared;
    }

    /**
     * Not equal to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _notEqual($val, $compared)
    {
        return $val != $compared;
    }

    /**
     * Identical to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _identical($val, $compared)
    {
        return $val === $compared;
    }

    /**
     * Not identical to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _notIdentical($val, $compared)
    {
        return $val !== $compared;
    }

    /**
     * Less than.
     *
     * @param mixed $val
     * @param mixed $min
     *
     * @return bool
     */
    protected function _lt($val, $min)
    {
        return $val < $min;
    }

    /**
     * Greater than.
     *
     * @param mixed $val
     * @param mixed $max
     *
     * @return bool
     */
    protected function _gt($val, $max)
    {
        return $val > $max;
    }

    /**
     * Less than or equal.
     *
     * @param mixed $val
     * @param mixed $min
     *
     * @return bool
     */
    protected function _lte($val, $min)
    {
        return $val <= $min;
    }

    /**
     * Greater than or equal.
     *
     * @param mixed $val
     * @param mixed $max
     *
     * @return bool
     */
    protected function _gte($val, $max)
    {
        return $val >= $max;
    }

    /**
     * Number min.
     *
     * @param mixed $val
     * @param mixed $min
     *
     * @return bool
     */
    protected function _min($val, $min)
    {
        return $val >= $min;
    }

    /**
     * Number max.
     *
     * @param mixed $val
     * @param mixed $max
     *
     * @return bool
     */
    protected function _max($val, $max)
    {
        return $val <= $max;
    }

    /**
     * Length.
     *
     * @param string $val
     * @param int    $len
     *
     * @return bool
     */
    protected function _len($val, $len)
    {
        return !$this->_required($val) || strlen($val) === $len;
    }

    /**
     * Min length.
     *
     * @param string $val
     * @param int    $min
     *
     * @return bool
     */
    protected function _lenMin($val, $min)
    {
        return !$this->_required($val) || strlen($val) >= $min;
    }

    /**
     * Max length.
     *
     * @param string $val
     * @param int    $max
     *
     * @return bool
     */
    protected function _lenMax($val, $max)
    {
        return !$this->_required($val) || strlen($val) <= $max;
    }

    /**
     * Count.
     *
     * @param array $val
     * @param int   $count
     *
     * @return bool
     */
    protected function _count(array $val, $count)
    {
        return count($val) === $count;
    }

    /**
     * Count min.
     *
     * @param array $val
     * @param int   $min
     *
     * @return bool
     */
    protected function _countMin(array $val, $min)
    {
        return count($val) >= $min;
    }

    /**
     * Count max.
     *
     * @param array $val
     * @param int   $max
     *
     * @return bool
     */
    protected function _countMax(array $val, $max)
    {
        return count($val) <= $max;
    }

    /**
     * Try to convert date to format.
     *
     * @param mixed  $val
     * @param string $format
     *
     * @return string
     */
    protected function _convertDate($val, $format = 'Y-m-d')
    {
        try {
            $date = (new \DateTime($val))->format($format);
        } catch (\Exception $e) {
            $date = (string) $val;
        }

        return $date;
    }

    /**
     * Check date in format YYYY-MM-DD.
     *
     * @param mixed $val
     *
     * @return bool
     */
    protected function _date($val)
    {
        return
            $val &&
            is_string($val) &&
            preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $val, $match) &&
            $match[1] > 1900 &&
            $match[2] > 0 && $match[2] < 13 &&
            $match[3] > 0 && $match[3] <= date('j', mktime(0, 0, 0, $match[2] + 1, 0, $match[1] + 0))
        ;
    }

    /**
     * Check date in format YYYY-MM-DD HH:MM:SS.
     *
     * @param mixed $val
     *
     * @return bool
     */
    protected function _datetime($val)
    {
        return
            $val &&
            is_string($val) &&
            preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val, $match) &&
            $match[1] > 1900 &&
            $match[2] > 0 && $match[2] < 13 &&
            $match[3] > 0 && $match[3] <= date('j', mktime(0, 0, 0, $match[2] + 1, 0, $match[1] + 0)) &&
            $match[4] >= 0 && $match[4] < 24 &&
            $match[5] >= 0 && $match[5] <= 60 &&
            $match[6] >= 0 && $match[6] <= 60
        ;
    }

    /**
     * Perform regex.
     *
     * @param mixed  $val
     * @param string $pattern
     *
     * @return bool
     */
    protected function _regex($val, $pattern)
    {
        $quote = $pattern[0];

        if (in_array($quote, array("'", '"')) && substr($pattern, -1) === $quote) {
            $pattern = substr($pattern, 1, -1);
        }

        return (bool) preg_match($pattern, $val);
    }

    /**
     * Check if val in choices.
     *
     * @param mixed $val
     * @param array $choices
     *
     * @return bool
     */
    protected function _choice($val, array $choices)
    {
        return in_array($val, $choices);
    }

    /**
     * Check if multiple val in choices.
     *
     * @param mixed $val
     * @param array $choices
     *
     * @return bool
     */
    protected function _choices($val, array $choices)
    {
        $vals = (array) $val;
        $intersection = array_intersect($vals, $choices);

        return count($intersection) === count($vals);
    }

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
