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

namespace Fal\Stick\Container;

use Fal\Stick\Util;

/**
 * Service container and parameters.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $definitions = array();

    /**
     * @var array
     */
    protected $aliases = array();

    /**
     * @var array
     */
    protected $services = array();

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * Class constructor.
     *
     * @param array|null $definitions
     * @param array|null $parameters
     */
    public function __construct(array $definitions = null, array $parameters = null)
    {
        foreach ($definitions ?? array() as $key => $value) {
            $this->set($key, $value);
        }

        foreach ($parameters ?? array() as $key => $value) {
            $this->setParameter($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function grab(string $expression)
    {
        if (2 === count($parts = explode('->', $expression))) {
            return array($this->get($parts[0]), $parts[1]);
        }

        if (2 === count($parts = explode('::', $expression))) {
            return $parts;
        }

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function call(callable $callback, array $arguments = null)
    {
        return $callback(...$this->resolveArguments($callback, $arguments));
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $class): bool
    {
        return isset($this->definitions[$class]) || isset($this->services[$class]) || false !== array_search($class, $this->aliases);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $class, array $arguments = null)
    {
        if ('container' === $class || 'Fal\\Stick\\Container\\ContainerInterface' === $class) {
            return $this;
        }

        if (null === $arguments && isset($this->services[$class])) {
            return $this->services[$class];
        }

        $id = $class;

        if ($alias = array_search($id, $this->aliases)) {
            if (null === $arguments && isset($this->services[$alias])) {
                return $this->services[$alias];
            }

            $id = $alias;
        }

        $definition = $this->definitions[$id] ?? new Definition($id, false);
        $instance = $this->createInstance($definition, $arguments);

        if (null === $arguments && $definition->isShared()) {
            $this->services[$id] = $instance;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $class, Definition $definition): ContainerInterface
    {
        unset($this->services[$class]);

        $this->definitions[$class] = $definition;

        if ($instance = $definition->getInstance()) {
            $this->services[$class] = $instance;
        }

        if ($class !== $useClass = $definition->getClass()) {
            $this->aliases[$class] = $useClass;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter(string $name): bool
    {
        $this->reference($name, false, $found);

        return $found;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter(string $name)
    {
        return $this->reference($name, false);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter(string $name, $value): ContainerInterface
    {
        $var = &$this->reference($name);
        $var = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Create class instance.
     *
     * @param Definition $definition
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function createInstance(Definition $definition, array $arguments = null)
    {
        $ref = new \ReflectionClass($definition->getUse() ?? $definition->getClass());

        if (!$ref->isInstantiable()) {
            throw new \LogicException(sprintf('Cannot instantiate %s (%s).', $ref->name, $definition->getClass()));
        }

        if (null === $arguments) {
            $arguments = $definition->getArguments();
        }

        if ($factory = $definition->getFactory()) {
            if (is_string($factory)) {
                $factory = $this->grab($factory);
            }

            $instance = $this->call($factory, array($arguments));

            if (!$instance instanceof $ref->name) {
                throw new \LogicException(sprintf('Factory should return instance of %s (%s).', $ref->name, $definition->getClass()));
            }
        } elseif ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArguments($ref->getMethod('__construct'), $arguments));
        } else {
            $instance = $ref->newInstance();
        }

        if ($boot = $definition->getBoot()) {
            if (is_string($boot)) {
                $boot = $this->grab($boot);
            }

            $this->call($boot, array($instance, $this));
        }

        return $instance;
    }

    /**
     * Returns parameters reference.
     *
     * @param string     $key
     * @param bool       $add
     * @param bool|null  &$found
     * @param array|null &$var
     *
     * @return mixed
     */
    public function &reference(string $key, bool $add = true, bool &$found = null, array &$var = null)
    {
        if (null === $var) {
            if ($add) {
                $var = &$this->parameters;
            } else {
                $var = $this->parameters;
            }
        }

        foreach (Util::split($key, '.') as $part) {
            if (null === $var || is_scalar($var)) {
                $var = array();
                $found = false;
            }

            if (is_array($var) && ($add || $exists = array_key_exists($part, $var))) {
                $found = isset($exists) && $exists;
                $var = &$var[$part];
            } elseif (is_object($var) && ($add || $exists = property_exists($var, $part))) {
                $found = isset($exists) && $exists;
                $var = &$var->$part;
            } else {
                $found = false;
                $var = null;
                break;
            }

            unset($exists);
        }

        return $var;
    }

    /**
     * Resolve callback arguments.
     *
     * @param string     $callable
     * @param array|null $arguments
     *
     * @return array
     */
    protected function resolveArguments($callable, array $arguments = null): array
    {
        if ($callable instanceof \ReflectionFunctionAbstract) {
            $reflection = $callable;
        } elseif (is_array($callable)) {
            $reflection = new \ReflectionMethod(reset($callable), next($callable));
        } else {
            $reflection = new \ReflectionFunction($callable);
        }

        if (0 === $reflection->getNumberOfParameters()) {
            return array();
        }

        if (null === $arguments) {
            $arguments = array();
        }

        $resolved = array();

        foreach ($reflection->getParameters() as $parameter) {
            $value = null;

            if (null !== ($key = key($arguments))) {
                if (is_string($key) && $key !== $parameter->name) {
                    $key = null;
                } else {
                    $value = $arguments[$key];
                }
            }

            if ($class = $parameter->getClass()) {
                if ($value instanceof $class->name) {
                    $resolved[] = $value;
                } elseif (is_string($value) && is_object($object = $this->resolveArgument($value, true))) {
                    $resolved[] = $object;
                } else {
                    $resolved[] = $this->get($class->name);

                    continue;
                }
            } elseif (is_string($value)) {
                $resolved[] = $this->resolveArgument($value);
            } elseif (null === $key) {
                if ($parameter->isDefaultValueAvailable()) {
                    $resolved[] = $parameter->getDefaultValue();
                }

                continue;
            } else {
                $resolved[] = is_array($value) ? $this->resolveArgumentRecursive($value) : $value;
            }

            unset($arguments[$key]);
        }

        foreach ($arguments as $value) {
            $resolved[] = $value;
        }

        return $resolved;
    }

    /**
     * Resolve argument value.
     *
     * This method can *TRIGGER ENDLESS LOOPING* if parameter not configured properly
     *
     * @param string $value
     * @param bool   $resolveClass
     *
     * @return mixed
     */
    protected function resolveArgument(string $value, bool $resolveClass = false)
    {
        if ($resolveClass && class_exists($value)) {
            return $this->get($value);
        }

        $match = $this->resolveArgumentExpression($value);

        while ($match) {
            list($value, $prefix, $suffix) = $match;

            if ($this->hasParameter($value)) {
                $value = $this->getParameter($value);
            } elseif (count($parts = Util::split($value, '.')) > 1 && is_object($obj = $this->get($prev = array_shift($parts)))) {
                $value = $this->resolveObjectExpression($obj, $parts, $prev);
            } else {
                return $this->get($value);
            }

            if (is_string($value) && $match = $this->resolveArgumentExpression($value)) {
                continue;
            }

            if (is_array($value)) {
                return $this->resolveArgumentRecursive($value);
            }

            if (is_scalar($value) && ($prefix || $suffix)) {
                return $prefix.$value.$suffix;
            }

            return $value;
        }

        return $value;
    }

    /**
     * Resolve object expression.
     *
     * @param object $object
     * @param array  $parts
     * @param string $prev
     *
     * @return mixed
     */
    protected function resolveObjectExpression($object, array $parts, string $prev)
    {
        foreach ($parts as $key => $part) {
            if (!is_object($object)) {
                throw new \LogicException(sprintf('Previous part is not an object (%s).', $prev));
            }

            if (is_callable($cb = array($object, 'get'.$part)) || is_callable($cb = array($object, 'is'.$part))) {
                $object = $cb();
            } elseif (property_exists($object, $part)) {
                $ref = new \ReflectionProperty($object, $part);

                if ($ref->isProtected() || $ref->isPrivate()) {
                    throw new \LogicException(sprintf('Cannot resolve private/protected object property (%s->%s).', get_class($object), $part));
                }

                $object = $object->$part;
            } else {
                throw new \LogicException(sprintf('Cannot resolve object property (%s->%s).', get_class($object), $part));
            }

            $prev .= '.'.$part;
        }

        return $object;
    }

    /**
     * Ensure array arguments get resolved properly.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function resolveArgumentRecursive(array $arguments): array
    {
        foreach ($arguments as $key => $value) {
            $arguments[$key] = is_string($value) ? $this->resolveArgument($value) : $value;
        }

        return $arguments;
    }

    /**
     * Resolve argument string expression.
     *
     * @param string $expression
     *
     * @return array|null
     */
    protected function resolveArgumentExpression(string $expression): ?array
    {
        if (preg_match('/^(.+)?%([.\w\\\\]+)%(.+)?$/', $expression, $match)) {
            return array(
                $match[2],
                $match[1] ?? null,
                $match[3] ?? null,
            );
        }

        return null;
    }
}
