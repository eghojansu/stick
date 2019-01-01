<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 01, 2019 06:02
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
     * @var string
     */
    protected $apcuPrefix;

    /**
     * @var array
     */
    protected $missingClasses = array();

    public function __construct(array $namespaces = null, array $fallback = null, string $apcuPrefix = null)
    {
        $this->setNamespaces($namespaces ?? array(), true);
        $this->setFallback($fallback ?? array());
        $this->setApcuPrefix($apcuPrefix);

        // Load first class citizen
        class_exists('Fal\\Stick\\Core') || include __DIR__.'/Core.php';
    }

    public function getFallback(): array
    {
        return $this->fallback;
    }

    public function setFallback(array $fallback): ClassLoader
    {
        $this->fallback = array();

        foreach ($fallback as $directory) {
            $this->fallback[] = rtrim($directory, '\\/');
        }

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function addNamespace(string $namespace, $directories): ClassLoader
    {
        if ('\\' !== substr($namespace, -1)) {
            throw new \LogicException('Namespace should ends with "\\"');
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
     * The APCu prefix in use, or null if APCu caching is not enabled.
     *
     * @return string|null
     */
    public function getApcuPrefix(): ?string
    {
        return $this->apcuPrefix;
    }

    /**
     * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
     *
     * @param string|null $apcuPrefix
     */
    public function setApcuPrefix(string $apcuPrefix = null): ClassLoader
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

        if (null !== $this->apcuPrefix && ($file = apcu_fetch($this->apcuPrefix.$class, $hit)) && $hit) {
            return $file;
        }

        // @codeCoverageIgnoreStart
        if ((!$file = $this->findFileWithExtension($class, '.php')) && defined('HHVM_VERSION')) {
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
            includeFile($file);

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
    private function findFileWithExtension(string $class, string $extension): ?string
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

        return null;
    }
}
