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

namespace Fal\Stick\Form;

/**
 * Form builder interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface FormBuilderInterface
{
    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Sets options.
     *
     * @param array $options
     *
     * @return FormBuilderInterface
     */
    public function setOptions(array $options): FormBuilderInterface;

    /**
     * Returns form open tag.
     *
     * @param array $attr
     *
     * @return string
     */
    public function open(array $attr): string;

    /**
     * Returns form close tag.
     *
     * @return string
     */
    public function close(): string;

    /**
     * Returns row field.
     *
     * @param Field $field
     *
     * @return string
     */
    public function renderField(Field $field): string;

    /**
     * Returns row buttons.
     *
     * @param array $buttons
     *
     * @return string
     */
    public function renderButtons(array $buttons): string;
}
