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

namespace Fal\Stick\Web\Form\FormBuilder;

use Fal\Stick\Web\Form\Button;
use Fal\Stick\Web\Form\Field;
use Fal\Stick\Util;

/**
 * Twitter bootstrap 4 form builder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs4FormBuilder extends Twbs3FormBuilder
{
    /**
     * {@inheritdoc}
     */
    public function open(array $attr): string
    {
        if (!isset($attr['class'])) {
            $attr['class'] = false;
        }

        return parent::open($attr);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareButton(Button $button): void
    {
        if (!isset($button->attr['class'])) {
            $button->attr['class'] = $button->isType('link') ? 'btn btn-secondary' : 'btn btn-primary';
        }

        parent::prepareButton($button);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareField(Field $field): void
    {
        parent::prepareField($field);

        if (isset($field->attr['class']) && !preg_match('/is\-(valid|invalid)/i', $field->attr['class'])) {
            $field->attr['class'] .= $this->validationClass($field);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(Field $field): string
    {
        $wrapperAttr = $field->wrapper_attr ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $field->attr['id'] ?? $field->id);

        $field->attr['class'] = 'form-check-input'.$this->validationClass($field);
        $field->attr['type'] = 'radio';

        if (!array_key_exists('checked', $field->attr)) {
            $field->attr['checked'] = isset($field->attr['value']) ? $field->attr['value'] == $field->value : 'on' === $field->value;
        }

        $radio = $this->inputInput($field);
        $label = Util::tag('label', $labelAttr, true, $field->label);

        return Util::tag('div', $wrapperAttr + $wrapperDefault, true, $radio.$label);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(Field $field): string
    {
        $wrapperAttr = $field->wrapper_attr ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $field->attr['id'] ?? $field->id);

        $field->attr['class'] = 'form-check-input'.$this->validationClass($field);
        $field->attr['type'] = 'checkbox';

        if (!array_key_exists('checked', $field->attr)) {
            $field->attr['checked'] = isset($field->attr['value']) ? $field->attr['value'] == $field->value : 'on' === $field->value;
        }

        $checkbox = $this->inputInput($field);
        $label = Util::tag('label', $labelAttr, true, $field->label);

        return Util::tag('div', $wrapperAttr + $wrapperDefault, true, $checkbox.$label);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderErrorsRow(array $errors = null): string
    {
        return $errors ? Util::tag('div', array('class' => 'invalid-feedback'), true, implode(', ', $errors)) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderButtonRow(string $buttons): string
    {
        if ($buttons) {
            return
                '<div class="form-group row"><div class="ml-auto '.$this->options['right'].'">'.
                $buttons.
                '</div></div>'
            ;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderFieldRow(Field $field, string $input): string
    {
        if ($field->isType('hidden')) {
            return $input;
        }

        $wrapperClass = 'form-group row';
        $errors = $this->renderErrorsRow($field->errors);

        if ($field->inType(array('checkbox', 'radio')) || $field instanceof Button) {
            return
                '<div class="'.$wrapperClass.'"><div class="ml-auto '.$this->options['right'].'">'.
                $input.$errors.
                '</div></div>'
            ;
        }

        $labelAttr = $field->label_attr + array('class' => 'col-form-label '.$this->options['left']);

        return
            '<div class="'.$wrapperClass.'">'.
            Util::tag('label', $labelAttr, true, $field->label).
            '<div class="'.$this->options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'
        ;
    }

    /**
     * Returns validation class name.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function validationClass(Field $field): string
    {
        if ($field->submitted && $field->constraints) {
            return $field->errors ? ' is-invalid' : ' is-valid';
        }

        return '';
    }
}
