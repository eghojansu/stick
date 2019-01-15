<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Template;

use Fal\Stick\Core;

class TemplateFile
{
    protected $core;

    protected $engine;

    protected $view;

    protected $context;

    protected $parent;

    protected $child;

    protected $blocks = [];
    protected $parentMarks = [];

    public static function create(Template $engine, Core $core, $view, array $context = null, self $child = null)
    {
        return new static($engine, $core, $view, $context, $child);
    }

    public function __construct(Template $engine, Core $core, $view, array $context = null, self $child = null)
    {
        $this->engine = $engine;
        $this->core = $core;
        $this->view = $view;
        $this->context = $context;
        $this->child = $child;
    }

    public function render()
    {
        $content = $this->load($this->view);

        return $this->parent ? $this->parent->render() : $content;
    }

    protected function fork($view, array $context = null, self $child = null)
    {
        return new static($this->engine, $this->core, $view, $context, $child);
    }

    protected function load($view, array $context = null)
    {
        $file = $this->engine->findView($view);

        if ($this->context) {
            extract($this->context);
        }

        if ($context) {
            extract($context);
        }

        ob_start();
        require $file;

        return ob_get_clean();
    }

    protected function layout($view, array $context = null)
    {
        $this->parent = $this->fork($view, $context, $this);
    }

    protected function parent($parentBlock = null)
    {
        if (!$block = $this->findOpenBlock()) {
            return;
        }

        $parentBlock || $parentBlock = $block;

        return $this->parentMarks[$block][$parentBlock][] = '%parent_'.microtime(true).'_parent%';
    }

    protected function block($block)
    {
        return isset($this->blocks[$block]) ? $this->blocks[$block] : null;
    }

    protected function applyParentBlock($block, $content)
    {
        $subject = isset($this->blocks[$block]) ? $this->blocks[$block] : $content;

        if (isset($this->parentMarks[$block])) {
            foreach ($this->parentMarks[$block] as $parentBlock => $search) {
                if (null !== $replace = $this->parent->block($parentBlock)) {
                    $subject = str_replace($search, $replace, $subject);
                }
            }
        }

        return $subject;
    }

    protected function start($block)
    {
        $this->blocks[$block] = null;
        ob_start();
    }

    protected function stop()
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

    protected function findOpenBlock()
    {
        foreach (array_reverse($this->blocks) as $block => $content) {
            if (null === $content) {
                return $block;
            }
        }
    }

    public function __get($service)
    {
        return $this->core->service($service);
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->engine, $method], $arguments);
    }
}
