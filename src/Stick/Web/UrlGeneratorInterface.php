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

namespace Fal\Stick\Web;

/**
 * Url generator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface UrlGeneratorInterface
{
    /**
     * Returns current URI.
     *
     * @return string
     */
    public function getUri(): string;

    /**
     * Returns base url if absolute, otherwise returns base path.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getBase(bool $absolute = false): string;

    /**
     * Generate url by route name.
     *
     * @param string $routeName
     * @param mixed  $parameters
     * @param bool   $absolute
     *
     * @return string
     */
    public function generate(string $routeName, $parameters = null, bool $absolute = false): string;

    /**
     * Generate asset-url.
     *
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     */
    public function asset(string $path, bool $absolute = false): string;

    /**
     * Returns redirect response.
     *
     * @param mixed $target
     * @param bool  $permanent
     *
     * @return RedirectResponse
     */
    public function redirect($target = null, bool $permanent = false): RedirectResponse;
}
