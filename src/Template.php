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
    private $maliases = [];

    /** @var string */
    private $templateExtension = '.php';

    /** @var array */
    private $aliases = [
        'esc' => 'e',
    ];

    /** @var array */
    private $funcs = [
        'upper' => 'strtoupper',
        'lower' => 'strtolower',
    ];

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
    }

    public function exists($offset): bool
    {
        return isset($this->context[$offset]);
    }

    public function &get($offset)
    {
        if (isset($this->context[$offset])) {
            return $this->context[$offset];
        }

        $null = null;
        $ref =& $null;

        return $ref;
    }

    public function set($offset, $value): Template
    {
        $this->context[$offset] = $value;

        return $this;
    }

    public function clear($offset): Template
    {
        unset($this->context[$offset]);

        return $this;
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
        $this->maliases = $aliases + $this->maliases;

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
            $val = $this->$callable($val, ...$args);
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
    public function viewExists(string $file, string &$realpath = null): bool
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
        $use = $this->maliases[$file] ?? $file;

        foreach ($this->macros as $rel) {
            if ($this->viewExists($rel . '/' . $use, $macro)) {
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
        if (file_exists($view = $file) || $this->viewExists($file, $view)) {
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
        } elseif (isset($this->aliases[$func])) {
            // call alias
            return call_user_func_array([$this, $this->aliases[$func]], $args);
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
        return $this->exists($offset);
    }

    public function &offsetGet($offset)
    {
        $ref =& $this->get($offset);

        return $ref;
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}
