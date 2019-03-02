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

use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Util;

/**
 * Template engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Template implements TemplateInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $functions = array();

    /**
     * @var array
     */
    protected $directories;

    /**
     * @var string
     */
    protected $extension;

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     * @param mixed              $directories
     * @param string             $extension
     */
    public function __construct(ContainerInterface $container, $directories = null, string $extension = '.php')
    {
        $this->container = $container;
        $this->extension = $extension;
        $this->setDirectories(Util::split($directories));
    }

    /**
     * {inheritdoc}.
     */
    public function findView(string $view): string
    {
        foreach ($this->directories as $directory) {
            if (file_exists($file = $directory.$view.$this->extension)) {
                return $file;
            }
        }

        throw new \LogicException(sprintf('View not exists: "%s".', $view));
    }

    /**
     * {inheritdoc}.
     */
    public function render(string $view, array $context = null): string
    {
        $template = new TemplateFile($this, $this->container, $view, $context);

        return $template->render();
    }

    /**
     * Returns template directories.
     *
     * @return array
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Add directory.
     *
     * @param string $directory
     * @param bool   $prepend
     *
     * @return Template
     */
    public function addDirectory(string $directory, bool $prepend = false): Template
    {
        if ($prepend) {
            array_unshift($this->directories, $directory);
        } else {
            $this->directories[] = $directory;
        }

        return $this;
    }

    /**
     * Assign template directories.
     *
     * @param array $directories
     *
     * @return Template
     */
    public function setDirectories(array $directories): Template
    {
        $this->directories = array();

        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }

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
     * Returns template extension.
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
     * Add custom template function.
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return Template
     */
    public function addFunction(string $name, callable $callback): Template
    {
        $this->functions[$name] = $callback;

        return $this;
    }

    /**
     * Forward method call to custom function.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!isset($this->functions[$method])) {
            throw new \BadFunctionCallException(sprintf('Call to undefined function: %s.', $method));
        }

        return ($this->functions[$method])(...$arguments);
    }
}
