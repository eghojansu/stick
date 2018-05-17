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

/**
 * Validator wrapper.
 */
final class Validator
{
    /** @var array */
    private $validators = [];

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
        $error = [];

        foreach ($rules as $field => $fieldRules) {
            foreach (Helper::parsexpr($fieldRules) as $rule => $args) {
                $validator = $this->findValidator($rule);
                $value = array_key_exists($field, $validated) ? $validated[$field] : Helper::ref($field, $data, false);
                $result = $validator->validate($rule, $value, $args, $field, $validated, $data);

                if (false === $result) {
                    // validation fail
                    $error[$field][] = $validator->message($rule, $value, $args, $field, $messages[$field.'.'.$rule] ?? null);
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
            'success' => 0 === count($error),
            'error' => $error,
            'data' => $validated,
        ];
    }

    /**
     * Find validator for rule.
     *
     * @param string $rule
     *
     * @return ValidatorInterface
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
}
