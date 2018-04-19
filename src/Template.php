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

class Template
{
    /** @var array */
    protected $context = [];

    /** @var array */
    protected $dirs = [];

    /** @var array Macro directory, relative to dirs */
    protected $macros = [];

    /** @var array Macro aliases */
    protected $aliases = [];

    /** @var string */
    protected $templateExtension = '.php';

    /** @var array */
    protected $funcs = [
        'upper' => 'strtoupper',
        'lower' => 'strtolower',
        'tr' => 'strtr',
    ];

    /**
     * Class constructor
     *
     * @param string $template_dir Comma delimited dirs
     * @param string $macro_dir Comma delimited macro dirs
     */
    public function __construct(string $template_dir, string $macro_dir = 'macros')
    {
        $this->dirs = reqarr($template_dir);
        $this->macros = reqarr($macro_dir);
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
     * Get from context
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->context[$name] ?? $default;
    }

    /**
     * Add to context
     *
     * @param string $name
     * @param mixed  $val
     *
     * @return Template
     */
    public function set(string $name, $val): Template
    {
        $this->context[$name] = $val;

        return $this;
    }

    /**
     * Push to context
     *
     * @param  string $name
     * @param  mixed $val
     *
     * @return Template
     */
    public function push(string $name, $val): Template
    {
        $ref =& $this->ref($name);
        $ref = (array) $ref;

        array_push($ref, $val);

        return $this;
    }

    /**
     * Pop from context
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function pop(string $name)
    {
        $ref =& $this->ref($name);
        $ref = (array) $ref;

        return array_pop($ref);
    }

    /**
     * Unshift to context
     *
     * @param  string $name
     * @param  mixed $val
     *
     * @return Template
     */
    public function unshift(string $name, $val): Template
    {
        $ref =& $this->ref($name);
        $ref = (array) $ref;

        array_unshift($ref, $val);

        return $this;
    }

    /**
     * Shift from context
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function shift(string $name)
    {
        $ref =& $this->ref($name);
        $ref = (array) $ref;

        return array_shift($ref);
    }

    /**
     * Merge to context
     *
     * @param  string $name
     * @param  array  $val
     *
     * @return Template
     */
    public function merge(string $name, array $val): Template
    {
        $ref =& $this->ref($name);
        $ref = (array) $ref;
        $ref = array_merge($ref, $val);

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
        foreach (parse_expr($filters) as $callable => $args) {
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
    public function esc(string $filter, string $filters = null): string
    {
        $rule = $filters . ($filters ? '|' : '') . 'htmlspecialchars';

        return $this->filter($filter, $rule);
    }

    /**
     * Esc alias
     *
     * @param  string      $filter
     * @param  string|null $filters
     *
     * @return string
     */
    public function e(string $filter, string $filters = null): string
    {
        return $this->esc($filter, $filters);
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
    public function hasMacro(string $file, string &$realpath = null): bool
    {
        foreach ($this->macros as $rel) {
            if (
                $this->exists($rel . '/' . $file, $macro)
                || $this->exists($rel . '/' . ($this->aliases[$file] ?? $file), $macro)
            ) {
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
    protected function sandbox(string $file, array $context = []): string
    {
        extract($this->context);
        extract($context);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Get context ref
     *
     * @param  string $name
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function &ref(string $name, $default = null)
    {
        if (!isset($this->context[$name])) {
            $this->context[$name] = $default;
        }

        $ref =& $this->context[$name];

        return $ref;
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
    public function __call($method, $args)
    {
        $custom = $this->funcs[$method] ?? null;
        $lib = __NAMESPACE__ . '\\' . $method;

        if ($custom) {
            // registered function
            return $custom(...$args);
        } elseif (is_callable($lib)) {
            // try from library namespace
            return $lib(...$args);
        } elseif (is_callable($method)) {
            // from globals
            return $method(...$args);
        } elseif ($this->hasMacro($method, $filepath)) {
            // how use the macro is up to the creator
            // we only treat provided arguments with following logic:
            // call macro like other function call, ex: $this->macroname($arg1, $arg2, ...$argN)
            //   we map args into ['arg1'=>$arg1,'arg2'=>$arg2,...'argN'=>$argN]
            //   (the "arg" will be constant)

            $use = [];
            foreach ($args as $key => $value) {
                $use['arg' . ($key + 1)] = $value;
            }

            return trim($this->render($filepath, $use));
        }

        throw new \BadFunctionCallException('Call to undefined function ' . $method);
    }
}
