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

class DefaultProvider implements ProviderInterface
{
    const MESSAGE_DEFAULT = 'This value is not valid.';

    protected $messages = array(
        'accepted' => array('This value should be accepted.'),
        'after' => array('This value should be after {argument_0}.'),
        'after_or_equal' => array('This value should be after or equal to {argument_0}.'),
        'alpha' => array('This value should be alpha characters.'),
        'alnum' => array('This value should be alpha or numeric characters.'),
        'array' => array('This value should be an array.'),
        'before' => array('This value should be before {argument_0}.'),
        'before_or_equal' => array('This should be before or equal to {argument_0}.'),
        'between' => array('This value should between {argument_0} and {argument_1}.'),
        'boolean' => array('This value should be boolean.'),
        'confirmed' => array('This value should be confirmed.'),
        'date' => array('This value should be a valid date.'),
        'date_equal' => array('This value should be equal to date {argument_0}.'),
        'date_format' => array('This value is not valid date format.'),
        'different' => array('This value should be different with {argument_0}.'),
        'digits' => array('This value should be digits characters.'),
        'digits_between' => array('This value should between {argument_0} and {argument_1} in length.'),
        'distinct' => array('This value is not unique.'),
        'email' => array('This value is not a valid email.'),
        'ends_with' => array('This value should ends with one of these values: {arguments}.'),
        'gt' => array('This value should greater than {argument_0}.'),
        'gte' => array('This value should greater than or equals {argument_0}.'),
        'in' => array('This value is not an option.'),
        'in_array' => array('This value should be in {argument_0}.'),
        'integer' => array('This value should be an integer.'),
        'ip' => array('This value should be a valid IP address.'),
        'ip4' => array('This value should be a valid IP4 address.'),
        'ip6' => array('This value should be a valid IP6 address.'),
        'json' => array('This value should be a valid json.'),
        'lt' => array('This value should be less than {argument_0}.'),
        'lte' => array('This value should be less than or equals {argument_0}.'),
        'match' => array('This value should match with expected pattern.'),
        'max' => array(
            'This value should not greater than {argument_0}.',
            'string' => 'This value is too long. It should have {argument_0} characters or less.',
        ),
        'min' => array(
            'This value should not less than {argument_0}.',
            'string' => 'This value is too short. It should have {argument_0} characters or more.',
        ),
        'not_in' => array('This value is not an option.'),
        'not_match' => array('This value should match with expected pattern.'),
        'numeric' => array('This value should be numeric.'),
        'required' => array('This value should not be blank.'),
        'required_if' => array('This value should not be blank.'),
        'required_unless' => array('This value should not be blank.'),
        'same' => array('This value should same with {argument_0}.'),
        'size' => array('This value should be {argument_0} in size.'),
        'starts_with' => array('This value should starts with one of these values: {arguments}.'),
        'string' => array('This value should be a string.'),
        'url' => array('This value should be an URL.'),
    );

    public function check(string $rule): bool
    {
        return method_exists($this, '_rule_' . $rule);
    }

    public function validate(string $rule, Context $context, ...$arguments)
    {
        return $this->{'_rule_' . $rule}($context, ...$arguments);
    }

    public function message(string $rule, Context $context, ...$arguments): string
    {
        $useRule = strtolower($rule);
        $message = $this->messages[$useRule][$context->getType()] ?? $this->messages[$useRule][0] ?? self::MESSAGE_DEFAULT;

        if (false === strpos($message, '{')) {
            return $message;
        }

        $data = array(
            '{position}' => $context->getPosition(),
            '{field}' => $context->getField(),
            '{prefix}' => $context->getPrefix(),
            '{suffix}' => $context->getSuffix(),
            '{path}' => $context->getPath(),
        );

        if (false !== strpos($message, '{value}')) {
            $data['{value}'] = Fw::stringify($context->getValue());
        }

        if (false !== strpos($message, '{arguments}')) {
            $data['{arguments}'] = Fw::stringify($arguments);
        }

        if (false !== strpos($message, '{argument_') && $arguments) {
            foreach ($arguments as $key => $value) {
                $data['{argument_' . $key . '}'] = Fw::stringify($value);
            }
        }

        return strtr($message, $data);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(string $rule, string $message, string $type = null): static
    {
        $this->messages[$rule][$type ?? 0] = $message;

        return $this;
    }

    public function addMessages(array $messages): static
    {
        foreach ($messages as $rule => $message) {
            $this->addMessage($rule, $message);
        }

        return $this;
    }

    public static function _rule_accepted(Context $context): bool
    {
        return in_array($context->getValue(), array('yes', 'on', 1, '1', true, 'true'), true);
    }

    public static function _rule_after(Context $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) == 1;
    }

    public static function _rule_after_or_equal(Context $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) >= 0;
    }

    public static function _rule_alpha(Context $context): bool
    {
        return ctype_alpha($context->getValue());
    }

    public static function _rule_alnum(Context $context): bool
    {
        return ctype_alnum($context->getValue());
    }

    public static function _rule_array(Context $context): bool
    {
        return $context->isArray();
    }

    public static function _rule_before(Context $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) == -1;
    }

    public static function _rule_before_or_equal(Context $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) <= 0;
    }

    public static function _rule_between(Context $context, $min, $max): bool
    {
        $length = $context->getSize();

        return $length >= $min && $length <= $max;
    }

    public static function _rule_boolean(Context $context): bool
    {
        if (is_bool($context->getValue()) || in_array($context->getValue(), array('true', 'false', 'TRUE', 'FALSE', 1, '1', 0, '0'), true)) {
            $context->setValue((bool) $context->getValue());
        }

        return $context->isBoolean();
    }

    public static function _rule_confirmed(Context $context, string $field = null): bool
    {
        return $context->checkOther($against = $field ?? "{$context->getField()}_confirmation") && $context->getValue() === $context->getOther($against);
    }

    public static function _rule_date(Context $context, bool $convert = false, string $format = null, string $timezone = null)
    {
        $value = $context->getDate(null, $format, $timezone);

        return $convert ? $value : null != $value;
    }

    public static function _rule_date_equal(Context $context, $date, string $format = null, string $timezone = null)
    {
        return $context->compareDate($date, $format, $timezone) == 0;
    }

    public static function _rule_date_format(Context $context, string $format, string $timezone = null)
    {
        return null != $context->getDate(null, $format, $timezone);
    }

    public static function _rule_different(Context $context, string $field): bool
    {
        return !$context->checkOther($field) || $context->getValue() !== $context->getOther($field);
    }

    public static function _rule_digits(Context $context): bool
    {
        return ctype_digit($context->getValue());
    }

    public static function _rule_digits_between(Context $context, $min, $max): bool
    {
        $value = $context->getValue();
        $length = strlen($value);

        return ctype_digit($value) && $length >= $min && $length <= $max;
    }

    public static function _rule_distinct(Context $context, bool $ignoreCase = false): bool
    {
        $data = $context->getData();
        $field = $context->getField();
        $values = array_column($data, $field) ?: ($data[$field] ?? $context->getValue());
        $unique = array_unique($ignoreCase ? array_map('strtolower', $values) : $values);

        return $values && count($values) == count($unique);
    }

    public static function _rule_email(Context $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_EMAIL);
    }

    public static function _rule_ends_with(Context $context, string ...$suffixes): bool
    {
        return array_reduce($suffixes, static function (bool $isTrue, string $suffix) use ($context) {
            return $isTrue || substr($context->getValue(), -strlen($suffix)) === $suffix;
        }, false);
    }

    public static function _rule_exclude(Context $context): bool
    {
        $context->setExcluded();

        return true;
    }

    public static function _rule_exclude_if(Context $context, $field = null, $value = null): bool
    {
        $context->setExcluded(
            ($field instanceof \Closure && $field($context))
                || (is_string($field) && $context->getOther($field) === $value)
        );

        return true;
    }

    public static function _rule_exclude_unless(Context $context, $field = null, $value = null): bool
    {
        $context->setExcluded(
            !($field instanceof \Closure && $field($context))
                && !(is_string($field) && $context->getOther($field) === $value)
        );

        return true;
    }

    public static function _rule_gt(Context $context, string $field): bool
    {
        return $context->checkOther($field) && $context->getSize() > $context->getSize($field);
    }

    public static function _rule_gte(Context $context, string $field): bool
    {
        return $context->checkOther($field) && $context->getSize() >= $context->getSize($field);
    }

    public static function _rule_in(Context $context, ...$elements): bool
    {
        return in_array($context->getValue(), $elements);
    }

    public static function _rule_in_array(Context $context, string $field): bool
    {
        return in_array($context->getValue(), (array) $context->getOther($field));
    }

    public static function _rule_integer(Context $context): bool
    {
        if (is_int($context->getValue() + 0)) {
            $context->setValue(intval($context->getValue()));
        }

        return $context->isInteger();
    }

    public static function _rule_ip(Context $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP);
    }

    public static function _rule_ip4(Context $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function _rule_ip6(Context $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    public static function _rule_json(Context $context, bool $convert = false, bool $assoc = true)
    {
        $json = json_decode($context->getValue(), $assoc);

        return $convert ? $json : null !== $json;
    }

    public static function _rule_lt(Context $context, string $field): bool
    {
        return $context->checkOther($field) && $context->getSize() < $context->getSize($field);
    }

    public static function _rule_lte(Context $context, string $field): bool
    {
        return $context->checkOther($field) && $context->getSize() <= $context->getSize($field);
    }

    public static function _rule_match(Context $context, string $pattern): bool
    {
        return (bool) preg_match($pattern, $context->getValue());
    }

    public static function _rule_max(Context $context, $length): bool
    {
        return $context->getSize() <= $length;
    }

    public static function _rule_min(Context $context, $length): bool
    {
        return $context->getSize() >= $length;
    }

    public static function _rule_not_in(Context $context, ...$elements): bool
    {
        return !in_array($context->getValue(), $elements);
    }

    public static function _rule_not_match(Context $context, string $pattern): bool
    {
        return !preg_match($pattern, $context->getValue());
    }

    public static function _rule_numeric(Context $context): bool
    {
        if (is_numeric($context->getValue())) {
            $context->setValue(0 + $context->getValue());
        }

        return $context->isNumeric();
    }

    public static function _rule_optional(Context $context, bool $exclude = null): bool
    {
        $context->setSkip(in_array($context->getValue(), array('', null), true));
        $context->setExcluded($exclude ?? $context->isExcluded());

        return true;
    }

    public static function _rule_required(Context $context): bool
    {
        return !in_array($context->getValue(), array('', null), true);
    }

    public static function _rule_required_if(Context $context, $field, $value = null): bool
    {
        return (($field instanceof \Closure && $field($context)) || (is_string($field) && $context->getOther($field) === $value)) && static::_rule_required($context);
    }

    public static function _rule_required_unless(Context $context, $field, $value = null): bool
    {
        return static::_rule_required($context) || (($field instanceof \Closure && $field($context)) || (is_string($field) && $context->getOther($field) === $value));
    }

    public static function _rule_same(Context $context, $value, bool $strict = true): bool
    {
        return ($strict && $context->getValue() === $value) || $context->getValue() == $value;
    }

    public static function _rule_size(Context $context, $length): bool
    {
        return $context->getSize() === $length;
    }

    public static function _rule_starts_with(Context $context, string ...$prefixes): bool
    {
        return array_reduce($prefixes, static function (bool $isTrue, string $prefix) use ($context) {
            return $isTrue || substr($context->getValue(), 0, strlen($prefix)) === $prefix;
        }, false);
    }

    public static function _rule_string(Context $context): bool
    {
        return $context->isString();
    }

    public static function _rule_url(Context $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_URL);
    }

    public static function _rule_trim(Context $context, string $mask = null)
    {
        return trim($context->getValue(), $mask ?? " \t\n\r\0\x0B");
    }

    public static function _rule_rtrim(Context $context, string $mask = null)
    {
        return rtrim($context->getValue(), $mask ?? " \t\n\r\0\x0B");
    }

    public static function _rule_ltrim(Context $context, string $mask = null, string $mode = null)
    {
        return ltrim($context->getValue(), $mask ?? " \t\n\r\0\x0B");
    }
}
