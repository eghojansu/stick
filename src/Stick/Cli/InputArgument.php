<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Cli;

/**
 * Command argument.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class InputArgument
{
    const IS_REQUIRED = 1;
    const IS_ARRAY = 2;

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var mixed */
    protected $defaultValue;

    /** @var int */
    protected $valueRequirement;

    /**
     * Static class constructor.
     *
     * @param string      $name
     * @param string|null $description
     * @param mixed       $defaultValue
     * @param int         $valueRequirement
     *
     * @return InputArgument
     */
    public static function create(
        string $name,
        string $description = null,
        $defaultValue = null,
        int $valueRequirement = 0
    ): InputArgument {
        return new static($name, $description, $defaultValue, $valueRequirement);
    }

    /**
     * Class constructor.
     *
     * @param string      $name
     * @param string|null $description
     * @param mixed       $defaultValue
     * @param int         $valueRequirement
     */
    public function __construct(
        string $name,
        string $description = null,
        $defaultValue = null,
        int $valueRequirement = 0
    ) {
        $this->name = $name;
        $this->description = $description ?? '';
        $this->defaultValue = $defaultValue;
        $this->valueRequirement = $valueRequirement;
    }

    /**
     * Returns name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns defaultValue.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Returns valueRequirement.
     *
     * @return int
     */
    public function getValueRequirement(): int
    {
        return $this->valueRequirement;
    }
}
