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

use Fal\Stick\App;

/**
 * PHP Template engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Template
{
    // events
    const EVENT_RENDER = 'template_render';
    const EVENT_AFTER_RENDER = 'template_after_render';

    /**
     * @var App
     */
    private $app;

    /**
     * Template directories.
     *
     * @var array
     */
    private $dirs;

    /**
     * Function aliases.
     *
     * @var array
     */
    private $funcs = array(
        'upper' => 'strtoupper',
        'lower' => 'strtolower',
    );

    /**
     * Macro aliases.
     *
     * @var array
     */
    private $macros = array();

    /**
     * Class constructor.
     *
     * @param App          $app
     * @param string|array $dirs
     */
    public function __construct(App $app, $dirs = './template/')
    {
        $this->app = $app;
        $this->setDirs($dirs);
    }

    /**
     * Returns template directories.
     *
     * @return array
     */
    public function getDirs()
    {
        return $this->dirs;
    }

    /**
     * Sets template directories, can be merged if needed.
     *
     * @param string|array $dirs
     * @param bool         $merge
     *
     * @return Template
     */
    public function setDirs($dirs, $merge = false)
    {
        $newDirs = App::arr($dirs);
        $this->dirs = $merge ? array_merge($newDirs, $this->dirs) : $newDirs;

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
    public function addFunction($name, $callable)
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
    public function addMacro($name, $path)
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
    public function call($func, array $args = null)
    {
        if (isset($this->funcs[$func])) {
            $call = $this->funcs[$func];
        } elseif (in_array(strtolower($func), array('e', 'filter', 'macro'))) {
            $call = array($this, '_'.$func);
        } elseif (method_exists($this->app, $func)) {
            $call = array($this->app, $func);
        } elseif (is_callable($func)) {
            $call = $func;
        } elseif ($macro = $this->findMacro($func)) {
            $call = array($this, '_macro');
            $args = array($macro, $args);
        } else {
            throw new \BadFunctionCallException('Call to undefined function "'.$func.'".');
        }

        return call_user_func_array($call, (array) $args);
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
    public function render($file, array $data = null, $mime = 'text/html')
    {
        $event = new TemplateEvent($file, $data, $mime);
        $this->app->trigger(self::EVENT_RENDER, $event);

        if ($event->isPropagationStopped()) {
            $content = $event->getContent();
        } else {
            $template = new TemplateFile($this->app, $this, $file, $event->getData());
            $content = $template->render();

            $this->app->set('HEADERS.Content-Type', $mime);
            $this->app->set('HEADERS.Content-Length', strlen($content));
        }

        $event = new TemplateEvent($file, $event->getData(), $mime, $content);
        $this->app->trigger(self::EVENT_AFTER_RENDER, $event);

        return $event->getContent();
    }

    /**
     * Call function in sequences.
     *
     * @param mixed  $val
     * @param string $filters
     *
     * @return mixed
     */
    private function _filter($val, $filters)
    {
        foreach (App::parseExpr($filters) as $callable => $args) {
            $cArgs = array_merge(array($val), $args);
            $val = $this->call($callable, $cArgs);
        }

        return $val;
    }

    /**
     * Escape variable.
     *
     * @param string      $val
     * @param string|null $filters
     *
     * @return string
     */
    private function _e($val, $filters = null)
    {
        return $this->_filter($val ?: '', ltrim($filters.'|htmlspecialchars', '|'));
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
    private function _macro($macro, array $args = null)
    {
        $realpath = is_file($macro) ? $macro : $this->findMacro($macro);

        App::throws(!$realpath, 'Macro not exists: "'.$macro.'".');

        if ($args) {
            $keys = explode(',', 'arg'.implode(',arg', range(1, count($args))));
            $args = array_combine($keys, $args);
        }

        $template = new TemplateFile($this->app, $this, $realpath, $args);

        return $template->render();
    }

    /**
     * Returns true if macro file exists.
     *
     * @param string $macro
     *
     * @return string|null
     */
    private function findMacro($macro)
    {
        $id = isset($this->macros[$macro]) ? $this->macros[$macro] : $macro;

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
