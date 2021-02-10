<?php

declare(strict_types=1);

namespace Ekok\Stick\Validation;

interface ProviderInterface
{
    public function check(string $rule): bool;
    public function message(string $rule, Context $context, ...$arguments): string;
    public function validate(string $rule, Context $context, ...$arguments);
}
