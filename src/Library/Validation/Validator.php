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
use Fal\Stick\Util;

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
        $this->fw = $fw->prepend('LOCALES', __DIR__.'/dict/;');
    }

    /**
     * Add validator.
     *
     * @param ValidatorInterface $validator
     *
     * @return Validator
     */
    public function add(ValidatorInterface $validator): Validator
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
     * @return array
     */
    public function validate(array $data, array $rules, array $messages = null): array
    {
        $validated = array();
        $errors = array();

        foreach ($rules as $field => $fieldRules) {
            foreach (Util::parseExpr($fieldRules) as $rule => $args) {
                $value = array_key_exists($field, $validated) ? $validated[$field] : $this->fw->ref($field, false, $data);
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
