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

namespace Fal\Stick\Web\Router;

use Fal\Stick\Util;
use Fal\Stick\Web\Request;

/**
 * Router.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Router implements RouterInterface
{
    const PATTERN_PARAMETER = '~(?:@(\w+))(?:(\*$)|(?:\(([^\)]+)\)))?~';

    /**
     * @var bool
     */
    protected $caseless;

    /**
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @var array
     */
    protected $routes = array();

    /**
     * @var array
     */
    protected $aliases = array();

    /**
     * Class constructor.
     *
     * @param bool $caseless
     */
    public function __construct(bool $caseless = true)
    {
        $this->caseless = $caseless;
    }

    /**
     * {@inheritdoc}
     */
    public function route(string $route, $controller): RouterInterface
    {
        $allowedMode = Request::AJAX.'|'.Request::SYNC;
        $rule = '~^([\w+|]+)(?:\h+(\w+))?(?:\h+(/[^\h]*))?(?:\h+('.$allowedMode.'))?$~';

        preg_match($rule, trim($route), $match, PREG_UNMATCHED_AS_NULL);

        if (count($match) < 3) {
            throw new \LogicException(sprintf('Invalid route: "%s".', $route));
        }

        $alias = $match[2] ?? null;
        $pattern = $match[3] ?? null;
        $mode = $match[4] ?? Request::ALL;

        if (!$pattern) {
            if (!isset($this->aliases[$alias])) {
                throw new \LogicException(sprintf('Route not exists: "%s".', $alias));
            }

            $pattern = $this->aliases[$alias];
        }

        foreach (array_filter(explode('|', strtoupper($match[1]))) as $method) {
            $this->routes[$pattern][$mode][$method] = $controller;
        }

        if ($alias) {
            $this->aliases[$alias] = $pattern;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function controller(string $class, array $routes): RouterInterface
    {
        foreach ($routes as $route => $method) {
            $this->route($route, $class.'->'.$method);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rest(string $route, string $class): RouterInterface
    {
        $itemRoute = preg_replace_callback('~^(?:(\w+)\h+)?(/[^\h]*)~', function ($match) {
            return ($match[1] ? $match[1].'_item' : '').' '.$match[2].'/@item';
        }, $route);

        return $this->controller($class, array(
            "GET $route" => 'all',
            "POST $route" => 'create',
            "GET $itemRoute" => 'get',
            "PUT|PATCH $itemRoute" => 'update',
            "DELETE $itemRoute" => 'delete',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $routeName, $parameters = null): string
    {
        if (isset($this->aliases[$routeName])) {
            $pattern = $this->aliases[$routeName];

            if (false === strpos($pattern, '@') && !$parameters) {
                return $pattern;
            }

            if (is_string($parameters)) {
                parse_str($parameters, $parameters);
            } elseif (!is_array($parameters)) {
                $parameters = (array) $parameters;
            }

            $path = preg_replace_callback(static::PATTERN_PARAMETER, function ($match) use (&$parameters) {
                $name = $match[1];

                if (!isset($parameters[$name])) {
                    throw new \LogicException(sprintf('Parameter "%s" should be provided.', $name));
                }

                $parameter = $parameters[$name];
                $rule = $match[3] ?? null;

                if ($rule && (!is_scalar($parameter) || !preg_match('~^'.$rule.'$~', (string) $parameter))) {
                    throw new \LogicException(sprintf('Parameter "%s" is not valid, given: %s.', $name, Util::stringify($parameter)));
                }

                unset($parameters[$name]);

                if (is_string($parameter)) {
                    return urlencode($parameter);
                }

                if (is_array($parameter)) {
                    return implode('/', array_map(function ($item) {
                        return is_string($item) ? urlencode($item) : $item;
                    }, $parameter));
                }

                return $parameter;
            }, $pattern);
        } else {
            $path = '/'.ltrim($routeName, '/');
        }

        if ($parameters) {
            $path .= '?'.http_build_query($parameters);
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): RouterInterface
    {
        if ($found = $this->findRoute($request)) {
            list($pattern, $route, $parameters) = $found;

            if ($found = $this->findController($route, $request)) {
                $alias = array_search($pattern, $this->aliases) ?: null;
                list($allowedMethods, $controller) = $found;

                $this->routeMatch = new RouteMatch($pattern, $alias, $allowedMethods, $controller, $parameters);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteMatch(): ?RouteMatch
    {
        return $this->routeMatch;
    }

    /**
     * Returns true if router caseless.
     *
     * @return bool
     */
    public function isCaseless(): bool
    {
        return $this->caseless;
    }

    /**
     * Sets routes.
     *
     * @param array $routes
     *
     * @return Router
     */
    public function routes(array $routes): Router
    {
        foreach ($routes as $route => $controller) {
            $this->route($route, $controller);
        }

        return $this;
    }

    /**
     * Returns route alias pattern.
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function getPattern(string $alias): ?string
    {
        return $this->aliases[$alias] ?? null;
    }

    /**
     * Returns routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Returns aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Find match route.
     *
     * @param Request $request
     *
     * @return array|null
     */
    protected function findRoute(Request $request): ?array
    {
        $modifier = $this->caseless ? 'i' : '';
        $path = $request->getPath();

        foreach ($this->routes as $pattern => $route) {
            if (null !== $arguments = $this->routeMatch($pattern, $modifier, $path)) {
                return array($pattern, $route, $arguments);
            }
        }

        return null;
    }

    /**
     * Find controller from matched route.
     *
     * @param array   $route
     * @param Request $request
     *
     * @return array|null
     */
    protected function findController(array $route, Request $request): ?array
    {
        if (isset($route[$mode = $request->getMode()]) || isset($route[$mode = Request::ALL])) {
            return array(array_keys($route[$mode]), $route[$mode][$request->getMethod()] ?? null);
        }

        return null;
    }

    /**
     * Returns array if path match pattern, otherwise returns null.
     *
     * @param string $pattern
     * @param string $modifier
     * @param string $path
     *
     * @return array|null
     */
    protected function routeMatch(string $pattern, string $modifier, string $path): ?array
    {
        $lastParameter = null;

        if (false !== strpos($pattern, '@')) {
            $pattern = preg_replace_callback(static::PATTERN_PARAMETER, function ($match) use (&$lastParameter) {
                $name = $match[1];
                $rule = $match[3] ?? '[^/]+';
                $all = $match[2] ?? null;

                if ($all) {
                    $rule = '.+';
                    $lastParameter = $name;
                }

                return '(?<'.$name.'>'.$rule.')';
            }, $pattern);
        }

        if (preg_match('~^'.$pattern.'$~'.$modifier, $path, $match)) {
            $parameters = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($lastParameter) {
                $parameters[$lastParameter] = explode('/', $parameters[$lastParameter]);
            }

            return $parameters;
        }

        return null;
    }
}
