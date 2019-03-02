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

namespace Fal\Stick\Validation;

use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Translation\TranslatorInterface;
use Fal\Stick\Util;

/**
 * Validator wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Validator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $rules = array();

    /**
     * Class constructor.
     *
     * @param TranslatorInterface $translator
     * @param ContainerInterface  $container
     * @param array               $rules
     */
    public function __construct(TranslatorInterface $translator, ContainerInterface $container = null, array $rules = null)
    {
        $this->translator = $translator->addLocale(__DIR__.'/dict/');

        foreach ($rules ?? array() as $rule) {
            if (is_string($rule) && $container) {
                $rule = $container->get($rule);
            }

            $this->addRule($rule);
        }
    }

    /**
     * Returns parsed string expression.
     *
     * Example:
     *
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string|null $expr
     *
     * @return array
     */
    public function parseExpr(string $expr = null): array
    {
        if ($expr) {
            $expr = trim($expr);
        }

        if (!$expr) {
            return array();
        }

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
                    $args[] = Util::cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = Util::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = Util::cast($tmp);
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
                    $args[] = Util::cast($tmp);
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
     * Add rule.
     *
     * @param RuleInterface $rule
     *
     * @return Validator
     */
    public function addRule(RuleInterface $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Validate data and returns validation result.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return Result
     */
    public function validate(array $data, array $rules, array $messages = null): Result
    {
        $context = new Result($data);

        foreach ($rules as $field => $fieldRules) {
            foreach ($this->parseExpr($fieldRules) as $rule => $arguments) {
                $value = $context->setCurrent($rule, $field)->value();
                $result = $this->findRule($rule)->context($context)->validate($rule, $value, $arguments);

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
     * @return RuleInterface
     *
     * @throws DomainException If no validator supports the rule
     */
    protected function findRule(string $ruleName): RuleInterface
    {
        foreach ($this->rules as $rule) {
            if ($rule->has($ruleName)) {
                return $rule;
            }
        }

        throw new \DomainException(sprintf('Rule "%s" not exists.', $ruleName));
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
    protected function message(string $rule, $value = null, array $arguments, string $field): string
    {
        $data = array();

        foreach (compact('field', 'rule', 'value') + $arguments as $k => $v) {
            $data['{'.$k.'}'] = is_array($v) ? implode(',', $v) : (string) $v;
        }

        return $this->translator->transAlt(array(
            'validation.'.strtolower($rule),
            'validation.default',
            'This value is not valid',
        ), $data);
    }
}
