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

namespace Fal\Stick\Template;

use Fal\Stick\Fw;

/**
 * PHP Template engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Template
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array|callable
     */
    protected $globals;

    /**
     * @var string
     */
    protected $extension;

    /**
     * @var array
     */
    protected $functions = array(
        'esc' => 'htmlspecialchars',
        'raw' => 'htmlspecialchars_decode',
    );

    /**
     * Class constructor.
     *
     * @param Fw                  $fw
     * @param array|string|null   $paths
     * @param array|callable|null $globals
     * @param string              $extension
     */
    public function __construct(Fw $fw, $paths = null, $globals = null, string $extension = '.php')
    {
        $this->fw = $fw;
        $this->paths = $fw->split($paths);
        $this->globals = $globals;
        $this->extension = $extension;
    }

    /**
     * Returns paths.
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Add path.
     *
     * @param string $path
     *
     * @return Template
     */
    public function addPath(string $path): Template
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Prepend path.
     *
     * @param string $path
     *
     * @return Template
     */
    public function prependPath(string $path): Template
    {
        array_unshift($this->paths, $path);

        return $this;
    }

    /**
     * Find template.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws LogicException If template not exists
     */
    public function find(string $name): string
    {
        foreach ($this->paths as $path) {
            if (is_file($view = $path.$name.$this->extension)) {
                return $view;
            }
        }

        throw new \LogicException(sprintf('Template "%s" does not exists.', $name));
    }

    /**
     * Returns globals data.
     *
     * @return array
     */
    public function getGlobals(): array
    {
        if (is_callable($this->globals)) {
            $this->globals = (array) $this->fw->call($this->globals);
        } elseif (!is_array($this->globals)) {
            $this->globals = (array) $this->globals;
        }

        return $this->globals;
    }

    /**
     * Sets globals.
     *
     * @param callable|array|null $globals
     *
     * @return Template
     */
    public function setGlobals($globals): Template
    {
        $this->globals = $globals;

        return $this;
    }

    /**
     * Returns template extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Sets template extension.
     *
     * @param string $extension
     *
     * @return Template
     */
    public function setExtension(string $extension): Template
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Add function.
     *
     * @param string   $name
     * @param callable $cb
     *
     * @return Template
     */
    public function addFunction(string $name, callable $cb): Template
    {
        $this->functions[$name] = $cb;

        return $this;
    }

    /**
     * Render template file.
     *
     * @param string     $name
     * @param array|null $context
     *
     * @return string
     */
    public function render(string $name, array $context = null): string
    {
        return (new TemplateFile($this, $name, $context))->render();
    }

    /**
     * Call registered functions.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (isset($this->functions[$method])) {
            $call = $this->functions[$method];
        } elseif (method_exists($this->fw, $method)) {
            $call = array($this->fw, $method);
        } else {
            throw new \LogicException(sprintf('Call to undefined function "%s".', $method));
        }

        return $call(...$args);
    }
}
