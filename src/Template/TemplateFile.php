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
    protected $_engine;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    protected $_file;

    /**
     * @var TemplateFile
     */
    protected $_reference;

    /**
     * @var TemplateFile
     */
    protected $_parent;

    /**
     * @var array
     */
    protected $_sections;

    /**
     * @var array
     */
    protected $_starts;

    /**
     * @var int
     */
    protected $_level;

    /**
     * @var string
     */
    protected $_content;

    /**
     * @var array
     */
    protected $_context;

    /**
     * @var array
     */
    protected $_globals;

    /**
     * @var int
     */
    protected $_ptr;

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
        $this->_engine = $engine;
        $this->_name = $name;
        $this->_reference = $reference;
        $this->_file = $engine->find($name);
        $this->_context = (array) $context;
    }

    /**
     * Render template.
     *
     * @return string
     */
    public function render(): string
    {
        $this->_sections = array();
        $this->_starts = array();
        $this->_ptr = -1;

        $this->doRender();

        if ($this->_parent) {
            return $this->_parent->render();
        }

        return $this->_content;
    }

    /**
     * Load another template.
     *
     * @param string|null $name
     * @param array|null  $context
     * @param bool        $useContext
     *
     * @return string
     */
    protected function load(string $name = null, array $context = null, bool $useContext = true): string
    {
        return $this->_engine->render($name, (array) $context + ($useContext ? $this->_context : array()));
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
        if ($this->_reference && $content = $this->_reference->section($name)) {
            return $content;
        }

        return $this->_sections[$name] ?? null;
    }

    /**
     * Returns parent section content.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected function parent(string $name): ?string
    {
        return $this->_parent ? $this->_parent->section($name) : null;
    }

    /**
     * Set template parent.
     *
     * @param string $view
     */
    protected function extend(string $view): void
    {
        $this->_parent = new TemplateFile($this->_engine, $view, $this->_context, $this);
        $this->_parent->render();

        $this->_sections = $this->_parent->_sections;
    }

    /**
     * Start block.
     *
     * @param string $name
     * @param bool   $raw
     */
    protected function start(string $name, bool $raw = false): void
    {
        $this->_starts[++$this->_ptr] = array($this->_ptr, $name, false, $raw);
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

        $this->_sections[$name] = $raw ? $content : trim($content).PHP_EOL;
        $this->_starts[$ptr][2] = true;

        if (!$this->_parent) {
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
        for ($i = count($this->_starts) - 1; $i >= 0; --$i) {
            if ($this->_starts[$i][2]) {
                continue;
            }

            return $this->_starts[$i];
        }

        return null;
    }

    /**
     * Do render.
     */
    protected function doRender(): void
    {
        $this->_level = ob_get_level();

        ob_start();

        try {
            extract($this->_context);

            include $this->_file;
        } catch (\Throwable $e) {
            while (ob_get_level() > $this->_level) {
                ob_end_clean();
            }

            throw $e;
        }

        $this->_content = ob_get_clean();
    }

    /**
     * Returns globals data or proxy to Core::service.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (null === $this->_globals) {
            $this->_globals = $this->_engine->getGlobals();
        }

        if (array_key_exists($key, $this->_globals)) {
            return $this->_globals[$key];
        }

        return $this->_engine->service($key);
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
        return $this->_engine->$method(...$args);
    }
}
