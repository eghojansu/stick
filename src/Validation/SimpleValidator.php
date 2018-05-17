<?php

declare(strict_types=1);

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
 * Simple validator rules.
 */
final class SimpleValidator extends AbstractValidator
{
    /** @var array Rule message */
    protected $messages = [
        'required' => 'This value should not be blank.',
        'type' => 'This value should be of type {0}.',
        'min' => 'This value should be {0} or more.',
        'max' => 'This value should be {0} or less.',
        'lt' => 'This value should be less than {0}.',
        'gt' => 'This value should be greater than {0}.',
        'lte' => 'This value should be less than or equal to {0}.',
        'gte' => 'This value should be greater than or equal to {0}.',
        'equalfield' => 'This value should be equal to value of {0}.',
        'notequalfield' => 'This value should not be equal to value of {0}.',
        'equal' => 'This value should be equal to {0}.',
        'notequal' => 'This value should not be equal to {0}.',
        'identical' => 'This value should be identical to {1} {0}.',
        'notidentical' => 'This value should not be identical to {1} {0}.',
        'len' => 'This value is not valid. It should have exactly {0} characters.',
        'lenmin' => 'This value is too short. It should have {0} characters or more.',
        'lenmax' => 'This value is too long. It should have {0} characters or less.',
        'count' => 'This collection should contain exactly {0} elements.',
        'countmin' => 'This collection should contain {0} elements or more.',
        'countmax' => 'This collection should contain {0} elements or less.',
        'regex' => null,
        'choice' => 'The value you selected is not a valid choice.',
        'choices' => 'One or more of the given values is invalid.',
        'date' => 'This value is not a valid date.',
        'datetime' => 'This value is not a valid datetime.',
        'email' => 'This value is not a valid email address.',
        'url' => 'This value is not a valid url.',
        'ipv4' => 'This value is not a valid ipv4 address.',
        'ipv6' => 'This value is not a valid ipv6 address.',
        'isprivate' => 'This value is not a private ip address.',
        'isreserved' => 'This value is not a reserved ip address.',
        'ispublic' => 'This value is not a public ip address.',
    ];

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
     * Return true if string is a valid e-mail address;
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
