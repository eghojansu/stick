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
 * PHP Template engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Template
{
    const EVENT_BEFORE_RENDER = 'template_before_render';
    const EVENT_AFTER_RENDER = 'template_after_render';

    /**
     * @var Fw
     */
    private $fw;

    /**
     * Template directories.
     *
     * @var array
     */
    private $dirs;

    /**
     * @var bool
     */
    private $autoEscape;

    /**
     * Macro aliases.
     *
     * @var array
     */
    private $macros = array();

    /**
     * Function aliases.
     *
     * @var array
     */
    private $funcs = array();

    /**
     * Class constructor.
     *
     * @param Fw    $fw
     * @param array $dirs
     * @param bool  $escape
     */
    public function __construct(Fw $fw, array $dirs = null, bool $escape = true)
    {
        $this->fw = $fw;
        $this->autoEscape = $escape;
        $this->setDirs((array) $dirs);
    }

    /**
     * Returns template directories.
     *
     * @return array
     */
    public function getDirs(): array
    {
        return $this->dirs;
    }

    /**
     * Sets template directories, can be merged if needed.
     *
     * @param array $dirs
     * @param bool  $merge
     *
     * @return Template
     */
    public function setDirs(array $dirs, bool $merge = false): Template
    {
        $this->dirs = $merge ? array_merge($this->dirs, $dirs) : $dirs;

        return $this;
    }

    /**
     * Add function alias.
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return Template
     */
    public function addFunction(string $name, callable $callable): Template
    {
        $this->funcs[$name] = $callable;

        return $this;
    }

    /**
     * Add macro alias.
     *
     * @param string $name
     * @param string $path
     *
     * @return Template
     */
    public function addMacro(string $name, string $path): Template
    {
        $this->macros[$name] = $path;

        return $this;
    }

    /**
     * Set autoescape status.
     *
     * @param bool $autoEscape
     *
     * @return Template
     */
    public function setAutoEscape(bool $autoEscape): Template
    {
        $this->autoEscape = $autoEscape;

        return $this;
    }

    /**
     * Returns autoescape status.
     *
     * @return bool
     */
    public function isAutoEscape(): bool
    {
        return $this->autoEscape;
    }

    /**
     * Encode characters to equivalent HTML entities.
     *
     * @param mixed $arg
     *
     * @return mixed
     */
    public function esc($arg)
    {
        return $this->fw->recursive($arg, function ($val) {
            return is_string($val) ? $this->fw->encode($val) : $val;
        });
    }

    /**
     * Decode HTML entities to equivalent characters.
     *
     * @param mixed $arg
     *
     * @return mixed
     */
    public function raw($arg)
    {
        return $this->fw->recursive($arg, function ($val) {
            return is_string($val) ? $this->fw->decode($val) : $val;
        });
    }

    /**
     * Returns rendered macro.
     *
     * @param string     $macro
     * @param array|null $args
     *
     * @return string
     *
     * @throws LogicException If macro not exists
     */
    public function macro(string $macro, array $args = null): string
    {
        if (!$path = $this->findMacro($macro)) {
            throw new \LogicException('Macro not exists: "'.$macro.'".');
        }

        if ($args) {
            $keys = explode(',', 'arg'.implode(',arg', range(1, count($args))));
            $args = array_combine($keys, $args);
        }

        $template = new TemplateFile($this->fw, $this, $path, $args);

        return $template->render();
    }

    /**
     * Returns rendered template file.
     *
     * @param string     $file
     * @param array|null $data
     * @param string     $mime
     *
     * @return string
     */
    public function render(string $file, array $data = null, string $mime = 'text/html'): string
    {
        $mData = $data;
        $mMime = $mime;
        $prepend = null;
        $result = $this->fw->trigger(self::EVENT_BEFORE_RENDER, array($file, $data, $mime));

        if ($result) {
            if (is_string($result)) {
                $result = array($result);
            }

            if (is_array($result)) {
                list($prepend, $mData, $mMime) = $result + array(1 => $data, $mime);
            }
        }

        $template = new TemplateFile($this->fw, $this, $file, $mData);
        $content = $prepend.$template->render();
        $result = $this->fw->trigger(self::EVENT_AFTER_RENDER, array($content, $file, $mData, $mMime));

        if ($result) {
            if (is_string($result)) {
                $result = array($result);
            }

            if (is_array($result)) {
                list($append, $mMime) = $result + array(1 => $mime);

                $content .= $append;
            }
        }

        $this->fw->mset(array(
            'Content-Type' => $mMime,
            'Content-Length' => strlen($content),
        ), 'RESPONSE.');

        return $content;
    }

    /**
     * Returns macro path if macro file exists.
     *
     * @param string $macro
     *
     * @return string|null
     */
    private function findMacro(string $macro): ?string
    {
        $id = $this->macros[$macro] ?? $macro;

        foreach ($this->dirs as $dir) {
            if (is_file($dir.($path = 'macros/'.$id)) ||
                is_file($dir.($path = 'macros/'.$id.'.php'))) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Call registered function.
     *
     * @param string $func
     * @param array  $args
     *
     * @return mixed
     *
     * @throws BadFunctionCallException if function cannot be resolved
     */
    public function __call($func, $args)
    {
        $call = $func;

        if (isset($this->funcs[$func])) {
            $call = $this->funcs[$func];
        } elseif (method_exists($this->fw, $func)) {
            $call = array($this->fw, $func);
        } else {
            $call = array($this, 'macro');
            $args = array($func, $args);
        }

        return $call(...$args);
    }
}
