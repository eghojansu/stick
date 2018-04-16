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

/**
 * Inspired by Plates (Native PHP Template - http://platesphp.com/)
 */
class TemplateEngine
{
    /** @var array */
    protected $globals = [];

    /** @var array */
    protected $dirs = [];

    /** @var string */
    protected $templateExtension = '.php';

    /** @var array */
    protected $funcs = [
        'upper' => 'strtoupper',
        'lower' => 'strtolower',
    ];

    /**
     * Class constructor
     *
     * @param string $template_dir Comma delimited dirs
     */
    public function __construct(string $template_dir)
    {
        $this->dirs = reqarr($template_dir);
    }

    /**
     * Add function alias
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return TemplateEngine
     */
    public function addFunction(string $name, callable $callback): TemplateEngine
    {
        $this->funcs[$name] = $callback;

        return $this;
    }

    /**
     * Add to global context var
     *
     * @param string $name
     * @param mixed  $val
     *
     * @return TemplateEngine
     */
    public function addGlobal(string $name, $val): TemplateEngine
    {
        $this->globals[$name] = $val;

        return $this;
    }

    /**
     * Add to global context var, massively
     *
     * @param array $data
     *
     * @return TemplateEngine
     */
    public function addGlobals(array $data): TemplateEngine
    {
        $this->globals = $data + $this->globals;

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
     * @return TemplateEngine
     */
    public function setTemplateExtension(string $templateExtension): TemplateEngine
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
        foreach (parse_expr($filters) as $call => $args) {
            array_unshift($args, $val);

            $val = $this->$call(...$args);
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
     * Make template
     *
     * @param  string $file
     * @param  array  $context
     *
     * @return Template
     *
     * @throws LogicException If view not found
     */
    public function make(string $file, array $context = []): Template
    {
        foreach ($this->dirs as $dir) {
            $view = $dir . $file . $this->templateExtension;

            if (file_exists($view)) {
                return new Template($this, $view, $context + $this->globals);
            }
        }

        throw new \LogicException('View file does not exists: ' . $file);
    }

    /**
     * Make and render template
     *
     * @param  string $file
     * @param  array  $context
     *
     * @return string
     */
    public function render(string $file, array $context = []): string
    {
        return $this->make($file, $context)->render();
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
        if (isset($this->funcs[$method])) {
            // registered function
            return call_user_func_array($this->funcs[$method], $args);
        } elseif (is_callable(__NAMESPACE__ . '\\' . $method)) {
            // try from library namespace
            return call_user_func_array(__NAMESPACE__ . '\\' . $method, $args);
        } elseif (is_callable($method)) {
            // from globals
            return call_user_func_array($method, $args);
        }

        throw new \BadFunctionCallException('Call to undefined function ' . $method);
    }
}
