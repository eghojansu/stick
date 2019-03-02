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
 * Common validation rules.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class CommonRule implements RuleInterface
{
    use RuleTrait;

    /**
     * Proxy to trim, prevent trimming null value.
     *
     * @param mixed $val
     *
     * @return string
     */
    protected function _trim($val)
    {
        return trim((string) $val);
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
    protected function _equalToField($val, $compared)
    {
        if ($this->context && null !== $compare = $this->context->data($compared)) {
            return $compare === $val;
        }

        if ($this->context && null !== $compare = $this->context->raw($compared)) {
            return $compare === $val;
        }

        return false;
    }

    /**
     * Not equal to other field.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _notEqualToField($val, $compared)
    {
        if ($this->context && null !== $compare = $this->context->data($compared)) {
            return $compare !== $val;
        }

        if ($this->context && null !== $compare = $this->context->raw($compared)) {
            return $compare !== $val;
        }

        return true;
    }

    /**
     * Equal to.
     *
     * @param mixed $val
     * @param mixed $compared
     *
     * @return bool
     */
    protected function _equalTo($val, $compared)
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
    protected function _notEqualTo($val, $compared)
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
    protected function _identicalTo($val, $compared)
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
    protected function _notIdenticalTo($val, $compared)
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
    protected function _toDate($val, $format = 'Y-m-d')
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
}
