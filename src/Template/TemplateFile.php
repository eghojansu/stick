<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Template;

/**
 * Template file wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class TemplateFile
{
    const CLEAN_LEFT = 1;
    const CLEAN_RIGHT = 2;

    /**
     * Template instance.
     *
     * @var Template
     */
    protected $engine;

    /**
     * Template file path.
     *
     * @var string
     */
    protected $file;

    /**
     * Additional variables.
     *
     * @var string
     */
    protected $data;

    /**
     * Current template content.
     *
     * @var string
     */
    protected $content;

    /**
     * Parent template file path.
     *
     * @var string
     */
    protected $parent;

    /**
     * Current output buffering level.
     *
     * @var int
     */
    protected $level;

    /**
     * Current block information.
     *
     * @var array
     */
    protected $block;

    /**
     * Current blocks holder.
     *
     * @var array
     */
    protected $blocks = array();

    /**
     * Child blocks holder.
     *
     * @var array
     */
    protected $childBlocks;

    /**
     * Class constructor.
     *
     * @param Template   $engine
     * @param string     $file
     * @param array|null $data
     * @param array|null $childBlocks
     */
    public function __construct(Template $engine, $file, array $data = null, array $childBlocks = null)
    {
        $this->engine = $engine;
        $this->file = $file;
        $this->data = $data ?: array();
        $this->childBlocks = $childBlocks ?: array();
    }

    /**
     * Returns rendered template file.
     *
     * @return string
     */
    public function render()
    {
        $file = is_file($this->file) ? $this->file : $this->findFile();

        extract($this->engine->getApp()->hive());
        extract($this->data);
        ob_start();
        $this->level = ob_get_level();
        include $file;
        $this->content = ob_get_clean();

        return $this->finalizeOutput();
    }

    /**
     * Returns real output content.
     *
     * @return string
     */
    protected function finalizeOutput()
    {
        if ($this->parent) {
            $parent = new TemplateFile($this->engine, $this->parent, null, $this->blocks);

            return $parent->render();
        }

        return $this->content;
    }

    /**
     * Returns absoulte path of template file, otherwise throws exception.
     *
     * @return string
     *
     * @throws LogicException if template file not exists
     */
    protected function findFile()
    {
        foreach ($this->engine->getDirs() as $dir) {
            if (is_file($file = $dir.$this->file)) {
                return $file;
            }
        }

        throw new \LogicException('Template file not exists: "'.$this->file.'".');
    }

    /**
     * Close un-closed output buffer.
     */
    protected function closeBuffer()
    {
        while (ob_get_level() >= $this->level) {
            ob_end_clean();
        }
    }

    /**
     * Returns rendered template file.
     *
     * @param string     $file
     * @param array|null $args
     *
     * @return string
     */
    protected function load($file, array $args = null)
    {
        $template = new TemplateFile($this->engine, $file, $args);

        return $template->render();
    }

    /**
     * Extend other template file.
     *
     * @param string $file
     *
     * @throws LogicException if a template try to extend twice
     * @throws LogicException if a template try to extend self
     */
    protected function extend($file)
    {
        if ($this->parent) {
            $this->closeBuffer();

            throw new \LogicException('A template could not have more than one parent.');
        }

        if ($file === $this->file) {
            $this->closeBuffer();

            throw new \LogicException('A template could not have self as parent.');
        }

        $this->parent = $file;
    }

    /**
     * Returns specified block content if any, otherwise returns defaults.
     *
     * @param string $blockName
     * @param string $default
     *
     * @return string
     */
    protected function section($blockName, $default = '')
    {
        if (isset($this->childBlocks[$blockName])) {
            return $this->childBlocks[$blockName];
        }

        return isset($this->blocks[$blockName]) ? $this->blocks[$blockName] : $default;
    }

    /**
     * Open block.
     *
     * @param string $blockName
     */
    protected function block($blockName)
    {
        $this->block = array($blockName);
        ob_start();
    }

    /**
     * Close block.
     *
     * @throws LogicException if no opened block
     * @throws LogicException if trying to nested block
     */
    protected function endBlock()
    {
        $level = ob_get_level();
        $content = ob_get_clean();

        if (!$this->block) {
            $this->closeBuffer();

            throw new \LogicException('Please open block first!');
        }

        $nested = 1 < ($level - $this->level);

        if ($nested) {
            $this->closeBuffer();

            throw new \LogicException('Nested block is not supported.');
        }

        list($blockName) = $this->block;

        if (isset($this->childBlocks[$blockName])) {
            echo $this->childBlocks[$blockName];
        } else {
            $this->blocks[$blockName] = $content;

            if ($this->childBlocks) {
                echo $content;
            }
        }

        $this->block = null;
    }

    /**
     * Proxy to App::service.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    protected function service($serviceName)
    {
        return $this->engine->getApp()->service($serviceName);
    }

    /**
     * Proxy to Template::call.
     *
     * @param string $func
     * @param mixed  $args
     *
     * @return mixed
     */
    public function __call($func, $args)
    {
        return $this->engine->call($func, (array) $args);
    }

    /**
     * Alias of self::service.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function __get($serviceName)
    {
        return $this->service($serviceName);
    }
}
