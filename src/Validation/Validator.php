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

namespace Ekok\Stick\Validation;

use Ekok\Stick\Fw;

class Validator
{
    protected $result;
    protected $providers = array();
    protected $map = array();

    public function __construct(array $providers = null)
    {
        $this->addProviders($providers ?? array(new DefaultProvider()));
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function addProvider(ProviderInterface $provider): static
    {
        $this->providers[] = $provider;

        return $this;
    }

    public function addProviders(array $providers): static
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }

        return $this;
    }

    public function getResult(): ?Result
    {
        return $this->result;
    }

    public function validate(array $rules, array $data = null, array $options = null): bool
    {
        $result = new Result($data);

        $messages = $options['messages'] ?? null;
        $skipOnError = $options['skipOnError'] ?? false;

        foreach ($rules as $field => $fieldRules) {
            list($useField, $suffix, $positional) = $this->splitField($field);

            $customMessage = $messages[$field] ?? null;
            $context = new Context($useField, Fw::refValue($result->getRaw(), $useField), array(
                'suffix' => $suffix,
                'positional' => $positional,
                'raw' => $result->getRaw(),
                'data' => $result->getData(),
            ));
            $errors = $this->validateField($fieldRules, $context, $customMessage);

            if ($errors) {
                $result->addErrors($errors);

                if ($skipOnError) {
                    break;
                } else {
                    continue;
                }
            }

            if (!$context->isExcluded()) {
                $result->addData($context->getField(), $context->getValue());
            }
        }

        $this->result = $result;

        return $result->valid();
    }

    protected function validateField($fieldRules, Context $context, string $customMessage = null): array
    {
        $rules = is_array($fieldRules) ? $fieldRules : Fw::parseExpression($fieldRules);
        $violations = array();

        if ($context->getSuffix() || $context->isPositional()) {
            $data = (array) ($context->getValue() ?: array(null));
            $values = (array) ($context->getCurrentData() ?? array());
            $field = null;
            $prefix = $context->getPath();
            $suffix = null;

            if ($context->getSuffix()) {
                list($field, $suffix) = $this->splitField($context->getSuffix());
            }

            foreach ($data as $position => $value) {
                $useField = $field;
                $useValue = $value;
                $usePosition = $position;

                if (!$useField) {
                    $useField = "{$position}";
                    $useValue = $data;
                    $usePosition = null;
                }

                $newContext = $context->duplicate($useField, Fw::refValue((array) $useValue, $useField), array(
                    'prefix' => $prefix,
                    'suffix' => $suffix,
                    'position' => $usePosition,
                    'data' => $values,
                    'raw' => $data,
                ));
                $subViolations = $this->validateField($fieldRules, $newContext, $customMessage);

                if ($subViolations) {
                    $violations = array_merge_recursive($violations, $subViolations);
                } elseif (!$newContext->isExcluded()) {
                    if ($field) {
                        $values[$position][$newContext->getField()] = $newContext->getValue();
                    } else {
                        $values[$position] = $newContext->getValue();
                    }
                }
            }

            if (!$violations) {
                $context->setValue($values);
            }

            return $violations;
        }

        foreach ($rules as $rule => $arguments) {
            $provider = $this->findProvider($rule);
            $result = $provider->validate($rule, $context->freeValue(), ...$arguments);

            if ($context->isSkipped()) {
                break;
            }

            if (false === $result || !$context->valid()) {
                $message =& Fw::refCreate($violations, $context->getPath());
                $message[] = $provider->message($rule, $context, ...$arguments);

                break;
            }

            if (true !== $result) {
                $context->setValue($result);
            }
        }

        return $violations;
    }

    protected function splitField(string $field): array
    {
        if (false === $pos = strpos($field, '*')) {
            return array($field, null, false);
        }

        return array(substr($field, 0, $pos - 1), substr($field, $pos + 2) ?: null, '*' === substr($field, -1));
    }

    protected function findProvider(string $rule): ProviderInterface
    {
        return $this->providers[$this->map[$rule] ?? 'none'] ?? $this->providers[$this->map[$rule] = $this->resolveProviderIndex($rule)];
    }

    protected function resolveProviderIndex(string $rule): int
    {
        for ($i = count($this->providers) - 1; $i >= 0; $i--) {
            if ($this->providers[$i]->check($rule)) {
                return $i;
            }
        }

        throw new \InvalidArgumentException("Rule provider not found: {$rule}.");
    }
}
