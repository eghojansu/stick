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
    private $funcs = array(
        'e' => 'htmlspecialchars',
    );

    /**
     * Class constructor.
     *
     * @param Fw    $fw
     * @param array $dirs
     */
    public function __construct(Fw $fw, array $dirs = null)
    {
        $this->fw = $fw;
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
     * Call registered function.
     *
     * @param string $func
     * @param mixed  $args
     *
     * @return mixed
     *
     * @throws BadFunctionCallException if function cannot be resolved
     */
    public function call(string $func, array $args = null)
    {
        $call = $func;
        $mArgs = (array) $args;

        if (isset($this->funcs[$func])) {
            $call = $this->funcs[$func];
        } elseif ('macro' === $func) {
            $call = array($this, 'macro');
        } elseif (method_exists($this->fw, $func)) {
            $call = array($this->fw, $func);
        } elseif ($macro = $this->findMacro($func)) {
            $call = array($this, 'macro');
            $mArgs = array($macro, $args);
        } else {
            throw new \BadFunctionCallException('Call to undefined function '.$func.'.');
        }

        return $call(...$mArgs);
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
     * Returns rendered macro.
     *
     * @param string     $macro
     * @param array|null $args
     *
     * @return string
     *
     * @throws LogicException If macro not exists
     */
    private function macro(string $macro, array $args = null): string
    {
        $realpath = is_file($macro) ? $macro : $this->findMacro($macro);

        if (!$realpath) {
            throw new \LogicException('Macro not exists: "'.$macro.'".');
        }

        if ($args) {
            $keys = explode(',', 'arg'.implode(',arg', range(1, count($args))));
            $args = array_combine($keys, $args);
        }

        $template = new TemplateFile($this->fw, $this, $realpath, $args);

        return $template->render();
    }

    /**
     * Returns true if macro file exists.
     *
     * @param string $macro
     *
     * @return string|null
     */
    private function findMacro(string $macro): ?string
    {
        $id = $this->macros[$macro] ?? $macro;

        if (is_file($id)) {
            return $id;
        }

        foreach ($this->dirs as $dir) {
            if (is_file($file = $dir.'macros/'.$id) ||
                is_file($file = $dir.'macros/'.$id.'.php')) {
                return $file;
            }
        }

        return null;
    }
}
