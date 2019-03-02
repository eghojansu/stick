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

/**
 * Template file.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class TemplateFile
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Template
     */
    protected $engine;

    /**
     * @var string
     */
    protected $view;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var TemplateFile
     */
    protected $parent;

    /**
     * @var TemplateFile
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
     * @param TemplateInterface  $engine
     * @param ContainerInterface $container
     * @param string             $view
     * @param array|null         $context
     * @param TemplateFile|null  $child
     */
    public function __construct(TemplateInterface $engine, ContainerInterface $container, string $view, array $context = null, TemplateFile $child = null)
    {
        $this->engine = $engine;
        $this->container = $container;
        $this->view = $view;
        $this->context = $context;
        $this->child = $child;
    }

    /**
     * Do render view.
     *
     * @return string
     */
    public function render(): string
    {
        $content = $this->load($this->view);

        return $this->parent ? $this->parent->render() : $content;
    }

    /**
     * Fork.
     *
     * @param string            $view
     * @param array|null        $context
     * @param TemplateFile|null $child
     *
     * @return TemplateFile
     */
    protected function fork(string $view, array $context = null, TemplateFile $child = null): TemplateFile
    {
        return new static($this->engine, $this->container, $view, $context, $child);
    }

    /**
     * Load view.
     *
     * @param string     $view
     * @param array|null $context
     * @param bool       $withParentContext
     *
     * @return string
     *
     * @todo option to not extract parent context
     */
    protected function load(string $view, array $context = null, bool $withParentContext = true): string
    {
        $file = $this->engine->findView($view);

        if ($withParentContext && $this->context) {
            extract($this->context);
        }

        if ($context) {
            extract($context);
        }

        ob_start();
        require $file;

        return ob_get_clean();
    }

    /**
     * Set parent view.
     *
     * @param string     $view
     * @param array|null $context
     */
    protected function extend(string $view, array $context = null): void
    {
        $this->parent = $this->fork($view, (array) $context + (array) $this->context, $this);
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

        if (!$this->parent) {
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
     * Returns service instance.
     *
     * @param string $service
     *
     * @return mixed
     */
    public function __get($service)
    {
        return $this->container->get($service);
    }

    /**
     * Call engine method.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return (array($this->engine, $method))(...$arguments);
    }
}
