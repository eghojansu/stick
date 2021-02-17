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

interface ProviderInterface
{
    public function check(string $rule): bool;
    public function message(string $rule, Context $context, ...$arguments): string;
    public function validate(string $rule, Context $context, ...$arguments);
}
