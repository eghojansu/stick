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

class Context
{
    protected $field;
    protected $prefix;
    protected $suffix;
    protected $position;
    protected $type;
    protected $data;
    protected $raw;
    protected $value;
    protected $valueSet = false;
    protected $valid = false;
    protected $skipped = false;
    protected $excluded = false;
    protected $positional = false;

    public function __construct(string $field, $value, array $options = null)
    {
        $this->field = $field;
        $this->setValue($value);
        $this->applyOptions($options);
    }

    public function duplicate(string $field, $value, array $options = null): static
    {
        $clone = clone $this;

        $clone->field = $field;
        $clone->setValue($value);
        $clone->applyOptions($options);

        return $clone;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function isPositional(): bool
    {
        return $this->positional;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCurrentData()
    {
        return $this->data[$this->field] ?? null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): static
    {
        $this->value = $value;
        $this->valueSet = true;
        $this->setValid();
        $this->setType(gettype($value));

        return $this;
    }

    public function hasValue(): bool
    {
        return $this->valueSet;
    }

    public function freeValue(): static
    {
        $this->valueSet = false;
        $this->skipped = false;

        return $this;
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid = true): static
    {
        $this->valid = $valid;

        return $this;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function setSkip(bool $skipped = true): static
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function isExcluded(): bool
    {
        return $this->excluded;
    }

    public function setExcluded(bool $excluded = true): static
    {
        $this->excluded = $excluded;

        return $this;
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, array('integer', 'double'));
    }

    public function isInteger(): bool
    {
        return 'integer' === $this->type;
    }

    public function isDouble(): bool
    {
        return 'double' === $this->type;
    }

    public function isArray(): bool
    {
        return 'array' === $this->type;
    }

    public function isString(): bool
    {
        return 'string' === $this->type;
    }

    public function isBoolean(): bool
    {
        return 'boolean' === $this->type;
    }

    public function isNull(): bool
    {
        return 'NULL' === $this->type;
    }

    public function checkOther(string $field): bool
    {
        return (null !== $this->position && (
                (Fw::refValue($this->data[$this->position] ?? array(), $field, $exists) || $exists)
                || (Fw::refValue($this->raw[$this->position] ?? array(), $field, $exists) || $exists)))
            || (Fw::refValue($this->data, $field, $exists) || $exists)
            || (Fw::refValue($this->raw, $field, $exists) || $exists);
    }

    public function getOther(string $field, $default = null)
    {
        return Fw::refValue($this->data[$this->position] ?? array(), $field) ??
            Fw::refValue($this->raw[$this->position] ?? array(), $field) ??
            Fw::refValue($this->data, $field) ??
            Fw::refValue($this->raw, $field) ??
            $default;
    }

    public function getPath(): string
    {
        $path = '';

        if ($this->prefix) {
            $path .= $this->prefix . '.';
        }

        if (null !== $this->position) {
            $path .= $this->position . '.';
        }

        return $path . $this->field;
    }

    public function getDate($field = null, string $format = null, string $timezone = null): ?\DateTimeInterface
    {
        $toDate = $field ? (is_string($field) && $this->checkOther($field) ? $this->getOther($field) : $field) : $this->value;

        if ($toDate instanceof \DateTimeInterface) {
            return $toDate;
        }

        try {
            $toTimezone = $timezone ? new \DateTimeZone($timezone) : null;
            $timestamp = strtotime($toDate);

            return $timestamp ? (new \DateTime('now', $toTimezone))->setTimestamp($timestamp) : ($format ? \DateTime::createFromFormat($format, $toDate, $toTimezone) : new \DateTime($toDate, $toTimezone));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function compareDate($against = null, string $format = null, string $timezone = null): int
    {
        $compareDate = $this->getDate(null, $format, $timezone);
        $againstDate = $against ? $this->getDate($against, $format, $timezone) : new \DateTime('now', $timezone ? new \DateTimeZone($timezone) : null);

        if (null === $compareDate || null === $againstDate) {
            throw new \RuntimeException("Both date should be valid date: {$this->getPath()}.");
        }

        return $compareDate <=> $againstDate;
    }

    public function getSize(string $field = null)
    {
        $value = $field ? $this->getOther($field) : $this->value;
        $type = gettype($value);

        if ('array' === $type) {
            return count($value);
        }

        if (in_array($type, array('integer', 'double'))) {
            return 0 + $value;
        }

        return strlen((string) $value);
    }

    protected function applyOptions(array $options = null): void
    {
        $this->prefix = $options['prefix'] ?? null;
        $this->suffix = $options['suffix'] ?? null;
        $this->position = $options['position'] ?? null;
        $this->positional = $options['positional'] ?? false;
        $this->raw = $options['raw'] ?? array();
        $this->data = $options['data'] ?? array();
    }
}
