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

namespace Ekok\Stick\Validation;

use Ekok\Stick\Fw;

/**
 * Validation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Validation
{
    protected $fw;
    protected $rules = array();
    protected $options = array(
        'sql_key' => 'db',
        'auth_key' => 'auth',
        'encoder_key' => 'encoder',
    );

    public function __construct(Fw $fw, array $options = null)
    {
        $this->fw = $fw;

        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function addRule(string $rule, $handler): Validation
    {
        $this->rules[$rule] = $handler;

        return $this;
    }

    public function validate(array $rules, array $raw = null, array $messages = null): Result
    {
        $result = new Result($raw ?? $this->fw->get('POST') ?? array());

        foreach ($rules as $field => $fieldRules) {
            $usedRules = is_array($fieldRules) ? $fieldRules : $this->parse($fieldRules);

            $this->validateField($result->setField($field), $usedRules, $messages);
        }

        return $result;
    }

    protected function parse(string $fieldRules): array
    {
        $result = array();

        foreach (explode('|', $fieldRules) as $line) {
            list($rule, $argLine) = explode(':', trim($line)) + array(1 => '');

            $result[$rule] = $argLine ? array_map(array(Fw::class, 'cast'), explode(',', $argLine)) : array();
        }

        return $result;
    }

    protected function validateField(Result $result, array $rules, array $messages = null): void
    {
        foreach ($rules as $rule => $arguments) {
            $value = $this->call($rule, $result->newRule(), $arguments);

            if (false === $value) {
                if ($result->noErrorAdded()) {
                    $result->errorAdd($this->buildMessage($rule, $result, $arguments, $messages));
                }
            } else {
                $result->setValue(true === $value ? $result->getValue() : $value);
            }

            if ($result->isSkip()) {
                break;
            }
        }
    }

    protected function buildMessage(string $rule, Result $result, array $arguments, array $messages = null): string
    {
        $key = $result->getField().'.'.$rule;
        $raw = $messages[$rule] ?? $messages[$key] ?? Fw::makeRef($key, $messages) ?? $this->fw->transRaw('validation.'.$rule) ?? 'This value is not valid.';

        if (false === strpos($raw, '%')) {
            return $raw;
        }

        $value = $result->getValue();
        $parameters = array(
            '%field%' => $result->getField(),
            '%value%' => is_scalar($value) ? $value : $this->fw->stringify($value),
        );

        foreach ($arguments as $key => $val) {
            $parameters["%{$key}%"] = is_scalar($val) ? $val : $this->fw->stringify($val);
        }

        return strtr($raw, $parameters);
    }

    protected function call(string $rule, Result $result, array $arguments)
    {
        if ($function = $this->rules[$rule] ?? null) {
            return $this->fw->call($function, $result, ...$arguments);
        }

        if (!method_exists($this, $validator = '_'.$rule)) {
            throw new \LogicException("Rule not exists: '{$rule}'.");
        }

        return $this->{$validator}($result, ...$arguments);
    }

    /**
     * The field under validation must be yes, on, 1, or true.
     */
    protected function _accepted(Result $result): bool
    {
        return in_array(
            $result->getValue(),
            array(1, true, '1', 'true', 'yes', 'on'),
            true
        );
    }

    /**
     * The field under validation must be a value after a given date.
     *
     * The dates will be passed into the strtotime PHP function.
     *
     * Also instead of passing a date string to be evaluated by strtotime,
     * you may specify another field to compare against the date.
     */
    protected function _after(Result $result, string $date): bool
    {
        $check = $result->getValueAsTime();
        $min = $result->getValueAsTime($date);

        return $check && $min && $check > $min;
    }

    /**
     * The field under validation must be a value after or equal to the given date.
     *
     * @see _after
     */
    protected function _afterOrEqual(Result $result, string $date): bool
    {
        $check = $result->getValueAsTime();
        $min = $result->getValueAsTime($date);

        return $check && $min && $check >= $min;
    }

    /**
     * The field under validation must be entirely alphabetic characters.
     */
    protected function _alpha(Result $result): bool
    {
        return (bool) preg_match('/^[[:alpha:]_]+$/', $result->getValue());
    }

    /**
     * The field under validation may have alpha-numeric characters, as well as dashes and underscores.
     */
    protected function _aldash(Result $result): bool
    {
        return (bool) preg_match('/^[[:alpha:]_-]+$/', $result->getValue());
    }

    /**
     * The field under validation must be entirely alpha-numeric characters.
     */
    protected function _alnum(Result $result): bool
    {
        return (bool) preg_match('/^[[:alnum:]]+$/', $result->getValue());
    }

    /**
     * The field under validation must be a PHP array.
     */
    protected function _array(Result $result): bool
    {
        return is_array($result->getValue());
    }

    /**
     * The field under validation must be a value preceding the given date.
     *
     * @see _after
     */
    protected function _before(Result $result, string $date): bool
    {
        $check = $result->getValueAsTime();
        $max = $result->getValueAsTime($date);

        return $check && $max && $check < $max;
    }

    /**
     * The field under validation must be a value preceding or equal to the given date.
     *
     * @see _before
     */
    protected function _beforeOrEqual(Result $result, string $date): bool
    {
        $check = $result->getValueAsTime();
        $max = $result->getValueAsTime($date);

        return $check && $max && $check <= $max;
    }

    /**
     * The field under validation must have a size between the given min and max.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * Also instead of passing integer min and max,
     * you may specify another field name to compare against the value.
     */
    protected function _between(Result $result, int $min, int $max): bool
    {
        $check = $result->getValueSize();

        return $check >= $min && $check <= $max;
    }

    /**
     * The field under validation must be able to be cast as a boolean.
     *
     * Accepted input are true, false, 1, 0, "1", and "0".
     */
    protected function _bool(Result $result): bool
    {
        return in_array(
            $result->getValue(),
            array(true, false, 1, 0, '1', '0'),
            true
        );
    }

    /**
     * The field under validation must have a matching to another field.
     *
     * Defaults to {fieldName}_confirmation.
     */
    protected function _confirmed(Result $result, string $field = null): bool
    {
        return $result->isValueEqualTo($field ?? $result->getField().'_confirmation');
    }

    /**
     * Convert to formatted date.
     */
    protected function _convert(Result $result, string $format): string
    {
        return date($format, $result->getValueAsTime());
    }

    /**
     * The field under validation must be a valid,
     * non-relative date according to the strtotime PHP function.
     */
    protected function _date(Result $result): bool
    {
        return $result->getValueAsTime() > 0;
    }

    /**
     * The field under validation must be equal to the given date.
     *
     * @see _date
     */
    protected function _dateEquals(Result $result, string $date): bool
    {
        return $result->getValueAsTime() === $result->getValueAsTime($date);
    }

    /**
     * The field under validation must match the given format.
     *
     * You should use either date or date_format when validating a field, not both.
     */
    protected function _dateFormat(Result $result, string $format): bool
    {
        $value = $result->getValue();

        return is_string($value) && false !== date_create_from_format($format, $value);
    }

    /**
     * The field under validation must have a different value than field.
     */
    protected function _different(Result $result, string $field): bool
    {
        return !$result->isValueEqualTo($field);
    }

    /**
     * The field under validation must be numeric and must have an exact length of value.
     */
    protected function _digits(Result $result, int $length): bool
    {
        return is_numeric($result->getValue()) && $result->getValueSize(false) === $length;
    }

    /**
     * The field under validation must have a length between the given min and max.
     */
    protected function _digitsBetween(Result $result, int $min, int $max): bool
    {
        if (is_numeric($result->getValue())) {
            $check = $result->getValueSize(false);

            return $check >= $min && $check <= $max;
        }

        return false;
    }

    /**
     * When working with arrays, the field under validation must not have any duplicate values.
     */
    protected function _distinct(Result $result): bool
    {
        $check = $result->getValue();

        return is_array($check) && count($check) === count(array_unique($check));
    }

    /**
     * Return true if string is a valid e-mail address, check DNS MX records if specified.
     */
    protected function _email(Result $result, bool $mx = false): bool
    {
        return $result->filterValue(FILTER_VALIDATE_EMAIL) && (!$mx || getmxrr($result->getValueAfter('@'), $hst = array()));
    }

    /**
     * The field under validation must end with one of the given values (case-insensitive).
     *
     * @param string ...$suffixes
     */
    protected function _endsWith(Result $result, string ...$suffixes): bool
    {
        $value = $result->getValue();

        foreach ($suffixes as $suffix) {
            if ($value && preg_match('/'.preg_quote($suffix, '/').'$/i', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The field under validation must exist on a given database table.
     *
     * If the column option is not specified, the field name will be used.
     *
     * Occasionally, you may need to specify a specific database connection
     * to be used for the exists query. You can accomplish this by prepending
     * the connection name to the table name using "dot" syntax.
     */
    protected function _sqlExists(Result $result, string $table, string $column = null): bool
    {
        list($conn, $table) = false === strpos($table, '.') ? array($this->options['sql_key'], $table) : explode('.', $table);

        $sql = $this->fw->get($conn);
        $get = $column ?? $this->getField();
        $row = $sql->findOne($table, array("{$get} = ?", $result->getValue()));

        return (bool) $row;
    }

    /**
     * The field under validation must not exist within the given database table.
     *
     * @see _exists
     *
     * @param mixed $except
     */
    protected function _sqlUnique(
        Result $result,
        string $table,
        $except = null,
        string $column = null,
        string $idColumn = null
    ): bool {
        list($conn, $table) = false === strpos($table, '.') ? array($this->options['sql_key'], $table) : explode('.', $table);

        $sql = $this->fw->get($conn);
        $get = $column ?? $this->getField();
        $row = $sql->findOne($table, array("{$get} = ?", $result->getValue()));

        if ($except && !$idColumn) {
            $idColumn = 'id';
        }

        return empty($mapper) || ($idColumn && (!isset($row[$idColumn]) || $row[$idColumn] == $except));
    }

    /**
     * Returns true if field equal to value.
     *
     * @param mixed $value
     */
    protected function _equalTo(Result $result, $value, bool $strict = false): bool
    {
        return $strict ? $result->getValue() === $value : $result->getValue() == $value;
    }

    /**
     * The field under validation must be greater than the given field.
     */
    protected function _gt(Result $result, int $min): bool
    {
        return $result->getValue() > $min;
    }

    /**
     * The field under validation must be greater than or equal to the given field.
     */
    protected function _gte(Result $result, int $min): bool
    {
        return $result->getValue() >= $min;
    }

    /**
     * The field under validation must be included in the given list of values.
     *
     * @param mixed ...$values
     */
    protected function _in(Result $result, ...$values): bool
    {
        return in_array($result->getValue(), $values);
    }

    /**
     * The field under validation must exist in another field's values.
     */
    protected function _inField(Result $result, string $field): bool
    {
        $values = $result->getValue($field);

        return is_array($values) && in_array($result->getValue(), $values);
    }

    /**
     * The field under validation must be an integer.
     */
    protected function _int(Result $result): bool
    {
        return $this->_integer($result);
    }

    /**
     * The field under validation must be an integer.
     */
    protected function _integer(Result $result): bool
    {
        $value = $result->getValue();

        return is_numeric($value) && is_int($value + 0);
    }

    /**
     * Return TRUE if string is a IP address.
     */
    protected function _ip(Result $result): bool
    {
        return $result->filterValue(FILTER_VALIDATE_IP);
    }

    /**
     * Return TRUE if string is a valid IPV4 address.
     */
    protected function _ipv4(Result $result): bool
    {
        return $result->filterValue(FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Return TRUE if string is a valid IPV6 address.
     */
    protected function _ipv6(Result $result): bool
    {
        return $result->filterValue(FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * The field under validation must be less than the given field.
     */
    protected function _lt(Result $result, int $max): bool
    {
        return $result->getValue() < $max;
    }

    /**
     * The field under validation must be less than or equal to the given field.
     */
    protected function _lte(Result $result, int $max): bool
    {
        return $result->getValue() <= $max;
    }

    /**
     * The field under validation must be less than or equal to a maximum value.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * @see _between
     */
    protected function _max(Result $result, int $max): bool
    {
        return $result->getValueSize() <= $max;
    }

    /**
     * The field under validation must have a minimum value.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * @see _between
     */
    protected function _min(Result $result, int $min): bool
    {
        return $result->getValueSize() >= $min;
    }

    /**
     * The field under validation must not be included in the given list of values.
     *
     * @param mixed ...$values
     */
    protected function _notIn(Result $result, ...$values): bool
    {
        return !$this->_in($result, ...$values);
    }

    /**
     * The field under validation must not exist in another field's values.
     */
    protected function _notInField(Result $result, string $field): bool
    {
        return !$this->_inField($result, $field);
    }

    /**
     * The field under validation must not match the given regular expression.
     */
    protected function _notRegex(Result $result, string $pattern): bool
    {
        return !$this->_regex($result, $pattern);
    }

    /**
     * This rule will always returns true.
     */
    protected function _optional(Result $result): bool
    {
        return $result->isValueEmpty() ? $result->skip()->isSkip() : true;
    }

    /**
     * Verify given password with current user password.
     */
    protected function _authPassword(Result $result, string $auth = null, string $encoder = null): bool
    {
        $authE = $this->fw->get($auth ?? $this->options['auth_key']);
        $encoderE = $this->fw->get($encoder ?? $this->options['encoder_key']);

        $user = $authE->getUser();

        return $user ? $encoderE->verify($result->getValue(), $user->getPassword()) : false;
    }

    /**
     * Returns true if field not equal to value.
     *
     * @param mixed $value
     */
    protected function _notEqualTo(Result $result, $value, bool $strict = false): bool
    {
        return $strict ? $result->getValue() !== $value : $result->getValue() != $value;
    }

    /**
     * The field under validation must be numeric.
     */
    protected function _numeric(Result $result): bool
    {
        return is_numeric($result->getValue());
    }

    /**
     * The field under validation must match the given regular expression.
     */
    protected function _regex(Result $result, string $pattern): bool
    {
        return (bool) preg_match($pattern, $result->getValue());
    }

    /**
     * The field under validation must be no, off, 0, or false.
     */
    protected function _rejected(Result $result): bool
    {
        return in_array(
            $result->getValue(),
            array(0, false, 'false', 'no', 'off'),
            true
        );
    }

    /**
     * The field under validation must be present in the input data and not empty.
     */
    protected function _required(Result $result): bool
    {
        return !in_array(
            $result->getValue(),
            array(null, '', array()),
            true
        );
    }

    /**
     * The given field must match the field under validation.
     */
    protected function _same(Result $result, string $field): bool
    {
        return $result->isValueEqualTo($field);
    }

    /**
     * The field under validation must have a size matching the given value.
     *
     * For string data, value corresponds to the number of characters.
     * For numeric data, value corresponds to a given integer value.
     * For an array, size corresponds to the count of the array.
     * For files, size corresponds to the file size in kilobytes.
     */
    protected function _size(Result $result, int $size): bool
    {
        return $result->getValueSize(false) === $size;
    }

    /**
     * The field under validation must start with one of the given values (case-insensitive).
     *
     * @param string ...$prefixes
     */
    protected function _startsWith(Result $result, string ...$prefixes): bool
    {
        $value = $result->getValue();

        foreach ($prefixes as $prefix) {
            if ($value && preg_match('/^'.preg_quote($prefix, '/').'/i', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The field under validation must be a string.
     */
    protected function _string(Result $result): bool
    {
        $value = $result->getValue();

        return is_string($value);
    }

    /**
     * The field under validation must be a valid timezone identifier
     * according to the timezone_identifiers_list PHP function.
     */
    protected function _timezone(Result $result): bool
    {
        return in_array($result->getValue(), timezone_identifiers_list());
    }

    /**
     * Trim current value.
     *
     * @return mixed
     */
    protected function _trim(Result $result)
    {
        $check = $result->getValue();

        return is_string($check) ? trim($check) : $check;
    }

    /**
     * The field under validation must be a valid URL.
     */
    protected function _url(Result $result): bool
    {
        return $result->filterValue(FILTER_VALIDATE_URL);
    }

    /**
     * Convert field value to string.
     */
    protected function _asString(Result $result): string
    {
        $value = $result->getValue();

        return (string) $value;
    }

    /**
     * Convert field value to number.
     *
     * @return mixed
     */
    protected function _asNumber(Result $result)
    {
        return 0 + $result->getValue();
    }

    /**
     * Convert field value to array.
     */
    protected function _asArray(Result $result): array
    {
        $value = $result->getValue();

        return (array) $value;
    }

    /**
     * Convert field value using json_encode.
     */
    protected function _toJson(Result $result, ...$flags): string
    {
        $flag = 0;

        foreach ($flags as $flagName) {
            $flag |= $flagName;
        }

        return json_encode($result->getValue(), $flag);
    }

    /**
     * Convert field value using json_decode.
     *
     * @return mixed
     */
    protected function _fromJson(Result $result, bool $assoc = true)
    {
        return json_decode($result->getValue(), $assoc);
    }

    /**
     * Split into array.
     *
     * @param string $pattern
     */
    protected function _split(Result $result, string $pattern = null): array
    {
        if ($pattern) {
            return preg_split($pattern, $result->getValue(), 0, PREG_SPLIT_NO_EMPTY);
        }

        return $this->fw->split($result->getValue());
    }

    /**
     * Join into string.
     */
    protected function _join(Result $result, string $glue = ','): string
    {
        return implode($glue, $result->getValue());
    }
}
