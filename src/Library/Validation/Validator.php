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

namespace Fal\Stick\Library\Validation;

use Fal\Stick\Fw;

/**
 * Validator wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Validator
{
    /**
     * @var Fw
     */
    private $fw;

    /**
     * @var array
     */
    private $validators = array();

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw->unshift('LOCALES', __DIR__.'/dict/');
    }

    /**
     * Returns parsed string expression.
     *
     * Example:
     *
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string $expr
     *
     * @return array
     */
    public function parseExpr(string $expr): array
    {
        $len = strlen($expr);
        $res = array();
        $tmp = '';
        $process = false;
        $args = array();
        $quote = null;
        $astate = 0;
        $jstate = 0;

        for ($ptr = 0; $ptr < $len; ++$ptr) {
            $char = $expr[$ptr];
            $prev = $expr[$ptr - 1] ?? null;

            if (('"' === $char || "'" === $char) && '\\' !== $prev) {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if (':' === $char && 0 === $jstate) {
                    // next chars is arg
                    $args[] = $this->fw->cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = $this->fw->cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = $this->fw->cast($tmp);
                        $tmp = '';
                    }
                } elseif ('[' === $char) {
                    $astate = 1;
                    $tmp .= $char;
                } elseif (']' === $char && 1 === $astate && 0 === $jstate) {
                    $astate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } elseif ('{' === $char) {
                    $jstate = 1;
                    $tmp .= $char;
                } elseif ('}' === $char && 1 === $jstate && 0 === $astate) {
                    $jstate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } else {
                    $tmp .= $char;
                    $astate += '[' === $char ? 1 : (']' === $char ? -1 : 0);
                    $jstate += '{' === $char ? 1 : ('}' === $char ? -1 : 0);
                }
            } else {
                $tmp .= $char;
            }

            if (!$process && $ptr === $len - 1) {
                $process = true;
                if ('' !== $tmp) {
                    $args[] = $this->fw->cast($tmp);
                    $tmp = '';
                }
            }

            if ($process) {
                if ($args) {
                    $res[array_shift($args)] = $args;
                    $args = array();
                }
                $process = false;
            }
        }

        return $res;
    }

    /**
     * Add validator.
     *
     * @param ValidatorInterface ...$validators
     *
     * @return Validator
     */
    public function add(ValidatorInterface ...$validators): Validator
    {
        array_push($this->validators, ...$validators);

        return $this;
    }

    /**
     * Returns validation result.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return array
     */
    public function validate(array $data, array $rules, array $messages = null): array
    {
        $validated = array();
        $errors = array();

        foreach ($rules as $field => $fieldRules) {
            foreach ($this->parseExpr($fieldRules) as $rule => $args) {
                $mData = $data;
                $value = array_key_exists($field, $validated) ? $validated[$field] : $this->fw->ref($field, false, $mData);
                $validator = $this->findValidator($rule);
                $result = $validator->validate($rule, $value, $args, $field, $validated, $data);

                if (false === $result) {
                    // validation fail
                    $messageKey = $field.'.'.$rule;
                    $custom = $messages[$messageKey] ?? null;
                    $errors[$field][] = $this->message($rule, $value, $args, $field, $custom);

                    break;
                } else {
                    $ref = &$this->fw->ref($field, true, $validated);
                    $ref = true === $result ? $value : $result;
                }
            }
        }

        return array(
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validated,
        );
    }

    /**
     * Find validator for rule.
     *
     * @param string $rule
     *
     * @return ValidatorInterface
     *
     * @throws DomainException If no validator supports the rule
     */
    private function findValidator($rule): ValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator->has($rule)) {
                return $validator;
            }
        }

        throw new \DomainException('Rule "'.$rule.'" not exists.');
    }

    /**
     * Get message for rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $args
     * @param string $field
     * @param string $message
     *
     * @return string
     */
    private function message(string $rule, $value = null, array $args = null, string $field = null, string $message = null): string
    {
        if ($message && false === strpos($message, '{')) {
            return $message;
        }

        $key = 'validation.'.strtolower($rule);
        $alt = 'validation.default';
        $fallback = 'This value is not valid.';
        $data = array();

        foreach (compact('field', 'rule', 'value') + $args as $k => $v) {
            $data['{'.$k.'}'] = is_array($v) ? implode(',', $v) : (string) $v;
        }

        return $this->fw->transAlt($key, $data, $fallback, $alt);
    }
}
