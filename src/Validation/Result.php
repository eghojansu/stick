<?php

declare(strict_types=1);

namespace Ekok\Stick\Validation;

class Result
{
    private $errors = array();
    private $data = array();
    private $raw;

    public function __construct(array $raw = null)
    {
        $this->raw = $raw;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function addData(string $field, $value): self
    {
        $this->data[$field] = $value;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(string $field): ?array
    {
        return $this->errors[$field] ?? null;
    }

    public function addError(string $field, array $errors): self
    {
        foreach ($errors as $key => $error) {
            $this->errors[$field][$key] = $error;
        }

        return $this;
    }

    public function addErrors(array $errors): self
    {
        foreach ($errors as $field => $fieldErrors) {
            $this->addError($field, (array) $fieldErrors);
        }

        return $this;
    }

    public function valid(): bool
    {
        return !$this->errors;
    }

    public function invalid(): bool
    {
        return !$this->valid();
    }
}
