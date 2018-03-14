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
    protected $customs = [];

    /** @var array */
    protected $rules = [];

    /** @var array Rule message */
    protected $messages = [
        'required' => 'This value should not be blank.',
        'lenmin' => 'This value is too short. It should have {{ arg1 }} characters or more.',
        'lenmax' => 'This value is too long. It should have {{ arg1 }} characters or less.',
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
     * Min length
     *
     * @param  string $val
     * @param  int    $min
     *
     * @return string
     */
    public function lenMin(string $val, int $min)
    {
        return strlen($val) >= $min;
    }

    /**
     * Max length
     *
     * @param  string $val
     * @param  int    $max
     *
     * @return string
     */
    public function lenMax(string $val, int $max)
    {
        return strlen($val) <= $max;
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
     * @return string|false
     */
    public function card($id)
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

        return false;
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
            throw new \LogicException('Run validate method first');
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
     * Do validate
     *
     * @param  array  $data
     * @param  array  $rules
     *
     * @return Audit
     */
    public function validate(array $data = null, array $rules = null): Audit
    {
        $this->errors = [];
        $this->processed = false;

        $use = $data ?? $this->data;
        $useRules = $rules ?? $this->rules;
        $validated = [];

        foreach ($useRules as $id => $def) {
            $xrules = explode('|', $def);

            foreach ($xrules as $rule) {
                $audit = [
                    'id' => $id,
                    'original' => $use,
                    'validated' => $result,
                ];
                $value = $validated[$id] ?? $use[$id] ?? null;
                $result = $this->execute($rule, $value, $audit, $prule, $args);

                if ($result === false) {
                    // validation fail
                    $this->addError($prule, $id, $args);

                    break;
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
     * @param string $rule
     * @param string $id
     * @param array  $args
     */
    protected function addError(string $rule, string $id, array $args): void
    {
        $message = $this->messages[strtolower($rule)] ?? null;

        if ($message) {
            $keys = quoteall(array_keys($args), ['{arg','}']);
            $keys[] = '{key}';
            $args[] = $id;

            $message  = str_replace($keys, $args, $message);
        } else {
            $message = 'Invalid data';
        }

        $this->errors[$id][] = $message;
    }

    /**
     * Execute rule
     *
     * @param  string     $rule
     * @param  mixed     $val
     * @param  array      $audit
     * @param  array|null &$args
     *
     * @return mixed
     */
    protected function execute(
        string $rule,
        $val,
        array $audit,
        string &$prule = null,
        array &$args = null
    ) {
        if (!preg_match('/^(\w+)(?:\[([^\]]+)\])?$/', $rule, $match)) {
            throw new \LogicException("Invalid rule declaration: {$rule}");
        }

        $prule = $match[1];
        $args = isset($match[2]) ? casts(split($match[2])) : [];
        $cust = true;
        $func = true;

        if (isset($this->customs[$prule])) {
            $ref = new \ReflectionFunction($this->customs[$prule]);
        } elseif (method_exists($this, $prule)) {
            $ref = new \ReflectionMethod($this, $prule);
            $func = false;
        } elseif (is_callable($prule)) {
            $ref = new \ReflectionFunction($prule);
            $cust = false;
        } else {
            throw new \LogicException("Rule not found: {$prule}");
        }

        // First parameter is value
        array_unshift($args, $val);

        $argCount = count($args);
        $reqCount = max(1, $ref->getNumberOfRequiredParameters());

        if ($argCount < $reqCount) {
            $error = 'Validator ' . $prule . ' expect at least ' .
                     $reqCount . ' parameters, ' . $argCount . ' given';
            throw new \ArgumentCountError($error);
        }

        $audit_key = -1;

        if ($cust) {
            foreach ($ref->getParameters() as $key => $param) {
                if ($param->name === '_audit') {
                    // special parameter name
                    $args[$key] = $audit;
                    $audit_key = $key;
                } elseif (!isset($args[$key]) && $param->isDefaultValueAvailable()) {
                    $args[$key] = $param->getDefaultValue();
                }
            }
        }

        $out = $func ? $ref->invoke(...$args) : $ref->invoke($this, ...$args);

        if ($audit_key > -1) {
            unset($args[$audit_key]);
        }

        return $out;
    }
}
