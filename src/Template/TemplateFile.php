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

/**
 * Template file data.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class TemplateFile
{
    /**
     * @var Template
     */
    protected $engine;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var TemplateFile
     */
    protected $reference;

    /**
     * @var TemplateFile
     */
    protected $parent;

    /**
     * @var array
     */
    protected $sections;

    /**
     * @var array
     */
    protected $starts;

    /**
     * @var int
     */
    protected $level;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var int
     */
    protected $ptr;

    /**
     * Class constructor.
     *
     * @param Template          $engine
     * @param string            $name
     * @param array|null        $context
     * @param TemplateFile|null $reference
     */
    public function __construct(Template $engine, string $name, array $context = null, TemplateFile $reference = null)
    {
        $this->engine = $engine;
        $this->name = $name;
        $this->reference = $reference;
        $this->file = $engine->find($name);
        $this->context = (array) $context;
    }

    /**
     * Render template.
     *
     * @param string|null $name
     * @param array|null  $context
     * @param bool        $useContext
     *
     * @return string
     */
    public function render(string $name = null, array $context = null, bool $useContext = true): string
    {
        if ($name) {
            return $this->engine->render($name, (array) $context + ($useContext ? $this->context : array()));
        }

        $this->sections = array();
        $this->starts = array();
        $this->ptr = -1;

        $this->doRender();

        if ($this->parent) {
            return $this->parent->render();
        }

        return $this->content;
    }

    /**
     * Returns section.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected function section(string $name): ?string
    {
        if ($this->reference && $content = $this->reference->section($name)) {
            return $content;
        }

        return $this->sections[$name] ?? null;
    }

    /**
     * Set template parent.
     *
     * @param string $view
     */
    protected function parent(string $view): void
    {
        $this->parent = new TemplateFile($this->engine, $view, $this->context, $this);
        $this->parent->render();

        $this->sections = $this->parent->sections;
    }

    /**
     * Start block.
     *
     * @param string $name
     * @param bool   $raw
     */
    protected function start(string $name, bool $raw = false): void
    {
        $this->starts[++$this->ptr] = array($this->ptr, $name, false, $raw);
        ob_start();
    }

    /**
     * Stop block.
     */
    protected function stop(): void
    {
        if (!$start = $this->findStart()) {
            throw new \LogicException(sprintf('Stop without starting point is not possible.'));
        }

        $content = ob_get_clean();
        list($ptr, $name, $closed, $raw) = $start;

        $this->sections[$name] = $raw ? $content : trim($content).PHP_EOL;
        $this->starts[$ptr][2] = true;

        if (!$this->parent) {
            echo $this->section($name);
        }
    }

    /**
     * Find start block.
     *
     * @return array|null
     */
    protected function findStart(): ?array
    {
        for ($i = count($this->starts) - 1; $i >= 0; --$i) {
            if ($this->starts[$i][2]) {
                continue;
            }

            return $this->starts[$i];
        }

        return null;
    }

    /**
     * Do render.
     */
    protected function doRender(): void
    {
        $this->level = ob_get_level();

        ob_start();

        try {
            extract($this->context + $this->engine->getGlobals());

            include $this->file;
        } catch (\Throwable $e) {
            while (ob_get_level() > $this->level) {
                ob_end_clean();
            }

            throw $e;
        }

        $this->content = ob_get_clean();
    }

    /**
     * Proxy to Fw::service.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->engine->service($name);
    }

    /**
     * Proxy to engine method call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->engine->$method(...$args);
    }
}
