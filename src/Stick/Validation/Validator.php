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

use Fal\Stick\Fw;

/**
 * Data validator.
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
    private $rules = array();

    /**
     * @var array
     */
    private $rulesCache = array();

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw->prepend('LOCALES', __DIR__.'/dict/;');
    }

    /**
     * Add rule.
     *
     * @param RuleInterface ...$rules
     *
     * @return Validator
     */
    public function add(RuleInterface ...$rules): Validator
    {
        array_push($this->rules, ...$rules);

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
    public function validate(array $data, array $rules, array $messages = null): Result
    {
        $result = new Result(new Context($data));

        foreach ($rules as $field => $expression) {
            $result->context->setField($field);

            foreach (RuleParser::parse($expression) as $rule => $arguments) {
                $initial = $result->context->getValue();

                // special rule
                if ('optional' === $rule) {
                    if (null === $initial || '' === $initial) {
                        break;
                    }

                    $result->context->addValidated($field, $initial);
                    continue;
                }

                $value = $this->findRule($rule)->validate($rule, $result->context->setArguments($arguments));

                // validation fail?
                if (false === $value) {
                    if ($messages && isset($messages[$field.'.'.$rule])) {
                        $result->addError($field, $messages[$field.'.'.$rule]);
                    } else {
                        $result->addError($field, $this->message($rule, $result->context));
                    }

                    break;
                }

                $result->context->addValidated($field, true === $value ? $initial : $value);
            }
        }

        return $result;
    }

    /**
     * Find validator rule.
     *
     * @param string $name
     *
     * @return RuleInterface
     *
     * @throws DomainException If no validator supports the rule
     */
    private function findRule($name): RuleInterface
    {
        if (isset($this->rulesCache[$name])) {
            return $this->rules[$this->rulesCache[$name]];
        }

        foreach ($this->rules as $key => $rule) {
            if ($rule->has($name)) {
                $this->rulesCache[$name] = $key;

                return $rule;
            }
        }

        throw new \DomainException(sprintf('Validation rule not exists: %s.', $name));
    }

    /**
     * Get message for rule.
     *
     * @param string  $rule
     * @param Context $context
     *
     * @return string
     */
    private function message(string $rule, Context $context): string
    {
        $params = array();
        $data = array(
            'rule' => $rule,
            'field' => $context->getField(),
            'value' => $context->getValue(),
        ) + $context->getArguments();

        foreach ($data as $key => $value) {
            $params['%'.$key.'%'] = is_array($value) ? implode(',', $value) : (string) $value;
        }

        return $this->fw->transAlt(array(
            'validation.'.strtolower($rule),
            'validation.default',
            'This value is not valid',
        ), $params);
    }
}
