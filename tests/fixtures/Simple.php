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

namespace Fixtures;

final class Simple
{
    private $name;
    private $std;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStd(): ?\stdClass
    {
        return $this->std;
    }

    public function setStd(\stdClass $std): self
    {
        $this->std = $std;

        return $this;
    }

    public static function outArguments(...$arguments)
    {
        return implode(' ', $arguments);
    }
}
