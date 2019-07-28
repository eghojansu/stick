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
    /** @var Fw */
    private $fw;

    /** @var array Registered rules */
    private $rules = array();

    /** @var array Rule cache */
    private $cache = array();

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
     * @param array $rawData
     * @param array $rules
     * @param array $messages
     *
     * @return array
     */
    public function validate(array $rawData, array $rules, array $messages = null): array
    {
        $success = true;
        $errors = array();
        $data = array();

        foreach ($rules as $fieldName => $expression) {
            $field = new Field(
                $this->fw,
                $data,
                $rawData,
                $fieldName,
                $expression
            );

            foreach ($field->rules() as $rule => $arguments) {
                $value = $this->findRule($rule)->validate($rule, $arguments, $field);

                // validation fail?
                if (false === $value) {
                    if ($success) {
                        $success = false;
                    }

                    $errors[$fieldName] = $messages[$fieldName.'.'.$rule] ??
                        $this->message($rule, $arguments, $field);
                    $field->setSkip();
                }

                if ($field->isSkip()) {
                    break;
                }

                if (true !== $value) {
                    $field->update($value);
                }
            }

            if (!$field->isSkip()) {
                $data[$fieldName] = $field->value();
            }
        }

        return compact('success', 'errors', 'data');
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
        if (isset($this->cache[$name])) {
            return $this->rules[$this->cache[$name]];
        }

        foreach ($this->rules as $key => $rule) {
            if ($rule->has($name)) {
                $this->cache[$name] = $key;

                return $rule;
            }
        }

        throw new \DomainException(sprintf('Validation rule not exists: %s.', $name));
    }

    /**
     * Get message for rule.
     *
     * @param string $rule
     * @param array  $arguments
     * @param Field  $field
     *
     * @return string
     */
    private function message(string $rule, array $arguments, Field $field): string
    {
        $params = array(
            '%rule%' => $rule,
            '%field%' => $field->field(),
            '%value%' => $this->fw->stringify($field->value()),
            '%all%' => $this->fw->stringify($arguments),
        );

        foreach ($arguments as $key => $value) {
            $params['%'.$key.'%'] = $this->fw->stringify($value);
        }

        return $this->fw->transAlt(array(
            'validation.'.strtolower($rule),
            'validation.default',
            'This value is not valid',
        ), $params);
    }
}
