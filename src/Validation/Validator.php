<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Validation;

use Fal\Stick\Helper;
use Fal\Stick\Translator;

/**
 * Validator wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Validator
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $validators = [];

    /**
     * Class constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
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
     * Do validate.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return array
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach (Helper::parsexpr($fieldRules) as $rule => $args) {
                $validator = $this->findValidator($rule);
                $value = array_key_exists($field, $validated) ? $validated[$field] : Helper::ref($field, $data, false);
                $result = $validator->validate($rule, $value, $args, $field, $validated, $data);

                if (false === $result) {
                    // validation fail
                    $errors[$field][] = $this->message($rule, $value, $args, $field, $messages[$field.'.'.$rule] ?? null);
                    break;
                } elseif (true === $result) {
                    $ref = &Helper::ref($field, $validated);
                    $ref = $value;
                } else {
                    $ref = &Helper::ref($field, $validated);
                    $ref = $result;
                }
            }
        }

        return [
            'success' => 0 === count($errors),
            'errors' => $errors,
            'data' => $validated,
        ];
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
    private function findValidator(string $rule): ValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator->has($rule)) {
                return $validator;
            }
        }

        throw new \DomainException('Rule "'.$rule.'" does not exists');
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
    private function message(string $rule, $value = null, array $args = [], string $field = '', string $message = null): string
    {
        if ($message && false === strpos($message, '{')) {
            return $message;
        }

        $key = 'validation.'.strtolower($rule);
        $alt = 'validation.default';
        $fallback = 'This value is not valid.';
        $data = [];

        foreach (compact('field', 'rule', 'value') + $args as $k => $v) {
            $data['{'.$k.'}'] = Helper::stringifyignorescalar($v);
        }

        return $this->translator->transAlt($key, $data, $fallback, $alt);
    }
}
