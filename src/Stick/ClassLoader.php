<?php

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
 * In case you cannot use composer autoloader, use this as class loader.
 *
 * Base code ported from composer autoloader.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ClassLoader
{
    /**
     * @var array
     */
    protected $fallback;

    /**
     * @var array
     */
    protected $namespaces;

    /**
     * @var array
     */
    protected $missingClasses = array();

    /**
     * Create class instance.
     *
     * @param array|null $namespaces
     * @param array|null $fallback
     *
     * @return ClassLoader
     */
    public static function create(array $namespaces = null, array $fallback = null): ClassLoader
    {
        return new static($namespaces, $fallback);
    }

    /**
     * Class constructor.
     *
     * @param array|null $namespaces
     * @param array|null $fallback
     */
    public function __construct(array $namespaces = null, array $fallback = null)
    {
        $this->setNamespaces($namespaces ?? array(), true);
        $this->setFallback($fallback ?? array());
    }

    /**
     * Returns fallback directories.
     *
     * @return array
     */
    public function getFallback(): array
    {
        return $this->fallback;
    }

    /**
     * Assign fallback directories.
     *
     * @param array $fallback
     *
     * @return ClassLoader
     */
    public function setFallback(array $fallback): ClassLoader
    {
        $this->fallback = array();

        foreach ($fallback as $directory) {
            $this->fallback[] = rtrim($directory, '\\/');
        }

        return $this;
    }

    /**
     * Returns namespaces.
     *
     * @return array
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Add namespace.
     *
     * @param string       $namespace
     * @param string|array $directories
     *
     * @return ClassLoader
     */
    public function addNamespace(string $namespace, $directories): ClassLoader
    {
        if ('\\' !== substr($namespace, -1)) {
            throw new \LogicException('Namespace should ends with "\\".');
        }

        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = array();
        }

        if (is_array($directories)) {
            foreach ($directories as $directory) {
                $this->namespaces[$namespace][] = rtrim($directory, '\\/');
            }
        } else {
            $this->namespaces[$namespace][] = rtrim($directories, '\\/');
        }

        return $this;
    }

    /**
     * Assign namespaces.
     *
     * @param array $namespaces
     * @param bool  $reset
     *
     * @return ClassLoader
     */
    public function setNamespaces(array $namespaces, bool $reset = false): ClassLoader
    {
        if ($reset) {
            $this->namespaces = array('Fal\\Stick\\' => array(__DIR__));
        }

        foreach ($namespaces as $namespace => $directories) {
            $this->addNamespace($namespace, $directories);
        }

        return $this;
    }

    /**
     * Register class loader.
     *
     * @param bool $prepend
     *
     * @return ClassLoader
     */
    public function register(bool $prepend = false): ClassLoader
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);

        return $this;
    }

    /**
     * Unregister class loader.
     *
     * @return ClassLoader
     */
    public function unregister(): ClassLoader
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Find class file.
     *
     * @param string $class
     *
     * @return string|null
     */
    public function findClass($class): ?string
    {
        if (isset($this->missingClasses[$class])) {
            return null;
        }

        if (!$file = $this->findFileWithExtensions($class, array('.php', '.hh'))) {
            // Remember that this class does not exist.
            $this->missingClasses[$class] = true;
        }

        return $file;
    }

    /**
     * Load class file.
     *
     * @param string $class
     *
     * @return true|null
     */
    public function loadClass($class)
    {
        if ($file = $this->findClass($class)) {
            require $file;

            return true;
        }
    }

    /**
     * Find file class with extension.
     *
     * @param string $class
     * @param array  $extensions
     *
     * @return string|null
     */
    protected function findFileWithExtensions(string $class, array $extensions): ?string
    {
        // PSR-4 lookup
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR);
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($this->namespaces[$search])) {
                $pathEnd = DIRECTORY_SEPARATOR.substr($logicalPath, $lastPos + 1);

                foreach ($this->namespaces[$search] as $directory) {
                    foreach ($extensions as $extension) {
                        if (is_file($file = $directory.$pathEnd.$extension)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallback as $directory) {
            foreach ($extensions as $extension) {
                if (file_exists($file = $directory.DIRECTORY_SEPARATOR.$logicalPath.$extension)) {
                    return $file;
                }
            }
        }

        return null;
    }
}
