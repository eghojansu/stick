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

use Fal\Stick\Util\Common;

/**
 * Template file.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Template
{
    /**
     * @var Environment
     */
    protected $env;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var string
     */
    protected $compiledPath;

    /**
     * @var Template
     */
    protected $parent;

    /**
     * @var Template
     */
    protected $child;

    /**
     * @var array
     */
    protected $blocks = array();

    /**
     * @var array
     */
    protected $parentMarks = array();

    /**
     * Class constructor.
     *
     * @param Environment   $env
     * @param string        $templateName
     * @param string        $sourcePath
     * @param string        $compiledPath
     * @param Template|null $child
     */
    public function __construct(Environment $env, string $templateName, string $sourcePath, string $compiledPath, Template $child = null)
    {
        $this->env = $env;
        $this->templateName = $templateName;
        $this->sourcePath = $sourcePath;
        $this->compiledPath = $compiledPath;
        $this->child = $child;
    }

    /**
     * Returns template name.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Returns template source filepath.
     *
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Returns compiled template filepath.
     *
     * @return string
     */
    public function getCompiledPath(): string
    {
        return $this->compiledPath;
    }

    /**
     * Render template.
     *
     * @param array|null $context
     *
     * @return string
     */
    public function render(array $context = null): string
    {
        $content = $this->sandbox($context ?? array());

        return $this->parent ? $this->parent->render($context) : Common::trimTrailingSpace(rtrim($content));
    }

    /**
     * Set parent template.
     *
     * @param string $templateName
     */
    protected function extend(string $templateName): void
    {
        $this->parent = $this->env->loadTemplate($templateName, $this);
    }

    /**
     * Mark parent block.
     *
     * @param string|null $parentBlock
     *
     * @return string|null
     */
    protected function parent(string $parentBlock = null): ?string
    {
        if (!$block = $this->findOpenBlock()) {
            return null;
        }

        if (!$parentBlock) {
            $parentBlock = $block;
        }

        return $this->parentMarks[$block][$parentBlock][] = '%parent_'.microtime(true).'_parent%';
    }

    /**
     * Returns block content.
     *
     * @param string $block
     *
     * @return string|null
     */
    protected function block(string $block): ?string
    {
        return $this->blocks[$block] ?? null;
    }

    /**
     * Apply parent block if exists.
     *
     * @param string $block
     * @param string $content
     *
     * @return string
     */
    protected function applyParentBlock(string $block, string $content): string
    {
        $subject = $this->blocks[$block] ?? $content;

        if (isset($this->parentMarks[$block])) {
            foreach ($this->parentMarks[$block] as $parentBlock => $search) {
                if (null !== $replace = $this->parent->block($parentBlock)) {
                    $subject = str_replace($search, $replace, $subject);
                }
            }
        }

        return $subject;
    }

    /**
     * Mark start block.
     *
     * @param string $block
     */
    protected function start(string $block): void
    {
        $this->blocks[$block] = null;
        ob_start();
    }

    /**
     * Stop block and output block content if needed.
     */
    protected function stop(): void
    {
        if (!$block = $this->findOpenBlock()) {
            return;
        }

        $this->blocks[$block] = ob_get_clean();

        if ($this->child) {
            $this->blocks[$block] = $this->child->applyParentBlock($block, $this->blocks[$block]);
        }

        if (!$this->parent || $this->child) {
            echo $this->blocks[$block];
        }
    }

    /**
     * Returns latest open block.
     *
     * @return string|null
     */
    protected function findOpenBlock(): ?string
    {
        foreach (array_reverse($this->blocks) as $block => $content) {
            if (null === $content) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Load view.
     *
     * @param array $__context
     *
     * @return string
     */
    protected function sandbox(array $__context): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            extract($__context);

            $_ = $this->env->fw;

            require $this->compiledPath;
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw new \RuntimeException(sprintf('An exception has been thrown during the rendering of a template: %s ("%s").', $this->templateName, $e->getMessage()), 0, $e);
        }

        return ob_get_clean();
    }

    /**
     * Call env method.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->env->$method(...$arguments);
    }
}
