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

namespace Fal\Stick\Security;

/**
 * JWT helper.
 *
 * Based on:
 *
 *  * https://github.com/bllohar/php-jwt-class-with-RSA-support/blob/master/src/JWToken.php
 *  * https://github.com/bastman/php-jwt/blob/master/src/JWT.php
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Jwt
{
    // Available algorithm
    const ALG_HS256 = 'HS256';
    const ALG_HS384 = 'HS384';
    const ALG_HS512 = 'HS512';
    const ALG_RS256 = 'RS256';
    const ALG_RS384 = 'RS384';
    const ALG_RS512 = 'RS512';

    protected static $algorithms = array(
        self::ALG_HS256 => 'SHA256',
        self::ALG_HS384 => 'SHA384',
        self::ALG_HS512 => 'SHA512',
        self::ALG_RS256 => 'SHA256',
        self::ALG_RS384 => 'SHA384',
        self::ALG_RS512 => 'SHA512',
    );

    /**
     * @var array
     */
    protected $supportedAlgorithms;

    /**
     * @var string
     */
    protected $algorithm;

    /**
     * @var string|resource
     */
    protected $encodeKey;

    /**
     * @var string|resource
     */
    protected $decodeKey;

    /**
     * Leeway time.
     *
     * The server leeway time in seconds to aware the acceptable different time
     * between clocks of token issued server and relying parties.
     *
     * When checking nbf, iat or expiration times, we want to provide
     * some extra leeway time to account for clock skew.
     *
     * @var int
     */
    protected $leeway = 0;

    /**
     * Class constructor.
     *
     * @param string|resource $encodeKey
     * @param string|resource $decodeKey
     * @param string|null     $algorithm
     * @param array|null      $supportedAlgorithms
     */
    public function __construct($encodeKey, $decodeKey = null, string $algorithm = null, array $supportedAlgorithms = null)
    {
        $this->setKey($encodeKey, $decodeKey);
        $this->setAlgorithm($algorithm);
        $this->setSupportedAlgorithms($supportedAlgorithms);
    }

    /**
     * Returns algorithm.
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Sets algorithm.
     *
     * @param string|null $algorithm
     *
     * @return Jwt
     */
    public function setAlgorithm(string $algorithm = null): Jwt
    {
        $this->algorithm = $algorithm ?? static::ALG_HS256;

        return $this;
    }

    /**
     * Returns supportedAlgorithms.
     *
     * @return array|null
     */
    public function getSupportedAlgorithms(): ?array
    {
        return $this->supportedAlgorithms;
    }

    /**
     * Sets supportedAlgorithms.
     *
     * @param array $supportedAlgorithms
     *
     * @return Jwt
     */
    public function setSupportedAlgorithms(array $supportedAlgorithms = null): Jwt
    {
        $this->supportedAlgorithms = $supportedAlgorithms;

        return $this;
    }

    /**
     * Returns encodeKey.
     *
     * @return string|resource
     */
    public function getEncodeKey()
    {
        return $this->encodeKey;
    }

    /**
     * Returns decodeKey.
     *
     * @return string|resource
     */
    public function getDecodeKey()
    {
        return $this->decodeKey;
    }

    /**
     * Sets key.
     *
     * @param string|resource $encodeKey
     * @param string|resource $decodeKey
     *
     * @return Jwt
     *
     * @throws LogicException If key is empty
     */
    public function setKey($encodeKey, $decodeKey = null): Jwt
    {
        if (empty($encodeKey)) {
            throw new \LogicException('Key may not be empty.');
        }

        $this->encodeKey = $encodeKey;
        $this->decodeKey = $decodeKey ?: $encodeKey;

        return $this;
    }

    /**
     * Returns leeway.
     *
     * @return int
     */
    public function getLeeway(): int
    {
        return $this->leeway;
    }

    /**
     * Sets leeway.
     *
     * @param int $leeway
     *
     * @return Jwt
     */
    public function setLeeway(int $leeway): Jwt
    {
        $this->leeway = $leeway;

        return $this;
    }

    /**
     * Encode payload.
     *
     * @param array $payload
     *
     * @return string
     */
    public function encode(array $payload): string
    {
        $header = array(
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        );

        $token = $this->urlencode(json_encode($header)).'.'.$this->urlencode(json_encode($payload));
        $token .= '.'.$this->urlencode($this->sign($token, $this->algorithm, $this->encodeKey));

        return $token;
    }

    /**
     * Decode jwt token.
     *
     * @param string $token
     *
     * @return array
     *
     * @throws UnexpectedValueException If given token is invalid or signature verification failed
     * @throws DomainException          If header contains no algorithm or algorithm is not allowed
     * @throws RuntimeException         If token expired or token is invalid
     */
    public function decode(string $token): array
    {
        $segments = explode('.', $token);

        if (3 !== count($segments)) {
            throw new \UnexpectedValueException('Wrong number of segments.');
        }

        $header = json_decode($this->urldecode($segments[0]), true);
        $payload = json_decode($this->urldecode($segments[1]), true);
        $signature = $this->urldecode($segments[2]);

        if (null === $header) {
            throw new \UnexpectedValueException('Invalid header encoding.');
        }

        if (null === $payload) {
            throw new \UnexpectedValueException('Invalid claims encoding.');
        }

        if (empty($header['alg'])) {
            throw new \DomainException('Empty algorithm.');
        }

        if ($this->supportedAlgorithms && !in_array($header['alg'], $this->supportedAlgorithms)) {
            throw new \DomainException('Algorithm is not allowed.');
        }

        // Check the signature
        if (!$this->verify($segments[0].'.'.$segments[1], $signature, $header['alg'], $this->decodeKey)) {
            throw new \UnexpectedValueException('Signature verification failed.');
        }

        $fix = $payload;

        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($fix['nbf']) && $fix['nbf'] > (time() + $this->leeway)) {
            throw new \RuntimeException('Token can not be used right now.');
        }

        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($fix['iat']) && $fix['iat'] > (time() + $this->leeway)) {
            throw new \RuntimeException('Token can not be used right now.');
        }

        // Check if this token has expired.
        if (isset($fix['exp']) && (time() - $this->leeway) >= $fix['exp']) {
            throw new \RuntimeException('Token expired.');
        }

        return $payload;
    }

    /**
     * Sign a string.
     *
     * @param string          $message   The message to sign
     * @param string          $algorithm
     * @param string|resource $key
     *
     * @return string An encrypted message
     *
     * @throws DomainException If algorithm is not supported
     */
    protected function sign(string $message, string $algorithm, $key): string
    {
        $usedAlgorithm = static::$algorithms[$algorithm] ?? null;

        switch ($algorithm) {
            case static::ALG_HS256:
            case static::ALG_HS384:
            case static::ALG_HS512:
                return hash_hmac($usedAlgorithm, $message, $key, true);
            case static::ALG_RS256:
            case static::ALG_RS384:
            case static::ALG_RS512:
                return $this->generateRsa($usedAlgorithm, $message, $key);
        }

        throw new \DomainException('Algorithm is not supported.');
    }

    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string          $message   The original message (header and body)
     * @param string          $signature The original signature
     * @param string          $algorithm The algorithm
     * @param string|resource $key
     *
     * @return bool
     *
     * @throws DomainException If algorithm is not supported
     */
    protected function verify(string $message, string $signature, string $algorithm, $key): bool
    {
        switch ($algorithm) {
            case static::ALG_HS256:
            case static::ALG_HS384:
            case static::ALG_HS512:
                return hash_equals($signature, $this->sign($message, $algorithm, $key));
            case static::ALG_RS256:
            case static::ALG_RS384:
            case static::ALG_RS512:
                $usedAlgorithm = static::$algorithms[$algorithm] ?? null;

                return $this->verifyRsa($signature, $usedAlgorithm, $message, $key);
        }

        throw new \DomainException('Algorithm is not supported.');
    }

    /**
     * Verify RSA.
     *
     * @param string          $signature
     * @param string          $algorithm
     * @param string          $message
     * @param string|resource $key
     *
     * @return bool
     */
    protected function verifyRsa(string $signature, string $algorithm, string $message, $key): bool
    {
        return openssl_verify($message, $signature, $key, $algorithm) > 0;
    }

    /**
     * Generate RSA.
     *
     * @param string          $algorithm
     * @param string          $message
     * @param string|resource $key
     *
     * @return string
     */
    protected function generateRsa(string $algorithm, string $message, $key): string
    {
        $signature = '';
        openssl_sign($message, $signature, $key, $algorithm);

        return $signature ?: '';
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $str A Base64 encoded string
     *
     * @return string A decoded string
     */
    protected function urldecode(string $str): string
    {
        $remainder = strlen($str) % 4;

        if ($remainder) {
            $padlen = 4 - $remainder;
            $str .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $str The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    protected function urlencode(string $str): string
    {
        return str_replace('=', '', strtr(base64_encode($str), '+/', '-_'));
    }
}
