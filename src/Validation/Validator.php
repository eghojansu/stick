<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Validation;

use Fal\Stick\Core;

/**
 * Validator wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Validator
{
    /**
     * @var Core
     */
    protected $core;

    /**
     * @var array
     */
    protected $validators = [];

    /**
     * Class constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core, array $validators = null)
    {
        $this->core = $core->prepend('LANGUAGE_LOCALES', __DIR__.'/dict/;');

        foreach ((array) $validators as $validator) {
            $this->add($core->service($validator));
        }
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
    public function parseExpr($expr)
    {
        $len = strlen($expr);
        $res = [];
        $tmp = '';
        $process = false;
        $args = [];
        $quote = null;
        $astate = 0;
        $jstate = 0;

        for ($ptr = 0; $ptr < $len; $ptr++) {
            $char = $expr[$ptr];
            $prev = isset($expr[$ptr - 1]) ? $expr[$ptr - 1] : null;

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
                    $args[] = $this->core->cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = $this->core->cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = $this->core->cast($tmp);
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
                    $args[] = $this->core->cast($tmp);
                    $tmp = '';
                }
            }

            if ($process) {
                if ($args) {
                    $res[array_shift($args)] = $args;
                    $args = [];
                }
                $process = false;
            }
        }

        return $res;
    }

    /**
     * Add validator.
     *
     * @param ValidatorInterface $validator
     *
     * @return Validator
     */
    public function add(ValidatorInterface $validator)
    {
        $this->validators[] = $validator;

        return $this;
    }

    /**
     * Returns validation result.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return Result
     */
    public function validate(array $data, array $rules, array $messages = null)
    {
        $context = new Result($data);

        foreach ($rules as $field => $fieldRules) {
            foreach ($this->parseExpr($fieldRules) as $rule => $arguments) {
                $value = $context->setCurrent($rule, $field)->value();
                $result = $this->findValidator($rule)->context($context)->validate($rule, $value, $arguments);

                if (false === $result) {
                    // validation fail
                    $key = $field.'.'.$rule;
                    $message = isset($messages[$key]) ? $messages[$key] : $this->message($rule, $value, $arguments, $field);
                    $context->addError($field, $message);

                    break;
                } elseif (true === $result) {
                    $context->addData($field, $value);
                } else {
                    $context->addData($field, $result);
                }
            }
        }

        return $context;
    }

    /**
     * Find validator for rule.
     *
     * @param string $rule
     *
     * @throws DomainException If no validator supports the rule
     *
     * @return ValidatorInterface
     */
    protected function findValidator($rule)
    {
        foreach ($this->validators as $validator) {
            if ($validator->has($rule)) {
                return $validator;
            }
        }

        throw new \DomainException(sprintf('Rule "%s" not exists', $rule));
    }

    /**
     * Get message for rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $arguments
     * @param string $field
     *
     * @return string
     */
    protected function message($rule, $value, array $arguments, $field)
    {
        $data = [];

        foreach (compact('field', 'rule', 'value') + $arguments as $k => $v) {
            $data['{'.$k.'}'] = is_array($v) ? implode(',', $v) : (string) $v;
        }

        return $this->core->transAlt([
            'validation.'.strtolower($rule),
            'validation.default',
            'This value is not valid',
        ], $data);
    }
}
