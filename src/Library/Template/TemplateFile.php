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

namespace Fal\Stick\Library\Template;

use Fal\Stick\Fw;

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
     * @var Fw
     */
    private $fw;

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
     * @var TemplateFile
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
     * @var string
     */
    protected $block;

    /**
     * Current blocks holder.
     *
     * @var array
     */
    protected $blocks = array();

    /**
     * Parent marks.
     *
     * @var array
     */
    protected $marks = array();

    /**
     * View realpath.
     *
     * @var string
     */
    protected $realpath;

    /**
     * Class constructor.
     *
     * @param Fw         $fw
     * @param Template   $engine
     * @param string     $file
     * @param array|null $data
     */
    public function __construct(Fw $fw, Template $engine, string $file, array $data = null)
    {
        $this->fw = $fw;
        $this->engine = $engine;
        $this->file = $file;
        $this->data = (array) $data;
        $this->realpath = $this->findFile();
    }

    /**
     * Returns rendered template file.
     *
     * @return string
     */
    public function render(): string
    {
        extract($this->data + array('HANDLERS' => null, 'ROUTES' => null, 'SERVICES' => null) + $this->fw->hive());
        ob_start();
        $this->level = ob_get_level();
        include $this->realpath;
        $this->content = ob_get_clean();

        return $this->finalizeOutput();
    }

    /**
     * Returns real output content.
     *
     * @return string
     */
    protected function finalizeOutput(): string
    {
        return $this->parent ? $this->parent->render() : $this->content;
    }

    /**
     * Returns absoulte path of template file, otherwise throws exception.
     *
     * @return string
     *
     * @throws LogicException if template file not exists
     */
    protected function findFile(): string
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
    protected function closeBuffer(): void
    {
        while (ob_get_level() >= $this->level) {
            ob_end_clean();
        }
    }

    /**
     * Returns rendered template file.
     *
     * @param string     $file
     * @param array|null $data
     *
     * @return string
     */
    protected function load(string $file, array $data = null): string
    {
        $template = new TemplateFile($this->fw, $this->engine, $file, ((array) $data) + $this->data);

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
    protected function extend(string $file): void
    {
        if ($this->parent) {
            $this->closeBuffer();

            throw new \LogicException('A template could not have more than one parent.');
        }

        if ($file === $this->file) {
            $this->closeBuffer();

            throw new \LogicException('A template could not have self as parent.');
        }

        $this->parent = new TemplateFile($this->fw, $this->engine, $file, $this->data);
    }

    /**
     * Returns parent mark.
     *
     * @return string
     */
    protected function parent(): string
    {
        if ($this->block && $this->parent) {
            return $this->parent->marks[$this->block][] = '*parent-'.$this->block.'-'.microtime(true).'*';
        }

        $this->closeBuffer();

        throw new \LogicException('Please open block first before call parent method!');
    }

    /**
     * Returns specified block content if any, otherwise returns defaults.
     *
     * @param string $blockName
     * @param string $default
     *
     * @return string
     */
    protected function section(string $blockName, string $default = ''): string
    {
        return $this->blocks[$blockName] ?? $default;
    }

    /**
     * Open block.
     *
     * @param string $blockName
     */
    protected function block(string $blockName): void
    {
        $this->block = $blockName;
        ob_start();
    }

    /**
     * Close block.
     *
     * @throws LogicException if no opened block
     * @throws LogicException if trying to nested block
     */
    protected function endBlock(): void
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

        $name = $this->block;

        if (isset($this->blocks[$name])) {
            if (isset($this->marks[$name])) {
                $this->blocks[$name] = str_replace($this->marks[$name], $content, $this->blocks[$name]);
            }

            echo $this->blocks[$name];
        } else {
            $this->blocks[$name] = $content;

            if ($this->parent) {
                $this->parent->blocks[$name] = $content;
            }

            echo $content;
        }

        $this->block = null;
    }

    /**
     * Proxy to Fw::service.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    protected function service(string $serviceName)
    {
        return $this->fw->service($serviceName);
    }

    /**
     * Proxy to Template::call.
     *
     * @param string $func
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($func, $args)
    {
        return $this->engine->$func(...$args);
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
