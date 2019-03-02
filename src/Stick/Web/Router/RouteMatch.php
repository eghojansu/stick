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

namespace Fal\Stick\Web\Router;

/**
 * Route match.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class RouteMatch
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected $allowedMethods;

    /**
     * @var mixed
     */
    protected $controller;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * Class constructor.
     *
     * @param string $pattern
     * @param string $alias
     * @param array  $allowedMethods
     * @param mixed  $controller
     * @param array  $arguments
     */
    public function __construct(string $pattern, ?string $alias, array $allowedMethods, $controller, array $arguments)
    {
        $this->pattern = $pattern;
        $this->alias = $alias;
        $this->allowedMethods = $allowedMethods;
        $this->controller = $controller;
        $this->arguments = $arguments;
    }

    /**
     * Returns pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Returns pattern alias.
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Returns allowed methods.
     *
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * Returns controller.
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Returns arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
