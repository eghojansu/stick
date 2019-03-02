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
 * Response header bag.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ResponseHeaderBag extends HeaderBag
{
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';

    /**
     * @var array
     */
    protected $cookies = array();

    /**
     * Class constructor.
     *
     * @param array|null $headers
     */
    public function __construct(array $headers = null)
    {
        parent::__construct($headers);

        /* RFC2616 - 14.18 says all Responses need to have a Date */
        if (!isset($this->data['Date'])) {
            $this->initDate();
        }
    }

    /**
     * Add cookie.
     *
     * @param Cookie $cookie
     *
     * @return ResponseHeaderBag
     */
    public function addCookie(Cookie $cookie): ResponseHeaderBag
    {
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;

        return $this;
    }

    /**
     * Remove cookie.
     *
     * @param string      $name
     * @param string      $path
     * @param string|null $domain
     *
     * @return ResponseHeaderBag
     */
    public function removeCookie(string $name, string $path = '/', string $domain = null): ResponseHeaderBag
    {
        unset($this->cookies[$domain][$path][$name]);

        return $this;
    }

    /**
     * Clear cookie from browser.
     *
     * @param string      $name
     * @param string      $path
     * @param string|null $domain
     * @param bool        $secure
     * @param bool        $httpOnly
     *
     * @return ResponseHeaderBag
     */
    public function clearCookie(string $name, string $path = '/', string $domain = null, bool $secure = false, bool $httpOnly = true): ResponseHeaderBag
    {
        return $this->addCookie(new Cookie($name, null, 1, $path, $domain, $secure, $httpOnly, false, null));
    }

    /**
     * Reset cookies.
     *
     * @return ResponseHeaderBag
     */
    public function clearCookies(): ResponseHeaderBag
    {
        $this->cookies = array();

        return $this;
    }

    /**
     * Returns cookies.
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Returns cookies flattened.
     *
     * @return array
     */
    public function getFlatCookies(): array
    {
        $flattenedCookies = array();

        foreach ($this->cookies as $paths) {
            foreach ($paths as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattenedCookies[] = $cookie;
                }
            }
        }

        return $flattenedCookies;
    }

    /**
     * Set date header.
     */
    protected function initDate(): void
    {
        $this->set('Date', \DateTime::createFromFormat('U', ''.time(), new \DateTimeZone('UTC'))->format('D, d M Y H:i:s').' GMT');
    }
}
