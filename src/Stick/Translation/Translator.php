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

namespace Fal\Stick\Translation;

/**
 * Translator utility.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Translator implements TranslatorInterface
{
    /**
     * @var array
     */
    protected $locales;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $fallback;

    /**
     * @var array
     */
    protected $lexicon;

    /**
     * Class constructor.
     *
     * @param array|null  $locales
     * @param string|null $language
     * @param string      $fallback
     */
    public function __construct(array $locales = null, string $language = null, string $fallback = 'en')
    {
        $this->locales = $locales ?? array();
        $this->language = $language ?? '';
        $this->fallback = $fallback;
        $this->load();
    }

    /**
     * Returns true if word definition exists.
     *
     * @param string $word
     *
     * @return bool
     */
    public function exists(string $word): bool
    {
        return isset($this->lexicon[$word]);
    }

    /**
     * Returns word definition if exists otherwise returns null.
     *
     * @param string $word
     *
     * @return mixed
     */
    public function get(string $word)
    {
        return $this->lexicon[$word] ?? null;
    }

    /**
     * Assign word definition to lexicon.
     *
     * @param string $word
     * @param string $definition
     *
     * @return Translator
     */
    public function set(string $word, string $definition): Translator
    {
        $this->lexicon[$word] = $definition;

        return $this;
    }

    /**
     * Remove word definition.
     *
     * @param string $word
     *
     * @return Translator
     */
    public function clear(string $word): Translator
    {
        unset($this->lexicon[$word]);

        return $this;
    }

    /**
     * Returns language fallback.
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * Assign fallback.
     *
     * @param string $fallback
     *
     * @return Translator
     */
    public function setFallback(string $fallback): Translator
    {
        $this->fallback = $fallback;
        $this->load();

        return $this;
    }

    /**
     * Returns locale directories.
     *
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Assign locale directories.
     *
     * @param array $locales
     *
     * @return Translator
     */
    public function setLocales(array $locales): Translator
    {
        $this->locales = array();

        foreach ($locales as $locale => $prepend) {
            $this->addLocale($locale, $prepend);
        }

        $this->load();

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function addLocale(string $locale, bool $prepend = false): Translator
    {
        if ($prepend) {
            array_unshift($this->locales, $locale);
        } else {
            $this->locales[] = $locale;
        }

        $this->load();

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * {inheritdoc}.
     */
    public function setLanguage(string $language): Translator
    {
        $this->language = $language;
        $this->load();

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function trans(string $message, array $parameters = null): string
    {
        $reference = $this->lexicon[$message] ?? $message;

        return strtr($reference, $parameters ?? array());
    }

    /**
     * {inheritdoc}.
     */
    public function choice(string $message, int $count, array $parameters = null): string
    {
        $parameters['#'] = $count;
        $reference = $this->lexicon[$message] ?? $message;

        foreach (explode('|', $reference) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $parameters);
            }
        }

        return strtr($choice, $parameters);
    }

    /**
     * {inheritdoc}.
     */
    public function transAlt(array $messages, array $parameters = null): string
    {
        foreach ($messages as $message) {
            if (array_key_exists($message, $this->lexicon)) {
                return strtr($this->lexicon[$message], $parameters ?? array());
            }
        }

        return end($messages);
    }

    /**
     * {inheritdoc}.
     */
    public function transAdv(string $message, array $parameters = null): ?string
    {
        if (isset($this->lexicon[$message])) {
            return strtr($this->lexicon[$message], $parameters ?? array());
        }

        return null;
    }

    /**
     * Returns language codes.
     *
     * @return array
     */
    protected function codes(): array
    {
        $languages = preg_replace('/\h+|;q=[0-9.]+/', '', $this->language).','.$this->fallback;
        $final = array();

        foreach (explode(',', $languages) as $lang) {
            if (preg_match('/^(\w{2})(?:-(\w{2}))?\b/i', $lang, $parts)) {
                if (isset($parts[2])) {
                    // Specific language
                    $final[] = $parts[1].'-'.strtoupper($parts[2]);
                }

                // Generic language
                $final[] = $parts[1];
            }
        }

        return array_unique($final);
    }

    /**
     * Load language.
     */
    protected function load(): void
    {
        $lexicon = array();
        $pattern = '/(?<=^|\n)(?:'.
            '\[(?<prefix>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';

        foreach ($this->codes() as $code) {
            foreach ($this->locales as $locale) {
                $file = $locale.$code.'.ini';
                $prefix = '';

                if (!is_file($file) || !preg_match_all($pattern, file_get_contents($file), $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as $match) {
                    if ($match['prefix']) {
                        $prefix = $match['prefix'].'.';
                    } elseif (!array_key_exists($key = $prefix.$match['lval'], $lexicon)) {
                        $lexicon[$key] = trim(preg_replace('/\\\\\h*\r?\n/', "\n", $match['rval']));
                    }
                }
            }
        }

        $this->lexicon = $lexicon;
    }
}
