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

/**
 * Validation result.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Result
{
    /**
     * Validation context.
     *
     * @var Context
     */
    public $context;

    /**
     * Error lists.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Class constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns field error.
     *
     * @param string $field
     *
     * @return array
     */
    public function getError(string $field): array
    {
        return $this->errors[$field] ?? array();
    }

    /**
     * Sets field errors.
     *
     * @param string $field
     * @param array  $errors
     *
     * @return Result
     */
    public function setError(string $field, array $errors): Result
    {
        $this->errors[$field] = $errors;

        return $this;
    }

    /**
     * Add field error.
     *
     * @param string       $field
     * @param string|array $error
     *
     * @return Result
     */
    public function addError(string $field, $error): Result
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = array();
        }

        if (is_array($error)) {
            array_push($this->errors[$field], ...array_values($error));
        } else {
            $this->errors[$field][] = $error;
        }

        return $this;
    }

    /**
     * Returns all errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns true if no error.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns validated data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->context->getValidated();
    }
}
