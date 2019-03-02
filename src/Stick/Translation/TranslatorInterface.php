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
 * Translator interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface TranslatorInterface
{
    /**
     * Add locale.
     *
     * @param string $locale
     * @param bool   $prepend
     *
     * @return Translator
     */
    public function addLocale(string $locale, bool $prepend = false): Translator;

    /**
     * Returns language.
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Assign language.
     *
     * @param string $language
     *
     * @return Translator
     */
    public function setLanguage(string $language): Translator;

    /**
     * Returns translated message.
     *
     * @param string     $message
     * @param array|null $parameters
     *
     * @return string
     */
    public function trans(string $message, array $parameters = null): string;

    /**
     * Returns translated choice.
     *
     * @param string     $message
     * @param int        $count
     * @param array|null $parameters
     *
     * @return string
     */
    public function choice(string $message, int $count, array $parameters = null): string;

    /**
     * Returns first exists message.
     *
     * @param array      $messages
     * @param array|null $parameters
     *
     * @return string
     */
    public function transAlt(array $messages, array $parameters = null): string;

    /**
     * Returns translated message only if exists in lexicon.
     *
     * @param string     $message
     * @param array|null $parameters
     *
     * @return string|null
     */
    public function transAdv(string $message, array $parameters = null): ?string;
}
