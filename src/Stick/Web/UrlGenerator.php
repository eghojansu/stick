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

use Fal\Stick\Web\Router\RouterInterface;

/**
 * Url generator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var RequestStackInterface
     */
    protected $requestStack;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $entry;

    /**
     * @var bool
     */
    protected $front;

    /**
     * @var string
     */
    protected $assetVersion;

    /**
     * @var array
     */
    protected $assets;

    /**
     * @var bool
     */
    protected $dry = true;

    /**
     * Class constructor.
     *
     * @param RequestStackInterface $requestStack
     * @param RouterInterface       $router
     * @param bool                  $front
     * @param string|null           $assetVersion
     * @param array|null            $assets
     */
    public function __construct(RequestStackInterface $requestStack, RouterInterface $router, bool $front = true, string $assetVersion = null, array $assets = null)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->front = $front;
        $this->assetVersion = $assetVersion;
        $this->assets = $assets;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): string
    {
        $this->getBase();

        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getBase(bool $absolute = false): string
    {
        if ($this->dry) {
            $request = $this->requestStack->getMasterRequest();

            $this->uri = $request->getUri();
            $this->baseUrl = $request->getBaseUrl();
            $this->basePath = $request->getBase();
            $this->entry = $this->front ? rtrim('/'.$request->getFront(), '/') : null;
            $this->dry = false;
        }

        return $absolute ? $this->baseUrl : $this->basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $routeName, $parameters = null, bool $absolute = false): string
    {
        $path = $this->getBase($absolute);
        $path .= $this->entry;
        $path .= $this->router->generate($routeName, $parameters);

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function asset(string $path, bool $absolute = false): string
    {
        $asset = $this->getBase($absolute);
        $asset .= '/';
        $asset .= $this->assets[$path] ?? $path;

        if ($this->assetVersion) {
            $asset .= '?'.('dynamic' === $this->assetVersion ? time() : $this->assetVersion);
        }

        return $asset;
    }

    /**
     * {inheritdoc}.
     */
    public function redirect($target = null, bool $permanent = false): RedirectResponse
    {
        if (!$target) {
            $url = $this->getUri();
        } elseif (is_array($target)) {
            $url = $this->generate(reset($target), next($target) ?: null, true);
        } elseif (preg_match('/^(\w+)(?:\(([^(]+)\))?(?:\?(.+))?$/', $target, $match)) {
            $parameters = array();

            if (isset($match[2]) && $match[2]) {
                parse_str(strtr($match[2], ',', '&'), $parameters);
            }

            if (isset($match[3]) && $match[3]) {
                parse_str($match[3], $query);

                $parameters += $query;
            }

            $url = $this->generate($match[1], $parameters, true);
        } else {
            $url = preg_match('/^\w+:/', $target) ? $target : $this->generate($target, null, true);
        }

        return new RedirectResponse($url, $permanent ? 301 : 302);
    }
}
