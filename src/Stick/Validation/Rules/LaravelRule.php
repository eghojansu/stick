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

use Fal\Stick\Db\Pdo\Mapper;
use Fal\Stick\Validation\Field;
use Fal\Stick\Validation\RuleInterface;
use Fal\Stick\Validation\RuleTrait;

/**
 * Laravel based validation rules.
 *
 * Inspired by laravel validation rules.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class LaravelRule implements RuleInterface
{
    use RuleTrait;

    /**
     * The field under validation must be yes, on, 1, or true.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _accepted(Field $field): bool
    {
        return in_array(
            $field->value(),
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
     *
     * @param Field  $field
     * @param string $date
     *
     * @return bool
     */
    protected function _after(Field $field, string $date): bool
    {
        $check = $field->time();
        $min = $field->time($date);

        return $check && $min && $check > $min;
    }

    /**
     * The field under validation must be a value after or equal to the given date.
     *
     * @see _after
     *
     * @param Field  $field
     * @param string $date
     *
     * @return bool
     */
    protected function _afterOrEqual(Field $field, string $date): bool
    {
        $check = $field->time();
        $min = $field->time($date);

        return $check && $min && $check >= $min;
    }

    /**
     * The field under validation must be entirely alphabetic characters.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _alpha(Field $field): bool
    {
        return (bool) preg_match('/^[[:alpha:]_]+$/', $field->value());
    }

    /**
     * The field under validation may have alpha-numeric characters, as well as dashes and underscores.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _aldash(Field $field): bool
    {
        return (bool) preg_match('/^[[:alpha:]_-]+$/', $field->value());
    }

    /**
     * The field under validation must be entirely alpha-numeric characters.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _alnum(Field $field): bool
    {
        return (bool) preg_match('/^[[:alnum:]]+$/', $field->value());
    }

    /**
     * The field under validation must be a PHP array.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _array(Field $field): bool
    {
        return is_array($field->value());
    }

    /**
     * The field under validation must be a value preceding the given date.
     *
     * @see _after
     *
     * @param Field  $field
     * @param string $date
     *
     * @return bool
     */
    protected function _before(Field $field, string $date): bool
    {
        $check = $field->time();
        $max = $field->time($date);

        return $check && $max && $check < $max;
    }

    /**
     * The field under validation must be a value preceding or equal to the given date.
     *
     * @see _before
     *
     * @param Field  $field
     * @param string $date
     *
     * @return bool
     */
    protected function _beforeOrEqual(Field $field, string $date): bool
    {
        $check = $field->time();
        $max = $field->time($date);

        return $check && $max && $check <= $max;
    }

    /**
     * The field under validation must have a size between the given min and max.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * Also instead of passing integer min and max,
     * you may specify another field name to compare against the value.
     *
     * @param Field $field
     * @param int   $min
     * @param int   $max
     *
     * @return bool
     */
    protected function _between(Field $field, int $min, int $max): bool
    {
        $check = $field->getSize();

        return $check >= $min && $check <= $max;
    }

    /**
     * The field under validation must be able to be cast as a boolean.
     *
     * Accepted input are true, false, 1, 0, "1", and "0".
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _bool(Field $field): bool
    {
        return in_array(
            $field->value(),
            array(true, false, 1, 0, '1', '0'),
            true
        );
    }

    /**
     * The field under validation must have a matching to another field.
     *
     * Defaults to {fieldName}_confirmation.
     *
     * @param Field       $field
     * @param string|null $cfield
     *
     * @return bool
     */
    protected function _confirmed(Field $field, string $cfield = null): bool
    {
        return $field->equalsTo($cfield ?? $field->field().'_confirmation');
    }

    /**
     * Convert to formatted date.
     *
     * @param Field  $field
     * @param string $format
     *
     * @return string
     */
    protected function _convert(Field $field, string $format): string
    {
        return date($format, $field->time());
    }

    /**
     * The field under validation must be a valid, non-relative date according to the strtotime PHP function.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _date(Field $field): bool
    {
        return false !== $field->time();
    }

    /**
     * The field under validation must be equal to the given date.
     *
     * @see _date
     *
     * @param Field  $field
     * @param string $date
     *
     * @return bool
     */
    protected function _dateEquals(Field $field, string $date): bool
    {
        return $field->time() === $field->time($date);
    }

    /**
     * The field under validation must match the given format.
     *
     * You should use either date or date_format when validating a field, not both.
     *
     * @param Field  $field
     * @param string $format
     *
     * @return bool
     */
    protected function _dateFormat(Field $field, string $format): bool
    {
        $check = $field->value();

        return is_string($check) && false !== date_create_from_format($format, $check);
    }

    /**
     * The field under validation must have a different value than field.
     *
     * @param Field  $field
     * @param string $cfield
     *
     * @return bool
     */
    protected function _different(Field $field, string $cfield): bool
    {
        return !$field->equalsTo($cfield);
    }

    /**
     * The field under validation must be numeric and must have an exact length of value.
     *
     * @param Field $field
     * @param int   $length
     *
     * @return bool
     */
    protected function _digits(Field $field, int $length): bool
    {
        return is_numeric($field->value()) && $field->getSize(false) === $length;
    }

    /**
     * The field under validation must have a length between the given min and max.
     *
     * @param Field $field
     * @param int   $min
     * @param int   $max
     *
     * @return bool
     */
    protected function _digitsBetween(Field $field, int $min, int $max): bool
    {
        if (is_numeric($field->value())) {
            $check = $field->getSize(false);

            return $check >= $min && $check <= $max;
        }

        return false;
    }

    /**
     * When working with arrays, the field under validation must not have any duplicate values.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _distinct(Field $field): bool
    {
        $check = $field->value();

        return is_array($check) && count($check) === count(array_unique($check));
    }

    /**
     * Return TRUE if string is a valid e-mail address, check DNS MX records if specified.
     *
     * @param Field $field
     * @param bool  $mx
     *
     * @return bool
     */
    protected function _email(Field $field, bool $mx = false): bool
    {
        return $field->filter(FILTER_VALIDATE_EMAIL) &&
            (!$mx || getmxrr($field->after('@'), $hosts = array()));
    }

    /**
     * The field under validation must end with one of the given values (case-insensitive).
     *
     * @param Field  $field
     * @param string ...$suffixes
     *
     * @return bool
     */
    protected function _endsWith(Field $field, string ...$suffixes): bool
    {
        $value = $field->value();

        foreach ($suffixes as $suffix) {
            if (preg_match('/'.preg_quote($suffix, '/').'$/i', $value)) {
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
     *
     * @param Field       $field
     * @param string      $table
     * @param string|null $column
     *
     * @return bool
     */
    protected function _exists(Field $field, string $table, string $column = null): bool
    {
        list($conn, $table) = false === strpos($table, '.') ?
            array('db', $table) : explode('.', $table);
        $db = $field->fw->service($conn, 'Fal\\Stick\\Db\\Pdo\\Db');

        return (new Mapper($db, $table))
            ->findOne(array(
                $column ?? $field->field() => $field->value(),
            ))
            ->valid();
    }

    /**
     * Returns true if field equal to value.
     *
     * @param Field $field
     * @param mixed $value
     * @param bool  $strict
     *
     * @return bool
     */
    protected function _equalTo(Field $field, $value, bool $strict = false): bool
    {
        return $strict ? $field->value() === $value : $field->value() == $value;
    }

    /**
     * The field under validation must be a successfully uploaded file.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _file(Field $field): bool
    {
        return $field->file($file);
    }

    /**
     * The field under validation must be greater than the given field.
     *
     * @param Field $field
     * @param int   $min
     *
     * @return bool
     */
    protected function _gt(Field $field, int $min): bool
    {
        return $field->value() > $min;
    }

    /**
     * The field under validation must be greater than or equal to the given field.
     *
     * @param Field $field
     * @param int   $min
     *
     * @return bool
     */
    protected function _gte(Field $field, int $min): bool
    {
        return $field->value() >= $min;
    }

    /**
     * The file under validation must be an image (jpeg, png, bmp, gif, or svg).
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _image(Field $field): bool
    {
        return $field->file($file) && preg_match(
            '/^image\/(jpeg|png|bmp|gif|svg)$/',
            $file['type']
        );
    }

    /**
     * The field under validation must be included in the given list of values.
     *
     * @param Field $field
     * @param mixed ...$values
     *
     * @return bool
     */
    protected function _in(Field $field, ...$values): bool
    {
        return in_array($field->value(), $values);
    }

    /**
     * The field under validation must exist in another field's values.
     *
     * @param Field  $field
     * @param string $cfield
     *
     * @return bool
     */
    protected function _inField(Field $field, string $cfield): bool
    {
        $values = $field->fieldValue($cfield);

        return is_array($values) && in_array($field->value(), $values);
    }

    /**
     * The field under validation must be an integer.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _integer(Field $field): bool
    {
        $value = $field->value();

        return is_numeric($value) && is_int($value + 0);
    }

    /**
     * Return TRUE if string is a IP address.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _ip(Field $field): bool
    {
        return $field->filter(FILTER_VALIDATE_IP);
    }

    /**
     * Return TRUE if string is a valid IPV4 address.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _ipv4(Field $field): bool
    {
        return $field->filter(FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Return TRUE if string is a valid IPV6 address.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _ipv6(Field $field): bool
    {
        return $field->filter(FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * The field under validation must be less than the given field.
     *
     * @param Field $field
     * @param int   $max
     *
     * @return bool
     */
    protected function _lt(Field $field, int $max): bool
    {
        return $field->value() < $max;
    }

    /**
     * The field under validation must be less than or equal to the given field.
     *
     * @param Field $field
     * @param int   $max
     *
     * @return bool
     */
    protected function _lte(Field $field, int $max): bool
    {
        return $field->value() <= $max;
    }

    /**
     * The field under validation must be less than or equal to a maximum value.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * @see _between
     *
     * @param Field $field
     * @param int   $max
     *
     * @return bool
     */
    protected function _max(Field $field, int $max): bool
    {
        return $field->getSize() <= $max;
    }

    /**
     * The file under validation must have a MIME type corresponding to one of the listed extensions.
     *
     * @param Field  $field
     * @param string ...$types
     *
     * @return bool
     */
    protected function _mimes(Field $field, string ...$types): bool
    {
        return $field->file($file) && preg_match(
            '/\/('.preg_quote(implode('|', $types), '/').')$/',
            $file['type']
        );
    }

    /**
     * The file under validation must match one of the given MIME types.
     *
     * @param Field  $field
     * @param string ...$mimes
     *
     * @return bool
     */
    protected function _mimeTypes(Field $field, string ...$mimes): bool
    {
        if ($field->file($file)) {
            foreach ($mimes as $mime) {
                if (preg_match('/^'.preg_quote($mime, '/').'$/', $file['type'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The field under validation must have a minimum value.
     *
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     *
     * @see _between
     *
     * @param Field $field
     * @param int   $min
     *
     * @return bool
     */
    protected function _min(Field $field, int $min): bool
    {
        return $field->getSize() >= $min;
    }

    /**
     * The field under validation must not be included in the given list of values.
     *
     * @param Field $field
     * @param mixed ...$values
     *
     * @return bool
     */
    protected function _notIn(Field $field, ...$values): bool
    {
        return !$this->_in($field, ...$values);
    }

    /**
     * The field under validation must not exist in another field's values.
     *
     * @param Field  $field
     * @param string $cfield
     *
     * @return bool
     */
    protected function _notInField(Field $field, string $cfield): bool
    {
        return !$this->_inField($field, $cfield);
    }

    /**
     * The field under validation must not match the given regular expression.
     *
     * @param Field  $field
     * @param string $pattern
     *
     * @return bool
     */
    protected function _notRegex(Field $field, string $pattern): bool
    {
        return !$this->_regex($field, $pattern);
    }

    /**
     * This rule will always returns true.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _optional(Field $field): bool
    {
        return true;
    }

    /**
     * Verify given password with current user password.
     *
     * @param Field       $field
     * @param string|null $auth
     *
     * @return bool
     */
    protected function _password(Field $field, string $auth = null): bool
    {
        $service = $field->fw->service($auth ?? 'auth', 'Fal\\Stick\\Security\\Auth');
        $user = $service->getUser();

        return $user ? $service->encoder->verify($field->value(), $user->getPassword()) : true;
    }

    /**
     * Returns true if field not equal to value.
     *
     * @param Field $field
     * @param mixed $value
     * @param bool  $strict
     *
     * @return bool
     */
    protected function _notEqualTo(Field $field, $value, bool $strict = false): bool
    {
        return $strict ? $field->value() !== $value : $field->value() != $value;
    }

    /**
     * The field under validation must be numeric.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _numeric(Field $field): bool
    {
        return is_numeric($field->value());
    }

    /**
     * The field under validation must match the given regular expression.
     *
     * @param Field  $field
     * @param string $pattern
     *
     * @return bool
     */
    protected function _regex(Field $field, string $pattern): bool
    {
        $q = $pattern[0];

        if (('"' === $q || "'" === $q) && substr($pattern, -1) === $q) {
            $pattern = substr($pattern, 1, -1);
        }

        return (bool) preg_match($pattern, $field->value());
    }

    /**
     * The field under validation must be no, off, 0, or false.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _rejected(Field $field): bool
    {
        return in_array(
            $field->value(),
            array(0, false, 'false', 'no', 'off'),
            true
        );
    }

    /**
     * The field under validation must be present in the input data and not empty.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _required(Field $field): bool
    {
        return !in_array(
            $field->value(),
            array(null, '', array()),
            true
        );
    }

    /**
     * The given field must match the field under validation.
     *
     * @param Field  $field
     * @param string $cfield
     *
     * @return bool
     */
    protected function _same(Field $field, string $cfield): bool
    {
        return $field->equalsTo($cfield);
    }

    /**
     * The field under validation must have a size matching the given value.
     *
     * For string data, value corresponds to the number of characters.
     * For numeric data, value corresponds to a given integer value.
     * For an array, size corresponds to the count of the array.
     * For files, size corresponds to the file size in kilobytes.
     *
     * @param Field $field
     * @param int   $size
     *
     * @return bool
     */
    protected function _size(Field $field, int $size): bool
    {
        return $field->getSize(false) === $size;
    }

    /**
     * The field under validation must start with one of the given values (case-insensitive).
     *
     * @param Field  $field
     * @param string ...$prefixes
     *
     * @return bool
     */
    protected function _startsWith(Field $field, string ...$prefixes): bool
    {
        $value = $field->value();

        foreach ($prefixes as $prefix) {
            if (preg_match('/^'.preg_quote($prefix, '/').'/i', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The field under validation must be a string.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _string(Field $field): bool
    {
        $value = $field->value();

        return is_string($value) && !is_numeric($value);
    }

    /**
     * The field under validation must be a valid timezone identifier
     * according to the timezone_identifiers_list PHP function.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _timezone(Field $field): bool
    {
        return in_array($field->value(), timezone_identifiers_list());
    }

    /**
     * Trim current value.
     *
     * @param Field $field
     *
     * @return mixed
     */
    protected function _trim(Field $field)
    {
        $check = $field->value();

        return is_string($check) ? trim($check) : $check;
    }

    /**
     * The field under validation must not exist within the given database table.
     *
     * @see _exists
     *
     * @param Field       $field
     * @param string      $table
     * @param mixed       $except
     * @param string|null $column
     * @param string|null $idColumn
     *
     * @return bool
     */
    protected function _unique(
        Field $field,
        string $table,
        $except = null,
        string $column = null,
        string $idColumn = null
    ): bool {
        list($conn, $table) = false === strpos($table, '.') ?
            array('db', $table) : explode('.', $table);
        $db = $field->fw->service($conn, 'Fal\\Stick\\Db\\Pdo\\Db');

        $mapper = (new Mapper($db, $table))
            ->findOne(array(
                $column ?? $field->field() => $field->value(),
            ));

        if ($except && !$idColumn) {
            $idColumn = 'id';
        }

        return !$mapper->valid() || (
            $idColumn && (
                !$mapper->schema->has($idColumn) ||
                $mapper->get($idColumn) == $except
            )
        );
    }

    /**
     * The field under validation must be a valid URL.
     *
     * @param Field $field
     *
     * @return bool
     */
    protected function _url(Field $field): bool
    {
        return $field->filter(FILTER_VALIDATE_URL);
    }

    /**
     * Convert field value to string.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function _asString(Field $field): string
    {
        $value = $field->value();

        return (string) $value;
    }

    /**
     * Convert field value to number.
     *
     * @param Field $field
     *
     * @return mixed
     */
    protected function _asNumber(Field $field)
    {
        return 1 * $field->value();
    }

    /**
     * Convert field value to array.
     *
     * @param Field $field
     *
     * @return array
     */
    protected function _asArray(Field $field): array
    {
        $value = $field->value();

        return (array) $value;
    }

    /**
     * Convert field value using json_encode.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function _toJson(Field $field): string
    {
        return json_encode($field->value());
    }

    /**
     * Convert field value using json_decode.
     *
     * @param Field $field
     * @param bool  $assoc
     *
     * @return mixed
     */
    protected function _fromJson(Field $field, bool $assoc = true)
    {
        return json_decode($field->value(), $assoc);
    }

    /**
     * Split into array.
     *
     * @param Field  $field
     * @param string $rule
     * @param bool   $regexp
     *
     * @return array
     */
    protected function _split(Field $field, string $rule = null, bool $regexp = false): array
    {
        if ($regexp && $rule) {
            $q = $rule[0];

            if (('"' === $q || "'" === $q) && substr($rule, -1) === $q) {
                $rule = substr($rule, 1, -1);
            }

            return preg_split($rule, $field->value(), 0, PREG_SPLIT_NO_EMPTY);
        }

        return $field->fw->split($field->value(), $rule);
    }

    /**
     * Join into string.
     *
     * @param Field  $field
     * @param string $glue
     *
     * @return string
     */
    protected function _join(Field $field, string $glue = ','): string
    {
        return implode($glue, $field->value());
    }
}
