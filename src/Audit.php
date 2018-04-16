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
        'equalfield' => 'This value should be equal to value of {1}.',
        'notequalfield' => 'This value should not be equal to value of {1}.',
        'equal' => 'This value should be equal to {1}.',
        'notequal' => 'This value should not be equal to {1}.',
        'identical' => 'This value should be identical to {2} {1}.',
        'notidentical' => 'This value should not be identical to {2} {1}.',
        'len' => 'This value is not valid. It should have exactly {1} characters.',
        'lenmin' => 'This value is too short. It should have {1} characters or more.',
        'lenmax' => 'This value is too long. It should have {1} characters or less.',
        'count' => 'This collection should contain exactly {1} elements.',
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
        'exists' => null,
        'unique' => 'This value is already used.',
    ];

    /** @var array */
    protected $rules = [];

    /** @var array */
    protected $services = [];

    /**
     * Add service
     *
     * @param object $instance
     * @param string $id
     *
     * @return  Audit
     */
    public function addService($instance, string $id = null): Audit
    {
        $useId = $id ?? get_class($instance);

        $this->services[$useId] = $instance;

        return $this;
    }

    /**
     * Check record existance
     *
     * Require service with id "db" (instance of DatabaseInterface)
     *
     * @param  mixed  $val
     * @param  string $table
     * @param  string $column
     *
     * @return bool
     */
    public function exists($val, string $table, string $column): bool
    {
        return (bool) $this->reqService('db')->selectOne($table, [$column=>$val]);
    }

    /**
     * Check record unique
     *
     * @param  mixed       $val
     * @param  string      $table
     * @param  string      $column
     * @param  string|null $fid
     * @param  mixe        $id
     *
     * @return bool
     */
    public function unique($val, string $table, string $column, string $fid = null, $id = null): bool
    {
        $res = $this->reqService('db')->selectOne($table, [$column=>$val]);

        return !$res || ($fid && (!isset($res[$fid]) || $res[$fid] == $id));
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
     * @param  mixed  $val
     * @param  string $type
     *
     * @return bool
     */
    public function type($val, string $type): bool
    {
        return gettype($val) === $type;
    }

    /**
     * Equal to other field
     *
     * @param  mixed $val
     * @param  mixed $compared
     * @param  array $_audit
     *
     * @return bool
     */
    public function equalField($val, $compared, array $_audit = []): bool
    {
        return $val === ($_audit['validated'][$compared] ?? $val . '-');
    }

    /**
     * Not equal to other field
     *
     * @param  mixed $val
     * @param  mixed $compared
     * @param  array $_audit
     *
     * @return bool
     */
    public function notEqualField($val, $compared, array $_audit = []): bool
    {
        return $val !== ($_audit['validated'][$compared] ?? $val);
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
     * Length
     *
     * @param  string $val
     * @param  int    $len
     *
     * @return bool
     */
    public function len($val, int $len): bool
    {
        return !$this->required($val) || strlen($val) === $len;
    }

    /**
     * Min length
     *
     * @param  string $val
     * @param  int    $min
     *
     * @return bool
     */
    public function lenMin($val, int $min): bool
    {
        return !$this->required($val) || strlen($val) >= $min;
    }

    /**
     * Max length
     *
     * @param  string $val
     * @param  int    $max
     *
     * @return bool
     */
    public function lenMax($val, int $max): bool
    {
        return !$this->required($val) || strlen($val) <= $max;
    }

    /**
     * Count
     *
     * @param  array $val
     * @param  int   $count
     *
     * @return bool
     */
    public function count(array $val, int $count): bool
    {
        return count($val) === $count;
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
        } catch (\Throwable $e) {
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
        $quote = $pattern[0];
        if (in_array($quote, ["'",'"']) && substr($pattern, -1) === $quote) {
            $pattern = substr($pattern, 1, -1);
        }

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
    public function email($str, $mx = false): bool
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
        return $this->messages[strtolower($rule)] ?? '';
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
    public function addRule(string $rule, callable $callback): Audit
    {
        $this->rules[$rule] = $callback;

        return $this;
    }

    /**
     * Do validate
     *
     * @param  array $data
     * @param  array $rules
     * @param  array $messages
     *
     * @return array
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validated = [];
        $error = [];

        foreach ($rules as $field => $frules) {
            $prules = parse_expr($frules);

            foreach ($prules as $rule => $args) {
                $value = array_key_exists($field, $validated) ?
                         $validated[$field] : $this->ref($field, $data);
                $audit = [
                    'validated' => $validated,
                    'field' => $field,
                ];
                array_unshift($args, $value);
                $result = $this->execute($rule, $args, $audit);

                if ($result === false) {
                    // validation fail
                    $error[$field][] = $this->errorMessage(
                        $field,
                        $rule,
                        $args,
                        $messages[$field . '.' . $rule] ?? null,
                        $error
                    );
                    break;
                } elseif ($result === true) {
                    $validated[$field] = $value;
                } else {
                    $validated[$field] = $result;
                }
            }
        }

        return [
            'success' => count($error) === 0,
            'error' => $error,
            'data' => $validated,
        ];
    }

    /**
     * Add error message
     *
     * @param string $field
     * @param string $rule
     * @param array  $args
     * @param string $message
     *
     * @return string
     */
    protected function errorMessage(
        string $field,
        string $rule,
        array $args,
        string $message = null
    ): string {
        $use = $message ?? $this->messages[strtolower($rule)] ?? 'This value is not valid.';

        if (strpos($use, '{') === false) {
            return $use;
        }

        $keys = ['{field}','{rule}'];
        $vals = [$field,$rule];

        foreach ($args as $key => $value) {
            $keys[] = '{' . $key . '}';
            $vals[] = is_array($value) ? stringify($value) : $value;
        }

        return str_replace($keys, $vals, $use);
    }

    /**
     * Execute rule
     *
     * @param  string $rule
     * @param  array  $args
     * @param  array  $audit
     *
     * @return mixed
     *
     * @throws DomainException
     * @throws ArgumentCountError
     */
    protected function execute(string $rule, array $args, array $audit)
    {
        $func = true;

        if (isset($this->rules[$rule])) {
            $ref = new \ReflectionFunction($this->rules[$rule]);
        } elseif (method_exists($this, $rule)) {
            $ref = new \ReflectionMethod($this, $rule);
            $func = false;
        } elseif (is_callable($rule)) {
            $ref = new \ReflectionFunction($rule);
        } else {
            throw new \DomainException('Rule "' . $rule . '" does not exists');
        }

        $argCount = count($args);
        $reqCount = max(1, $ref->getNumberOfRequiredParameters());

        if ($argCount < $reqCount) {
            throw new \ArgumentCountError(
                'Validator ' . $rule . ' expect at least ' . $reqCount . ' parameters, ' .
                $argCount . ' given'
            );
        }

        foreach ($ref->getParameters() as $key => $param) {
            if ($param->name === '_audit') {
                $args[$key] = $audit;
            } elseif (!isset($args[$key]) && $param->isDefaultValueAvailable()) {
                $args[$key] = $param->getDefaultValue();
            }
        }

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

    /**
     * Get required service
     *
     * @param  string $id
     *
     * @return object
     *
     * @throws LogicException If service not found
     */
    protected function reqService(string $id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        throw new \LogicException('Service "' . $id . '" is not registered');
    }
}
