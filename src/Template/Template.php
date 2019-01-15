<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 13, 2019 10:11
 */

namespace Fal\Stick\Template;

use Fal\Stick\Core;

class Template
{
    protected $core;

    protected $functions = array();

    protected $directories = array();

    protected $extension;

    public function __construct(Core $core, $directories = null, $extension = '.php')
    {
        $this->core = $core;
        $this->extension = $extension;
        empty($directories) || $this->setDirectories($core->split($directories));
    }

    public function getDirectories()
    {
        return $this->directories;
    }

    public function addDirectory($directory, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->directories, $directory);
        } else {
            $this->directories[] = $directory;
        }

        return $this;
    }

    public function setDirectories(array $directories)
    {
        $this->directories = array();

        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }

        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    public function addFunction($name, callable $callback)
    {
        $this->functions[$name] = $callback;

        return $this;
    }

    public function findView($view)
    {
        foreach ($this->directories as $directory) {
            if (file_exists($file = $directory.$view.$this->extension)) {
                return $file;
            }
        }

        throw new \LogicException(sprintf('View not exists: "%s"', $view));
    }

    public function render($view, array $context = null, $mime = 'text/html')
    {
        $content = TemplateFile::create($this, $this->core, $view, $context)->render();

        $this->core->set('RESPONSE.Content-Type', $mime);
        $this->core->set('RESPONSE.Content-Length', strlen($content));

        return $content;
    }

    public function __call($method, $arguments)
    {
        if (isset($this->functions[$method])) {
            $call = $this->functions[$method];
            array_unshift($arguments, $this->core);
        } elseif (method_exists($this->core, $method)) {
            $call = array($this->core, $method);
        } else {
            throw new \BadFunctionCallException(sprintf('Call to undefined function: %s', $method));
        }

        return call_user_func_array($call, $arguments);
    }
}
