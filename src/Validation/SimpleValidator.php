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

namespace Fal\Stick\Validation;

/**
 * Simple validator rules.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class SimpleValidator extends AbstractValidator
{
    /**
     * Proxy to trim, prevent trimming null value.
     *
     * @param mixed  $val
     * @param string $chars
     *
     * @return string
     */
    protected function _trim($val, string $chars = " \t\n\r\0\x0B"): string
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
    protected function _ltrim($val, string $chars = " \t\n\r\0\x0B"): string
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
    protected function _rtrim($val, string $chars = " \t\n\r\0\x0B"): string
    {
        return rtrim((string) $val, $chars);
    }

    /**
     * Required rule.
     *
     * @param mixed $val
     *
     * @return bool
     */
    protected function _required($val): bool
    {
        return isset($val) && '' !== $val;
    }

    /**
     * Check variabel type.
     *
     * @param mixed  $val
     * @param string $type
     *
     * @return bool
     */
    protected function _type($val, string $type): bool
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
    protected function _equalField($val, $compared): bool
    {
        return ($this->currentData['validated'][$compared] ?? $this->currentData['raw'][$compared] ?? $val.'-') === $val;
    }

    /**
     * Not equal to other field.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _notEqualField($val, $compared): bool
    {
        return ($this->currentData['validated'][$compared] ?? $this->currentData['raw'][$compared] ?? $val) !== $val;
    }

    /**
     * Equal to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _equal($val, $compared): bool
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
    protected function _notEqual($val, $compared): bool
    {
        return $val != $compared;
    }

    /**
     * Identical to.
     *
     * @param mixed  $val
     * @param mixed  $compared
     * @param string $type
     *
     * @return bool
     */
    protected function _identical($val, $compared, string $type = 'string'): bool
    {
        return $val === $compared;
    }

    /**
     * Not identical to.
     *
     * @param mixed  $val
     * @param mixed  $compared
     * @param string $type
     *
     * @return bool
     */
    protected function _notIdentical($val, $compared, string $type = 'string'): bool
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
    protected function _lt($val, $min): bool
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
    protected function _gt($val, $max): bool
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
    protected function _lte($val, $min): bool
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
    protected function _gte($val, $max): bool
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
    protected function _min($val, $min): bool
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
    protected function _max($val, $max): bool
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
    protected function _len($val, int $len): bool
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
    protected function _lenMin($val, int $min): bool
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
    protected function _lenMax($val, int $max): bool
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
    protected function _count(array $val, int $count): bool
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
    protected function _countMin(array $val, int $min): bool
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
    protected function _countMax(array $val, int $max): bool
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
    protected function _cdate($val, string $format = 'Y-m-d'): string
    {
        try {
            $date = (new \DateTime($val))->format($format);
        } catch (\Throwable $e) {
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
    protected function _date($val): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $val);
    }

    /**
     * Check date in format YYYY-MM-DD HH:MM:SS.
     *
     * @param mixed $val
     *
     * @return bool
     */
    protected function _datetime($val): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $val);
    }

    /**
     * Perform regex.
     *
     * @param mixed  $val
     * @param string $pattern
     *
     * @return bool
     */
    protected function _regex($val, string $pattern): bool
    {
        $quote = $pattern[0];
        if (in_array($quote, ["'", '"']) && substr($pattern, -1) === $quote) {
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
    protected function _choice($val, array $choices): bool
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
    protected function _choices($val, array $choices): bool
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
    protected function _url($str): bool
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
    protected function _email($str, $mx = false): bool
    {
        $hosts = [];

        return is_string(filter_var($str, FILTER_VALIDATE_EMAIL)) && (!$mx || getmxrr(substr($str, strrpos($str, '@') + 1), $hosts));
    }

    /**
     * Return true if string is a valid IPV4 address.
     *
     * @param string $addr
     *
     * @return bool
     */
    protected function _ipv4($addr): bool
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
    protected function _ipv6($addr): bool
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
    protected function _isPrivate($addr): bool
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
    protected function _isReserved($addr): bool
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
    protected function _isPublic($addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
