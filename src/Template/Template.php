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

namespace Ekok\Stick\Template;

use Ekok\Stick\Fw;

class Template
{
    protected $directories = array();
    protected $functions = array();
    protected $internals = array(
        'chain',
        'escape',
        'esc' => 'escape',
        'e' => 'escape',
    );
    protected $globals = array();
    protected $options = array(
        'extension' => 'php',
        'thisVar' => '_',
        'escapeFlags' => ENT_QUOTES|ENT_HTML401|ENT_SUBSTITUTE,
        'escapeEncoding' => 'UTF-8',
    );

    public function __construct(array $directories = null, array $options = null)
    {
        $this->addDirectories($directories ?? array());
        $this->setOptions($options ?? array());
    }

    public function __call($name, $arguments)
    {
        if (isset($this->functions[$name])) {
            return ($this->functions[$name])(...$arguments);
        }

        if (isset($this->internals[$name]) || (false !== $found = array_search($name, $this->internals))) {
            $call = $this->internals[$name] ?? $this->internals[$found];

            return $this->$call(...$arguments);
        }

        if (function_exists($name)) {
            return $name(...$arguments);
        }

        throw new \BadFunctionCallException("Function is not found in any context: {$name}.");
    }

    public function createTemplate(string $template, array $data = null)
    {
        return new Context($this, $template, $data);
    }

    public function render(string $template, array $data = null)
    {
        return $this->createTemplate($template, $data)->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        foreach (array_intersect_key($options, $this->options) as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function addDirectory(string $directory, string $name = null): static
    {
        $this->directories[$name ?? 'default'][] = Fw::normSlash($directory, true);

        return $this;
    }

    public function addDirectories(array $directories): static
    {
        foreach ($directories as $name => $directory) {
            $this->addDirectory($directory, is_numeric($name) ? null : $name);
        }

        return $this;
    }

    public function getGlobals(): array
    {
        return $this->globals;
    }

    public function addGlobal(string $name, $value): static
    {
        if ($name === $this->options['thisVar']) {
            throw new \InvalidArgumentException("Variable name is reserved for *this*: {$name}.");
        }

        $this->globals[$name] = $value;

        return $this;
    }

    public function addGlobals(array $globals): static
    {
        foreach ($globals as $name => $value) {
            $this->addGlobal($name, $value);
        }

        return $this;
    }

    public function addFunction(string $name, callable $function): static
    {
        $this->functions[$name] = $function;

        return $this;
    }

    public function findPath(string $template): string
    {
        list($directories, $file) = $this->getTemplateDirectories($template);

        foreach ($directories as $directory) {
            if (
                is_file($filepath = $directory . $file)
                || is_file($filepath = $directory . $file . '.' . $this->options['extension'])
                || is_file($filepath = $directory . strtr($file, '.', '/') . '.' . $this->options['extension'])
            ) {
                return $filepath;
            }
        }

        throw new \InvalidArgumentException("Template not found: '{$template}'.");
    }

    public function getTemplateDirectories(string $template): array
    {
        if (false === $pos = strpos($template, ':')) {
            $directories = $this->directories['default'];
            $file = $template;
        } else {
            $directories = $this->directories[substr($template, 0, $pos)] ?? null;
            $file = substr($template, $pos + 1);
        }

        if (!$directories) {
            throw new \InvalidArgumentException("Directory not exists for template: '{$template}'.");
        }

        return array($directories, $file);
    }

    public function chain($value, string $functions)
    {
        $result = $value;

        foreach (Fw::parseExpression($functions) as $function => $arguments) {
            $result = $this->$function($result, ...$arguments);
        }

        return $result;
    }

    public function escape(?string $data, string $functions = null): string
    {
        $useData = $functions ? $this->chain($data, $functions) : $data;

        return htmlspecialchars($useData ?? '', $this->options['escapeFlags'], $this->options['escapeEncoding']);
    }
}
