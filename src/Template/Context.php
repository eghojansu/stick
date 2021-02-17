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

class Context
{
    protected $engine;
    protected $parent;
    protected $name;
    protected $filepath;
    protected $data = array();
    protected $search = array();
    protected $searchParents = array();
    protected $sections = array();
    protected $sectioning;
    protected $sectioningMode;

    public function __construct(Template $engine, string $name, array $data = null)
    {
        $this->engine = $engine;
        $this->name = $name;

        if ($data) {
            $this->addData($data);
        }
    }

    public function __call($name, $arguments)
    {
        return $this->engine->$name(...$arguments);
    }

    public function getEngine(): Template
    {
        return $this->engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilepath(): string
    {
        return $this->filepath ?? ($this->filepath = $this->engine->findPath($this->name));
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(): string
    {
        $level = ob_get_level();

        try {
            ob_start();
            (static function ($thisVar) {
                $$thisVar = func_get_arg(1);
                extract($$thisVar->getEngine()->getGlobals());
                extract($$thisVar->getData());
                require $$thisVar->getFilepath();
            })($this->engine->getOptions()['thisVar'], $this);
            $content = ob_get_clean();

            if ($this->parent) {
                list($template, $data, $filepath) = $this->parent;
                $parent = $this->engine->createTemplate($template, $data);
                $parent->addData($this->data);
                $parent->merge(compact('content') + $this->sections, $this->searchParents);
                $parent->filepath = $filepath;
                $content = $parent->render();

                return $this->search ? $parent->replace($content, $this->search) : $content;
            }

            return $content;
        } catch (\Throwable $error) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $error;
        }
    }

    public function load(string $view, array $data = null): string
    {
        $template = $this->engine->createTemplate($view, $data);
        $template->addData($this->data);
        $template->filepath = '.' === $view[0] ? $this->resolveRelative($view) : null;

        if ($template->getFilepath() === $this->getFilepath()) {
            throw new \LogicException("Recursive view rendering is not supported.");
        }

        return $template->render();
    }

    public function loadIfExists(string $view, array $data = null, string $default = null): ?string
    {
        try {
            return $this->load($view, $data);
        } catch (\Exception $_) {
            return $default;
        }
    }

    public function addData(array $data): static
    {
        $reserved = $this->engine->getOptions()['thisVar'];

        foreach ($data as $key => $value) {
            if ($key === $reserved) {
                throw new \InvalidArgumentException("Variable name is reserved for *this*: {$key}.");
            }

            $this->data[$key] = $value;
        }

        return $this;
    }

    public function extend(string $parent, array $data = null): void
    {
        $this->parent = array(
            $parent,
            $data ?? array(),
            '.' === $parent[0] ? $this->resolveRelative($parent) : null,
        );
    }

    public function parent(): void
    {
        if (!$this->sectioning) {
            throw new \LogicException("Calling parent when not in section context is forbidden.");
        }

        $key = 'parent__' . mt_rand(100, 999) . '_' . mt_rand(100, 999);
        $this->searchParents[$this->sectioning][] = $key;
        echo $key;
    }

    public function insert(string $sectionName, string $prefix = 'section__'): void
    {
        $key = $prefix . mt_rand(100, 999) . '_' . mt_rand(100, 999);
        $this->search[$key] = $sectionName;
        echo $key;
    }

    public function exists(string $sectionName = 'content'): bool
    {
        return isset($this->sections[$sectionName]);
    }

    public function merge(array $sections, array $searchParents): void
    {
        foreach ($sections as $sectionName => $content) {
            $this->sections[$sectionName] = $content;
        }

        foreach ($searchParents as $sectionName => $search) {
            $this->searchParents[$sectionName] = $search;
        }
    }

    public function section(string $sectionName = 'content', string $default = null): ?string
    {
        return $this->sections[$sectionName] ?? $default;
    }

    public function start(string $sectionName): void
    {
        if ('content' === $sectionName) {
            throw new \InvalidArgumentException("Section name is reserved: {$sectionName}.");
        }

        if ($this->sectioning) {
            throw new \LogicException("Nested section is not supported.");
        }

        $this->sectioning = $sectionName;

        ob_start();
    }

    public function end(bool $flush = false): void
    {
        if (!$this->sectioning) {
            throw new \LogicException("No section has been started.");
        }

        if (isset($this->searchParents[$this->sectioning], $this->sections[$this->sectioning])) {
            $this->sections[$this->sectioning] = str_replace($this->searchParents[$this->sectioning], ob_get_clean(), $this->sections[$this->sectioning]);
        } else {
            $this->sections[$this->sectioning] = ob_get_clean();
        }

        if ($flush) {
            echo $this->sections[$this->sectioning];
        }

        $this->sectioning = null;
    }

    public function endFlush(): void
    {
        $this->end(true);
    }

    protected function replace(string $content, array $search): string
    {
        $replaces = array();

        foreach ($search as $key => $sectionName) {
            $replaces[$key] = $this->sections[$sectionName] ?? null;
        }

        return strtr($content, $replaces);
    }

    protected function resolveRelative(string $view): string
    {
        $relative = dirname($this->getFilepath()) . '/' . $view;

        if (
            !($realpath = realpath($relative))
            && !($realpath = realpath($relative . '.' . $this->engine->getOptions()['extension']))
        ) {
            throw new \InvalidArgumentException("Relative view not found: '{$view}'.");
        }

        return $realpath;
    }
}
