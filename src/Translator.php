<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Simple translator class.
 */
final class Translator
{
    /** @var array */
    private $dict;

    /** @var array */
    private $locales;

    /** @var array */
    private $languages;

    /** @var string */
    private $fallback;

    /** @var bool */
    private $loaded;

    /**
     * Class constructor.
     *
     * @param string      $locales
     * @param string|null $languages
     * @param string      $fallback
     */
    public function __construct($locales = './', string $languages = null, string $fallback = 'en')
    {
        $this->setLocales($locales);
        $this->setFallback($fallback);
        $this->setLanguages($languages ?? '');
    }

    /**
     * Trans message.
     *
     * @param string $key
     * @param array  $args
     *
     * @return string
     */
    public function trans(string $key, array $args = []): string
    {
        return strtr($this->ref($key) ?? $key, $args);
    }

    /**
     * Trans plural message.
     *
     * @param string  $key
     * @param numeric $count
     * @param array   $args
     *
     * @return string
     */
    public function choice(string $key, $count, array $args = []): string
    {
        $args['#'] = $count;

        foreach (explode('|', $this->ref($key) ?? $key) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $args);
            }
        }

        return strtr($choice, $args);
    }

    /**
     * Get dict.
     *
     * @return array
     */
    public function getDict(): array
    {
        return $this->dict;
    }

    /**
     * Add message.
     *
     * @param string $key
     * @param string $message
     *
     * @return Translator
     */
    public function add(string $key, string $message): Translator
    {
        $ref = &$this->ref($key, true);
        $ref = $message;

        return $this;
    }

    /**
     * Get locales.
     *
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Set locales.
     *
     * @param string|array $locales
     *
     * @return Translator
     */
    public function setLocales($locales): Translator
    {
        $this->locales = Helper::reqarr($locales);
        $this->reset();

        return $this;
    }

    /**
     * Get languages.
     *
     * @return string
     */
    public function getLanguages(): string
    {
        return implode(',', $this->languages);
    }

    /**
     * Set languages.
     *
     * @param string $code
     *
     * @return Translator
     */
    public function setLanguages(string $code): Translator
    {
        $use = ltrim(preg_replace('/\h+|;q=[0-9.]+/', '', $code).','.$this->fallback, ',');

        $languages = [];
        foreach (array_reverse(explode(',', $use)) as $lang) {
            if (preg_match('/^(\w{2})(?:-(\w{2}))?\b/i', $lang, $parts)) {
                // Generic language
                array_unshift($languages, $parts[1]);

                if (isset($parts[2])) {
                    // Specific language
                    $parts[0] = $parts[1].'-'.strtoupper($parts[2]);
                    array_unshift($languages, $parts[0]);
                }
            }
        }

        $this->languages = array_unique($languages);
        $this->reset();

        return $this;
    }

    /**
     * Get fallback.
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * Set fallback.
     *
     * @param string $fallback
     *
     * @return Translator
     */
    public function setFallback(string $fallback): Translator
    {
        $this->fallback = $fallback;
        $this->reset();

        return $this;
    }

    /**
     * Reset dict.
     */
    private function reset(): void
    {
        $this->loaded = false;
        $this->dict = [];
    }

    /**
     * Get message reference.
     *
     * @param string $key
     * @param bool   $add
     */
    private function &ref(string $key, bool $add = false)
    {
        $this->load();

        if ($add) {
            $ref = &Helper::ref($key, $this->dict);

            return $ref;
        }

        $ref = Helper::ref($key, $this->dict, false);

        if ($ref && !is_string($ref)) {
            throw new \UnexpectedValueException('Message reference is not a string');
        }

        return $ref;
    }

    /**
     * Load languages.
     *
     * @return Translator
     */
    private function load(): Translator
    {
        if ($this->loaded) {
            return $this;
        }

        $this->loaded = true;

        foreach (array_reverse($this->languages) as $lang) {
            foreach ($this->locales as $dir) {
                if (file_exists($file = $dir.$lang.'.php')) {
                    $this->dict = array_replace_recursive($this->dict, Helper::exrequire($file, []));
                }
            }
        }

        return $this;
    }
}
