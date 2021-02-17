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

namespace Ekok\Stick\Event;

class RequestControllerArgumentsEvent extends RequestEvent
{
    private $arguments;
    private $argumentsSet = false;

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function hasArguments(): bool
    {
        return $this->argumentsSet;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments, bool $set = true): static
    {
        $this->arguments = $arguments;
        $this->argumentsSet = $set;

        return $this;
    }
}
