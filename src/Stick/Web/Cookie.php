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
 * Cookie.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Cookie
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var int
     */
    protected $expire;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var bool
     */
    protected $secure;

    /**
     * @var bool
     */
    protected $httpOnly;

    /**
     * @var bool
     */
    protected $raw;

    /**
     * @var string
     */
    protected $sameSite;

    /**
     * @var bool
     */
    protected $secureDefault = false;

    const SAMESITE_LAX = 'lax';
    const SAMESITE_STRICT = 'strict';

    /**
     * Creates cookie from raw header string.
     *
     * @param string $cookie
     * @param bool   $decode
     *
     * @return Cookie
     */
    public static function fromString(string $cookie, bool $decode = false): Cookie
    {
        $data = array_reduce(explode(';', $cookie), function ($carry, $item) {
            $part = explode('=', $item);
            $carry[trim(strtolower($part[0]))] = isset($part[1]) ? trim($part[1]) : null;

            return $carry;
        }) + array(
            'expires' => 0,
            'path' => '/',
            'domain' => null,
            'secure' => null,
            'httponly' => null,
            'raw' => !$decode,
            'samesite' => null,
        );
        $name = key($data);
        $value = (string) array_shift($data);

        if ($decode) {
            $name = urldecode($name);
            $value = urldecode($value);
        }

        if (isset($data['max-age'])) {
            $data['expires'] = time() + (int) $data['max-age'];
        }

        return new static($name, $value, $data['expires'], $data['path'], $data['domain'], $data['secure'], $data['httponly'], $data['raw'], $data['samesite']);
    }

    /**
     * Class create.
     *
     * @param string                        $name     The name of the cookie
     * @param string|null                   $value    The value of the cookie
     * @param int|string|\DateTimeInterface $expire   The time the cookie expires
     * @param string                        $path     The path on the server in which the cookie will be available on
     * @param string|null                   $domain   The domain that the cookie is available to
     * @param bool|null                     $secure   Whether the client should send back the cookie only over HTTPS or null to auto-enable this when the request is already using HTTPS
     * @param bool                          $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @param bool                          $raw      Whether the cookie value should be sent with no url encoding
     * @param string|null                   $sameSite Whether the cookie will be available for cross-site requests
     *
     * @return Cookie
     */
    public static function create(string $name, string $value = null, $expire = null, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, bool $raw = null, string $sameSite = null): Cookie
    {
        return new self($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite ?? static::SAMESITE_LAX);
    }

    /**
     * Class constructor.
     *
     * @param string                        $name     The name of the cookie
     * @param string|null                   $value    The value of the cookie
     * @param int|string|\DateTimeInterface $expire   The time the cookie expires
     * @param string                        $path     The path on the server in which the cookie will be available on
     * @param string|null                   $domain   The domain that the cookie is available to
     * @param bool|null                     $secure   Whether the client should send back the cookie only over HTTPS or null to auto-enable this when the request is already using HTTPS
     * @param bool                          $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @param bool                          $raw      Whether the cookie value should be sent with no url encoding
     * @param string|null                   $sameSite Whether the cookie will be available for cross-site requests
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, string $value = null, $expire = null, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, bool $raw = null, string $sameSite = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = $expire ?? 0;
        $this->path = $path ?: '/';
        $this->secure = $secure;
        $this->httpOnly = $httpOnly ?? true;
        $this->raw = $raw ?? false;
        $this->sameSite = $sameSite;

        if (empty($this->name)) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        // from PHP source code
        if (preg_match("/[=,; \t\r\n\013\014]/", $this->name)) {
            throw new \InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $this->name));
        }

        // convert expiration time to a Unix timestamp
        if ($this->expire instanceof \DateTimeInterface) {
            $this->expire = $this->expire->format('U') + 0;
        } elseif (!is_numeric($this->expire)) {
            $this->expire = strtotime($this->expire);

            if (false === $this->expire) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        } elseif (0 > $this->expire) {
            $this->expire = 0;
        }

        if ($this->sameSite && !in_array($this->sameSite, array(static::SAMESITE_LAX, static::SAMESITE_STRICT))) {
            throw new \InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }
    }

    /**
     * Returns the cookie as a string.
     *
     * @return string The cookie
     */
    public function toString(): string
    {
        $str = $this->isRaw() ? $this->getName().'=' : urlencode($this->getName()).'=';

        if ('' === (string) $this->getValue()) {
            $str .= 'deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001).'; Max-Age=0';
        } else {
            $str .= $this->isRaw() ? $this->getValue() : rawurlencode($this->getValue());

            if (0 !== $this->getExpiresTime()) {
                $str .= '; expires='.gmdate('D, d-M-Y H:i:s T', $this->getExpiresTime()).'; Max-Age='.$this->getMaxAge();
            }
        }

        if ($this->getPath()) {
            $str .= '; path='.$this->getPath();
        }

        if ($this->getDomain()) {
            $str .= '; domain='.$this->getDomain();
        }

        if (true === $this->isSecure()) {
            $str .= '; secure';
        }

        if (true === $this->isHttpOnly()) {
            $str .= '; httponly';
        }

        if (null !== $this->getSameSite()) {
            $str .= '; samesite='.$this->getSameSite();
        }

        return $str;
    }

    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the value of the cookie.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Gets the domain that the cookie is available to.
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Gets the time the cookie expires.
     *
     * @return int
     */
    public function getExpiresTime(): int
    {
        return $this->expire;
    }

    /**
     * Gets the max-age attribute.
     *
     * @return int
     */
    public function getMaxAge(): int
    {
        $maxAge = $this->expire - time();

        return 0 >= $maxAge ? 0 : $maxAge;
    }

    /**
     * Gets the path on the server in which the cookie will be available on.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure ?? $this->secureDefault;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Whether this cookie is about to be cleared.
     *
     * @return bool
     */
    public function isCleared(): bool
    {
        return 0 !== $this->expire && $this->expire < time();
    }

    /**
     * Checks if the cookie value should be sent with no url encoding.
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * Gets the SameSite attribute.
     *
     * @return string|null
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * Sets default secure.
     *
     * @param bool $default The default value of the "secure" flag when it is set to null
     *
     * @return Cookie
     */
    public function setSecureDefault(bool $default): Cookie
    {
        $this->secureDefault = $default;

        return $this;
    }
}
