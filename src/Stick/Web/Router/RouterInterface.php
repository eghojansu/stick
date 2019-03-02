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

use Fal\Stick\Web\Request;

/**
 * Router.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface RouterInterface
{
    /**
     * Add route.
     *
     * @param string $route
     * @param mixed  $controller
     *
     * @return RouterInterface
     */
    public function route(string $route, $controller): RouterInterface;

    /**
     * Add controller routes.
     *
     * @param string $class
     * @param array  $routes
     *
     * @return RouterInterface
     */
    public function controller(string $class, array $routes): RouterInterface;

    /**
     * Add rest controller.
     *
     * @param string $route
     * @param string $class
     *
     * @return RouterInterface
     */
    public function rest(string $route, string $class): RouterInterface;

    /**
     * Generate route.
     *
     * @param string $routeName
     * @param mixed  $parameters
     *
     * @return string
     */
    public function generate(string $routeName, $parameters = null): string;

    /**
     * Handle request.
     *
     * @param Request $request
     *
     * @return RouterInterface
     */
    public function handle(Request $request): RouterInterface;

    /**
     * Returns route match.
     *
     * @return RouteMatch|null
     */
    public function getRouteMatch(): ?RouteMatch;
}
