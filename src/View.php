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

namespace Ekok\Stick;

/**
 * PHP Template engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class View implements \ArrayAccess
{
    protected $fw;
    protected $directories;
    protected $extension;
    protected $layout;
    protected $sections;
    protected $tree = array();

    /**
     * @param mixed $directories
     */
    public function __construct(Fw $fw, $directories, string $extension = 'html')
    {
        $this->fw = $fw;
        $this->setDirectories($directories);
        $this->setExtension($extension);
    }

    public function __get($key)
    {
        return $this->fw->get($key);
    }

    public function __call($method, $arguments)
    {
        return $this->fw->{$method}(...$arguments);
    }

    public function __invoke(...$arguments)
    {
        $method = array_shift($arguments);

        if (method_exists($this, $method)) {
            return $this->{$method}(...$arguments);
        }

        return $this->fw->{$method}(...$arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->fw->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function &offsetGet($key)
    {
        $ref = &$this->fw->get($key);

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->fw->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->fw->rem($key);
    }

    public function esc(string $text): string
    {
        return $this->fw->encode($text);
    }

    public function raw(string $text): string
    {
        return $this->fw->decode($text);
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function addDirectory(string $directory): View
    {
        $this->directories[] = Fw::fixSlashes($directory);

        return $this;
    }

    public function setDirectories(string $directories): View
    {
        $this->directories = array_map(Fw::class.'::fixSlashes', Fw::split($directories));

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): View
    {
        $this->extension = $extension;

        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    /**
     * Set view layout.
     */
    public function layout(?string $layout): View
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Returns section content.
     */
    public function section(string $sectionName): ?string
    {
        return $this->sections[$sectionName] ?? null;
    }

    /**
     * Section start.
     */
    public function start(string $sectionName): View
    {
        $this->tree[] = $sectionName;
        ob_start();

        return $this;
    }

    /**
     * Section stop buffering.
     */
    public function stop(): View
    {
        $sectionName = array_pop($this->tree);

        if (!$sectionName) {
            throw new \LogicException('No opening section.');
        }

        $this->sections[$sectionName] = ob_get_clean();

        return $this;
    }

    /**
     * Returns absolute view path.
     */
    public function findView(string $view): string
    {
        $ext = '.'.$this->extension;

        foreach ($this->directories as $directory) {
            if (
                file_exists($file = $directory.$view)
                || file_exists($file = $directory.$view.$ext)
                || file_exists($file = $directory.strtr($view, '.', '/').$ext)
            ) {
                return $file;
            }
        }

        throw new \LogicException("View not found: '{$view}'.");
    }

    /**
     * Load view.
     */
    public function load(string $view, array $data = null): string
    {
        $level = ob_get_level();

        try {
            $data['_'] = $this;
            $file = $this->findView($view);
            $load = $this->createLoader();

            return $load($file, $data);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    /**
     * Render view with layout if any.
     */
    public function render(string $view, array $data = null): string
    {
        $layout = $this->layout;
        $content = $this->load($view, $data);

        if ($this->layout) {
            $data['_content'] = $content;
            $content = $this->load($this->layout, $data);

            $this->layout = $layout;
        }

        return $content;
    }

    protected function createLoader(): callable
    {
        return static function () {
            ob_start();
            extract(func_get_arg(1));
            require func_get_arg(0);

            return ob_get_clean();
        };
    }
}
