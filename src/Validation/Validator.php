<?php declare(strict_types=1);

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

final class Validator
{
    /** @var array */
    private $validators = [];

    public function add(ValidatorInterface $validator): Validator
    {
        $this->validators[] = $validator;

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

        foreach ($rules as $field => $fieldRules) {
            foreach (Helper::parsexpr($fieldRules) as $rule => $args) {
                $validator = $this->findValidator($rule);
                $value = array_key_exists($field, $validated) ? $validated[$field] : $this->ref($field, $data, false);
                $result = $validator->validate($rule, $value, $args, $field, $validated, $data);

                if ($result === false) {
                    // validation fail
                    $error[$field][] = $validator->message($rule, $value, $args, $field, $messages[$field . '.' . $rule] ?? null);
                    break;
                } elseif ($result === true) {
                    $ref =& $this->ref($field, $validated);
                    $ref = $value;
                } else {
                    $ref =& $this->ref($field, $validated);
                    $ref = $result;
                }
            }
        }

        return [
            'success' => count($error) === 0,
            'error' => $error,
            'data' => $validated,
        ];
    }

    private function findValidator(string $rule): ValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator->has($rule)) {
                return $validator;
            }
        }

        throw new \DomainException('Rule "' . $rule . '" does not exists');
    }

    /**
     * Get hive ref
     *
     * @param  string $key
     * @param  array  $var
     * @param  bool   $add
     *
     * @return mixed
     */
    private function &ref(string $key, array &$var, bool $add = true)
    {
        $null = null;
        $parts = explode('.', $key);

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = [];
            }

            if ($add || array_key_exists($part, $var)) {
                $var =& $var[$part];
            } else {
                $var =& $null;
                break;
            }
        }

        return $var;
    }
}
