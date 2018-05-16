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

final class Template implements \ArrayAccess
{
    /** @var array */
    private $context = [];

    /** @var array */
    private $dirs = [];

    /** @var array Macro directory, relative to dirs */
    private $macros = [];

    /** @var array Macro aliases */
    private $aliases = [];

    /** @var string */
    private $templateExtension = '.php';

    /** @var array */
    private $funcs;

    /**
     * Class constructor
     *
     * @param string $dirs Comma delimited dirs
     * @param string $macros Comma delimited macro dirs
     */
    public function __construct(string $dirs, string $macros = 'macros')
    {
        $this->dirs = Helper::reqarr($dirs);
        $this->macros = Helper::reqarr($macros);
        $this->funcs = [
            'upper' => 'strtoupper',
            'lower' => 'strtolower',
            'esc' => [$this, 'e'],
        ];
    }

    /**
     * Add function alias
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return Template
     */
    public function addFunction(string $name, callable $callback): Template
    {
        $this->funcs[$name] = $callback;

        return $this;
    }

    /**
     * Set macro aliases
     *
     * @param array $aliases
     *
     * @return Template
     */
    public function setMacroAliases(array $aliases): Template
    {
        $this->aliases = $aliases + $this->aliases;

        return $this;
    }

    /**
     * Get templateExtension
     *
     * @return string
     */
    public function getTemplateExtension(): string
    {
        return $this->templateExtension;
    }

    /**
     * Set templateExtension
     *
     * @param string $templateExtension
     * @return Template
     */
    public function setTemplateExtension(string $templateExtension): Template
    {
        $this->templateExtension = $templateExtension;

        return $this;
    }

    /**
     * Call function in sequences
     *
     * @param  mixed  $val
     * @param  string $filters
     *
     * @return mixed
     */
    public function filter($val, string $filters)
    {
        foreach (Helper::parsexpr($filters) as $callable => $args) {
            array_unshift($args, $val);

            $val = $this->$callable(...$args);
        }

        return $val;
    }

    /**
     * Escape variable
     *
     * @param  string      $filter
     * @param  string|null $filters
     *
     * @return string
     */
    public function e(string $filter, string $filters = null): string
    {
        $rule = $filters . ($filters ? '|' : '') . 'htmlspecialchars';

        return $this->filter($filter, $rule);
    }

    /**
     * Check if view file is exists
     *
     * @param  string      $file
     * @param  string|null &$realpath
     *
     * @return bool
     */
    public function exists(string $file, string &$realpath = null): bool
    {
        foreach ($this->dirs as $dir) {
            $view = $dir . $file . $this->templateExtension;

            if (file_exists($view)) {
                $realpath = $view;

                return true;
            }
        }

        return false;
    }

    /**
     * Check if macro file is exists
     *
     * @param  string      $file
     * @param  string|null &$realpath
     *
     * @return bool
     */
    public function macroExists(string $file, string &$realpath = null): bool
    {
        $use = $this->aliases[$file] ?? $file;

        foreach ($this->macros as $rel) {
            if ($this->exists($rel . '/' . $use, $macro)) {
                $realpath = $macro;

                return true;
            }
        }

        return false;
    }

    /**
     * Render template
     *
     * @param  string      $file    Relative or realpath
     * @param  array|null  $context
     *
     * @return string
     *
     * @throws LogicException If view not found
     */
    public function render(string $file, array $context = null): string
    {
        if (file_exists($view = $file) || $this->exists($file, $view)) {
            return $this->sandbox($view, $context ?? []);
        }

        throw new \LogicException('View file does not exists: ' . $file);
    }

    /**
     * Render file with trim option
     *
     * @param  string     $file
     * @param  array|null $context
     * @param  int        $mode
     *
     * @return string
     */
    public function include(string $file, array $context = null, int $mode = 0): string
    {
        $rule = [1 => 'ltrim', 'rtrim', 'trim'];
        $use = $rule[$mode] ?? null;
        $res = $this->render($file, $context);

        return $use ? $use($res) : $res;
    }

    /**
     * Include file in this box
     *
     * @param  string $file
     * @param  array  $context
     *
     * @return string
     */
    private function sandbox(string $file, array $context = []): string
    {
        extract($this->context);
        extract($context);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Call registered function
     *
     * @param  string $func
     * @param  mixed $args
     *
     * @return mixed
     *
     * @throws BadFunctionCallException
     */
    public function __call($func, $args)
    {
        $custom = $this->funcs[$func] ?? null;
        $lib = __NAMESPACE__ . '\\' . $func;

        if (isset($this->funcs[$func])) {
            // registered function
            return call_user_func_array($this->funcs[$func], $args);
        } elseif (is_callable($lib = Helper::class . '::' . $func)) {
            // try from library namespace (helper)
            return call_user_func_array($lib, $args);
        } elseif (is_callable($func)) {
            // from globals
            return call_user_func_array($func, $args);
        } elseif ($this->macroExists($func, $filepath)) {
            // how use the macro is up to the creator
            // we only treat provided arguments with following logic:
            // call macro like other function call, ex: $this->macroname($arg1, $arg2, ...$argN)
            //   we map args into ['arg1'=>$arg1,'arg2'=>$arg2,...'argN'=>$argN]
            //   (the "arg" will be constant)

            if ($args) {
                $keys = explode('|', 'arg' . implode('|arg', range(1, count($args))));
                $args = array_combine($keys, $args);
            }

            return trim($this->render($filepath, $args));
        }

        throw new \BadFunctionCallException('Call to undefined function ' . $func);
    }

    public function offsetExists($offset)
    {
        return isset($this->context[$offset]);
    }

    public function &offsetGet($offset)
    {
        if (isset($this->context[$offset])) {
            return $this->context[$offset];
        }

        $null = null;
        $ref =& $null;

        return $ref;
    }

    public function offsetSet($offset, $value)
    {
        $this->context[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->context[$offset]);
    }
}
