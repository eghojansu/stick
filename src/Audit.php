<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

class Audit
{
    /** User agents */
    const
        UA_Mobile  = 'android|blackberry|phone|ipod|palm|windows\s+ce',
        UA_Desktop = 'bsd|linux|os\s+[x9]|solaris|windows',
        UA_Bot     = 'bot|crawl|slurp|spider';

    /** @var array */
    public static $specialMap = [
        ',' => ':ccomma:',
        '|' => ':cvbar:',
    ];

    /** @var array */
    protected $customs = [];

    /** @var array */
    protected $rules = [];

    /** @var array Rule message */
    protected $messages = [
        'required' => 'This value should not be blank.',
        'type' => 'This value should be of type {1}.',
        'min' => 'This value should be {1} or more.',
        'max' => 'This value should be {1} or less.',
        'lt' => 'This value should be less than {1}.',
        'gt' => 'This value should be greater than {1}.',
        'lte' => 'This value should be less than or equal to {1}.',
        'gte' => 'This value should be greater than or equal to {1}.',
        'equal' => 'This value should be equal to {1}.',
        'notequal' => 'This value should not be equal to {1}.',
        'identical' => 'This value should be identical to {2} {1}.',
        'notidentical' => 'This value should not be identical to {2} {1}.',
        'lenmin' => 'This value is too short. It should have {1} characters or more.',
        'lenmax' => 'This value is too long. It should have {1} characters or less.',
        'countmin' => 'This collection should contain {1} elements or more.',
        'countmax' => 'This collection should contain {1} elements or less.',
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

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $validated = [];

    /** @var array */
    protected $errors = [];

    /** @var bool */
    protected $processed = false;

    /**
     * Class constructor
     *
     * @param array $customs
     * @param array $messages
     */
    public function __construct(array $customs = [], array $messages = [])
    {
        foreach ($customs as $rule => $callback) {
            $this->setRule($rule, $callback);
        }

        $this->setMessages($messages);
    }

    /**
     * Required rule
     *
     * @param  mixed $val
     *
     * @return bool
     */
    public function required($val): bool
    {
        return isset($val) && $val !== '';
    }

    /**
     * Check variabel type
     *
     * @param  mixed $val
     * @param  string $type
     *
     * @return bool
     */
    public function type($val, string $type): bool
    {
        return gettype($val) === $type;
    }

    /**
     * Equal to
     *
     * @param  mixed $val
     * @param  mixed $compared
     *
     * @return bool
     */
    public function equal($val, $compared): bool
    {
        return $val == $compared;
    }

    /**
     * Not equal to
     *
     * @param  mixed $val
     * @param  mixed $compared
     *
     * @return bool
     */
    public function notEqual($val, $compared): bool
    {
        return $val != $compared;
    }

    /**
     * Identical to
     *
     * @param  mixed  $val
     * @param  mixed  $compared
     * @param  string $type
     *
     * @return bool
     */
    public function identical($val, $compared, string $type = 'string'): bool
    {
        return $val === $compared;
    }

    /**
     * Not identical to
     *
     * @param  mixed  $val
     * @param  mixed  $compared
     * @param  string $type
     *
     * @return bool
     */
    public function notIdentical($val, $compared, string $type = 'string'): bool
    {
        return $val !== $compared;
    }

    /**
     * Less than
     *
     * @param  mixed $val
     * @param  mixed $min
     *
     * @return bool
     */
    public function lt($val, $min): bool
    {
        return $val < $min;
    }

    /**
     * Greater than
     *
     * @param  mixed $val
     * @param  mixed $max
     *
     * @return bool
     */
    public function gt($val, $max): bool
    {
        return $val > $max;
    }

    /**
     * Less than or equal
     *
     * @param  mixed $val
     * @param  mixed $min
     *
     * @return bool
     */
    public function lte($val, $min): bool
    {
        return $val <= $min;
    }

    /**
     * Greater than or equal
     *
     * @param  mixed $val
     * @param  mixed $max
     *
     * @return bool
     */
    public function gte($val, $max): bool
    {
        return $val >= $max;
    }

    /**
     * Number min
     *
     * @param  mixed $val
     * @param  mixed $min
     *
     * @return bool
     */
    public function min($val, $min): bool
    {
        return $val >= $min;
    }

    /**
     * Number max
     *
     * @param  mixed $val
     * @param  mixed $max
     *
     * @return bool
     */
    public function max($val, $max): bool
    {
        return $val <= $max;
    }

    /**
     * Min length
     *
     * @param  string $val
     * @param  int    $min
     *
     * @return bool
     */
    public function lenMin(string $val, int $min): bool
    {
        return strlen($val) >= $min;
    }

    /**
     * Max length
     *
     * @param  string $val
     * @param  int    $max
     *
     * @return bool
     */
    public function lenMax(string $val, int $max): bool
    {
        return strlen($val) <= $max;
    }

    /**
     * Count min
     *
     * @param  array $val
     * @param  int   $min
     *
     * @return bool
     */
    public function countMin(array $val, int $min): bool
    {
        return count($val) >= $min;
    }

    /**
     * Count max
     *
     * @param  array $val
     * @param  int   $max
     *
     * @return bool
     */
    public function countMax(array $val, int $max): bool
    {
        return count($val) <= $max;
    }

    /**
     * Try to convert date to format
     *
     * @param  mixed  $val
     * @param  string $format
     *
     * @return string
     */
    public function cdate($val, string $format = 'Y-m-d'): string
    {
        try {
            $date = (new \DateTime($val))->format($format);
        } catch (\Exception $e) {
            $date = (string) $val;
        }

        return $date;
    }

    /**
     * Check date in format YYYY-MM-DD
     *
     * @param  mixed $val
     *
     * @return bool
     */
    public function date($val): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $val);
    }

    /**
     * Check date in format YYYY-MM-DD HH:MM:SS
     *
     * @param  mixed $val
     *
     * @return bool
     */
    public function datetime($val): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $val);
    }

    /**
     * Perform regex
     *
     * @param  mixed $val
     * @param  string $pattern
     *
     * @return bool
     */
    public function regex($val, string $pattern): bool
    {
        return (bool) preg_match($pattern, $val);
    }

    /**
     * Check if val in choices
     *
     * @param  mixed $val
     * @param  array $choices
     *
     * @return bool
     */
    public function choice($val, array $choices): bool
    {
        return in_array($val, $choices);
    }

    /**
     * Check if multiple val in choices
     *
     * @param  mixed $val
     * @param  array $choices
     *
     * @return bool
     */
    public function choices($val, array $choices): bool
    {
        $vals = (array) $val;
        $intersection = array_intersect($vals, $choices);

        return count($intersection) === count($vals);
    }

    /**
     * Return true if string is a valid URL
     *
     * @param  string $str
     *
     * @return bool
     */
    public function url($str): bool
    {
        return is_string(filter_var($str, FILTER_VALIDATE_URL));
    }

    /**
     * Return true if string is a valid e-mail address;
     * Check DNS MX records if specified
     *
     * @param  string  $str
     * @param  boolean $mx
     *
     * @return bool
     */
    public function email($str, $mx = true): bool
    {
        $hosts = [];

        return (
            is_string(filter_var($str, FILTER_VALIDATE_EMAIL))
            && (!$mx || getmxrr(substr($str, strrpos($str, '@')+1), $hosts))
        );
    }

    /**
     * Return true if string is a valid IPV4 address
     *
     * @param  string $addr
     *
     * @return bool
     */
    public function ipv4($addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Return true if string is a valid IPV6 address
     *
     * @param  string $addr
     *
     * @return bool
     */
    public function ipv6($addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Return true if IP address is within private range
     *
     * @param  string $addr
     *
     * @return bool
     */
    public function isPrivate($addr): bool
    {
        return !(bool) filter_var(
            $addr,
            FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6|FILTER_FLAG_NO_PRIV_RANGE
        );
    }

    /**
     * Return true if IP address is within reserved range
     *
     * @param  string $addr
     *
     * @return bool
     */
    public function isReserved($addr): bool
    {
        return !(bool) filter_var(
            $addr,
            FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6|FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Return true if IP address is neither private nor reserved
     *
     * @param  string $addr
     *
     * @return bool
     */
    public function isPublic($addr): bool
    {
        return (bool) filter_var(
            $addr,
            FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Return true if user agent is a desktop browser
     *
     * @param  string $agent
     *
     * @return bool
     */
    public function isDesktop($agent): bool
    {
        return (
            (bool) preg_match('/(' . self::UA_Desktop . ')/i', $agent)
            && !$this->ismobile($agent)
        );
    }

    /**
     * Return true if user agent is a mobile device
     *
     * @param  string $agent
     *
     * @return bool
     */
    public function isMobile($agent): bool
    {
        return (bool) preg_match('/(' . self::UA_Mobile . ')/i', $agent);
    }

    /**
     * Return true if user agent is a Web bot
     *
     * @param  string $agent
     *
     * @return bool
     */
    public function isBot($agent): bool
    {
        return (bool) preg_match('/(' . self::UA_Bot . ')/i', $agent);
    }

    /**
     * Return true if specified ID has a valid (Luhn) Mod-10 check digit
     *
     * @param  string $id
     *
     * @return bool
     */
    public function mod10($id): bool
    {
        if (!ctype_digit($id)) {
            return false;
        }

        $id  = strrev($id);
        $sum = 0;
        for ($i = 0, $l = strlen($id); $i<$l; $i++) {
            $sum += $id[$i]+$i%2*(($id[$i]>4)*-4+$id[$i]%5);
        }

        return !($sum%10);
    }

    /**
     * Return credit card type if number is valid
     *
     * @param  string $id
     *
     * @return string
     */
    public function card($id): string
    {
        $id = preg_replace('/[^\d]/', '', $id);

        if ($this->mod10($id)) {
            if (preg_match('/^3[47][0-9]{13}$/', $id)) {
                return 'American Express';
            }

            if (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $id)) {
                return 'Diners Club';
            }

            if (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $id)) {
                return 'Discover';
            }

            if (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $id)) {
                return 'JCB';
            }

            if (preg_match('/^5[1-5][0-9]{14}$|^(222[1-9]|2[3-6]\d{2}|27[0-1]\d|2720)\d{12}$/', $id)) {
                return 'MasterCard';
            }

            if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $id)) {
                return 'Visa';
            }
        }

        return '';
    }

    /**
     * Set rule messages
     *
     * @param array $messages
     *
     * @return Audit
     */
    public function setMessages(array $messages): Audit
    {
        foreach ($messages as $rule => $message) {
            $this->setMessage($rule, $message);
        }

        return $this;
    }

    /**
     * Set rule message
     *
     * @param string $rule
     * @param string $message
     *
     * @return Audit
     */
    public function setMessage(string $rule, string $message): Audit
    {
        $this->messages[strtolower($rule)] = $message;

        return $this;
    }

    /**
     * Get rule message
     *
     * @param  string $rule
     *
     * @return string
     */
    public function getMessage(string $rule): string
    {
        return $this->messages[$rule] ?? '';
    }

    /**
     * Get rule messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Add custom rule
     *
     * @param string   $rule
     * @param callable $callback
     *
     * @return Audit
     */
    public function setRule(string $rule, callable $callback): Audit
    {
        $this->customs[$rule] = $callback;

        return $this;
    }

    /**
     * Get defined rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Define rules to be used in validation
     *
     * @param array $rules
     *
     * @return Audit
     */
    public function setRules(array $rules): Audit
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data to be validated
     *
     * @param array $data
     * @return Audit
     */
    public function setData(array $data): Audit
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get validation status
     *
     * @return bool
     *
     * @throws LogicException
     */
    public function isSuccess(): bool
    {
        if (!$this->processed) {
            throw new \LogicException(
                'No validation has been processed, run validate method first'
            );
        }

        return count($this->errors) === 0;
    }

    /**
     * isSuccess complement
     *
     * @return bool
     *
     * @throws LogicException
     */
    public function isFailed(): bool
    {
        return !$this->isSuccess();
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated
     *
     * @return array
     */
    public function getValidated(): array
    {
        return $this->validated;
    }

    /**
     * Compact array values to string
     *
     * @param  array  $values
     *
     * @return string
     */
    public function compact(array $values): string
    {
        return implode(self::$specialMap[','], $values);
    }

    /**
     * Convert to special map
     *
     * @param  string $val
     *
     * @return string
     */
    public function encode(string $val): string
    {
        return strtr($val, self::$specialMap);
    }

    /**
     * Inverse special map
     *
     * @param  string $val
     *
     * @return string
     */
    public function decode(string $val): string
    {
        return strtr($val, array_flip(self::$specialMap));
    }

    /**
     * Do validate
     *
     * @param  array  $data
     * @param  array  $rules
     *
     * @return Audit
     */
    public function validate(array $data = null, array $rules = null): Audit
    {
        static $fieldRule = ['_'=>' ','.'=>' - '];

        $this->errors = [];
        $this->processed = false;

        $use = $data ?? $this->data;
        $useRules = $rules ?? $this->rules;
        $validated = [];

        foreach ($useRules as $id => $def) {
            $xid = explode('|', $id);
            $xrules = explode('|', $def);
            $id = $xid[0];

            foreach ($xrules as $rule) {
                $audit = [
                    'field' => $xid[1] ?? ucwords(strtr(snakecase($id), $fieldRule)),
                    'args' => [],
                    'rule' => null,
                ];
                $value = $validated[$id] ?? $this->ref($id, $use);
                $result = $this->execute($rule, $value, $audit);

                if ($result === false) {
                    // validation fail
                    $this->addError($audit);
                } elseif ($result === true) {
                    $validated[$id] = $value;
                } else {
                    $validated[$id] = $result;
                }
            }
        }

        $this->validated = $validated;
        $this->processed = true;

        return $this;
    }

    /**
     * Add error message
     *
     * @param array $audit From validate
     */
    protected function addError(array $audit): void
    {
        $message = $this->messages[strtolower($audit['rule'])] ?? 'This value is not valid.';
        $field = $audit['field'];

        if (strpos($message, '{') !== false) {
            $keys = ['{key}'];
            $vals = [$field];

            foreach ($audit['args'] as $key => $value) {
                $keys[] = '{' . $key . '}';
                $vals[] = is_array($value) ? stringify($value) : $value;
            }

            $message  = str_replace($keys, $vals, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Execute rule
     *
     * @param  string $rule
     * @param  mixed  $val
     * @param  array  &$audit
     *
     * @return mixed
     *
     * @throws LogicException
     * @throws DomainException
     * @throws ArgumentCountError
     */
    protected function execute(string $rule, $val, array &$audit)
    {
        if (!preg_match('/^(\w+)(?:\[([^\]]+)\])?$/', $rule, $match)) {
            throw new \LogicException(
                'Rule declaration is invalid, given "' . $rule . '"'
            );
        }

        $prule = $match[1];
        $args = [];
        $func = true;

        if (isset($match[2]) && $match[2]) {
            foreach (explode(',', $match[2]) as $key => $value) {
                $dvalue = $this->decode($value);
                $args[] = strpos($dvalue, ',') === false ?
                          cast($dvalue) : casts(explode(',', $dvalue));
            }
        }

        if (isset($this->customs[$prule])) {
            $ref = new \ReflectionFunction($this->customs[$prule]);
        } elseif (method_exists($this, $prule)) {
            $ref = new \ReflectionMethod($this, $prule);
            $func = false;
        } elseif (is_callable($prule)) {
            $ref = new \ReflectionFunction($prule);
        } else {
            throw new \DomainException(
                'Rule "' . $prule . '" does not exists'
            );
        }

        // First parameter is value
        array_unshift($args, $val);

        $argCount = count($args);
        $reqCount = max(1, $ref->getNumberOfRequiredParameters());

        if ($argCount < $reqCount) {
            throw new \ArgumentCountError(
                'Validator ' . $prule . ' expect at least ' . $reqCount . ' parameters, ' .
                $argCount . ' given'
            );
        }

        foreach ($ref->getParameters() as $key => $param) {
            if (!isset($args[$key]) && $param->isDefaultValueAvailable()) {
                $args[$key] = $param->getDefaultValue();
            }
        }

        $audit['rule'] = $prule;
        $audit['args'] = $args;

        return $func ? $ref->invoke(...$args) : $ref->invoke($this, ...$args);
    }

    /**
     * Get hive ref
     *
     * @param  string $key
     * @param  array  $var
     *
     * @return mixed
     */
    protected function ref(string $key, array $var)
    {
        $null = null;
        $parts = explode('.', $key);

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = [];
            }
            if (array_key_exists($part, $var)) {
                $var =& $var[$part];
            } else {
                $var =& $null;
                break;
            }
        }

        return $var;
    }
}
