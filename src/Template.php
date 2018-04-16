<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

class Template
{
    /** Block mark */
    const BLOCK_MARK = '<!-- block_%s -->';

    /** @var TemplateEngine */
    protected $engine;

    /** @var string */
    protected $file;

    /** @var Template */
    protected $parent;

    /** @var array */
    protected $context;

    /** @var string */
    protected $rendered = '';

    /** @var array */
    protected $blocks = [];

    /**
     * Class constructor
     *
     * @param TemplateEngine $engine
     * @param string         $file
     * @param array          $context
     */
    public function __construct(TemplateEngine $engine, string $file, array $context)
    {
        $this->engine = $engine;
        $this->file = $file;
        $this->context = $context;
    }

    /**
     * Get parent block content
     *
     * @return string
     */
    public function parent(): string
    {
        if (!$this->parent) {
            return '';
        }

        end($this->blocks);
        $last = key($this->blocks);

        return $last ? $this->parent->getBlock($last) : '';
    }

    /**
     * Get block content
     *
     * @param  string $name
     *
     * @return string
     */
    public function getBlock(string $name): string
    {
        return $this->blocks[$name] ?? '';
    }

    /**
     * Get blocks
     *
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get rendered
     *
     * @return string
     */
    public function getRendered(): string
    {
        return $this->rendered;
    }

    /**
     * Render template
     *
     * @param bool $replace
     *
     * @return string
     */
    public function render(bool $replace = true): string
    {
        extract($this->context);
        ob_start();
        require $this->file;
        $out = ob_get_clean();

        if ($this->parent) {
            $out = $this->replaceBlocks(
                $this->parent->getRendered(),
                $this->blocks + $this->parent->getBlocks()
            );
            $replace = false;
        }

        if ($replace) {
            $out = $this->replaceBlocks($out, $this->blocks);
        }

        return $this->rendered = $out;
    }

    /**
     * Include file
     *
     * @param  string $file
     * @param  array  $context
     *
     * @return string
     */
    protected function include(string $file, array $context = []): string
    {
        return $this->engine->make($file, $context)->render();
    }

    /**
     * Set parent template
     *
     * @param  string $file
     * @param  array  $context
     *
     * @return void
     */
    protected function layout(string $file, array $context = []): void
    {
        $this->parent = $this->engine->make($file, $context);
        $this->parent->render(false);
    }

    /**
     * Open block
     *
     * @param  string $name
     *
     * @return void
     */
    protected function block(string $name): void
    {
        $this->blocks[$name] = null;
        ob_start();
    }

    /**
     * Close block
     *
     * @return void
     */
    protected function endBlock(): void
    {
        $content = ob_get_clean();
        end($this->blocks);
        $last = key($this->blocks);
        $this->blocks[$last] = $content;

        printf(self::BLOCK_MARK, $last);
    }

    /**
     * Perform block content replacement
     *
     * @param  string $content
     * @param  array  $blocks
     *
     * @return string
     */
    protected function replaceBlocks(string $content, array $blocks): string
    {
        $search = [];
        $replace = [];

        foreach ($blocks as $id => $block) {
            $search[] = '/' . preg_quote(sprintf(self::BLOCK_MARK, $id), '/') . '/';
            $replace[] = $block;
        }

        return preg_replace($search, $replace, $content);
    }

    /**
     * Call registered function
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->engine->$method(...$args);
    }
}
