<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
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
    protected $fallback = [];

    /**
     * @var array
     */
    protected $namespaces = [];

    /**
     * @var string
     */
    protected $apcuPrefix;

    /**
     * @var array
     */
    protected $missingClasses = [];

    public function __construct(array $namespaces = null, array $fallback = null, string $apcuPrefix = null)
    {
        null === $namespaces || $this->setNamespaces($namespaces);
        null === $fallback || $this->setFallback($fallback);
        null === $apcuPrefix || $this->setApcuPrefix($apcuPrefix);
    }

    public function getFallback()
    {
        return $this->fallback;
    }

    public function setFallback(array $fallback)
    {
        $this->fallback = [];

        foreach ($fallback as $directory) {
            $this->fallback[] = rtrim($directory, '\\/');
        }

        return $this;
    }

    public function getNamespaces()
    {
        return $this->namespaces;
    }

    public function addNamespace($namespace, $directories)
    {
        if ('\\' !== substr($namespace, -1)) {
            throw new \LogicException('Namespace should ends with "\\"');
        }

        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
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

    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = [];

        foreach ($namespaces as $namespace => $directories) {
            $this->addNamespace($namespace, $directories);
        }

        return $this;
    }

    /**
     * The APCu prefix in use, or null if APCu caching is not enabled.
     *
     * @return string|null
     */
    public function getApcuPrefix()
    {
        return $this->apcuPrefix;
    }

    /**
     * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
     *
     * @param string|null $apcuPrefix
     */
    public function setApcuPrefix($apcuPrefix = null)
    {
        $this->apcuPrefix = function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN) ? $apcuPrefix : null;

        return $this;
    }

    /**
     * Register class loader.
     *
     * @param bool $prepend
     *
     * @return ClassLoader
     */
    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);

        return $this;
    }

    /**
     * Unregister class loader.
     *
     * @return ClassLoader
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);

        return $this;
    }

    /**
     * Find class file.
     *
     * @param string $class
     *
     * @return string|null
     */
    public function findClass($class)
    {
        if (isset($this->missingClasses[$class])) {
            return;
        }

        if (null !== $this->apcuPrefix && ($file = apcu_fetch($this->apcuPrefix.$class, $hit)) && $hit) {
            return $file;
        }

        $file = $this->findFileWithExtension($class, '.php');

        // @codeCoverageIgnoreStart
        if (!$file && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }
        // @codeCoverageIgnoreEnd

        if (null !== $this->apcuPrefix) {
            apcu_add($this->apcuPrefix.$class, $file);
        }

        if (!$file) {
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
     * @param string $extension
     *
     * @return string|null
     */
    protected function findFileWithExtension($class, $extension)
    {
        // PSR-4 lookup
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR).$extension;
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($this->namespaces[$search])) {
                $pathEnd = DIRECTORY_SEPARATOR.substr($logicalPath, $lastPos + 1);

                foreach ($this->namespaces[$search] as $directory) {
                    if (is_file($file = $directory.$pathEnd)) {
                        return $file;
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallback as $directory) {
            if (file_exists($file = $directory.DIRECTORY_SEPARATOR.$logicalPath)) {
                return $file;
            }
        }
    }
}
