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

namespace Fal\Stick\Form\FormBuilder;

use Fal\Stick\Form\Field;
use Fal\Stick\Html\Element;

/**
 * Twitter bootstrap 3 form builder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs3FormBuilder extends DivFormBuilder
{
    const MARK_NONE = 0;
    const MARK_SUCCESS = 1;
    const MARK_ERROR = 2;

    /**
     * @var array
     */
    protected $options = array(
        'left' => 'col-sm-2',
        'right' => 'col-sm-10',
        'offset' => 'col-sm-offset-2',
        'mark' => self::MARK_ERROR,
    );

    /**
     * {@inheritdoc}
     */
    public function open(array $attr): string
    {
        if (!isset($attr['class'])) {
            $attr['class'] = 'form-horizontal';
        }

        return parent::open($attr);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareButton(Field $button): void
    {
        if (!isset($button->attr['class'])) {
            $button->attr['class'] = $button->isType('link') ? 'btn btn-default' : 'btn btn-primary';
        }

        parent::prepareButton($button);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareField(Field $field): void
    {
        if (!isset($field->attr['class']) && (!$field->inType(array('radio', 'checkbox', 'hidden', 'choice')) || ($field->isType('choice') && !$field->expanded))) {
            $field->attr['class'] = 'form-control';
        }

        parent::prepareField($field);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(Field $field): string
    {
        $attr = $field->wrapper_attr;

        if (!isset($attr['class'])) {
            $attr['class'] = 'radio';
        }

        return Element::tag('div', $attr, true, parent::inputRadio($field));
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(Field $field): string
    {
        $attr = $field->wrapper_attr;

        if (!isset($attr['class'])) {
            $attr['class'] = 'checkbox';
        }

        return Element::tag('div', $attr, true, parent::inputCheckbox($field));
    }

    /**
     * {@inheritdoc}
     */
    protected function renderErrorsRow(array $errors = null): string
    {
        return $errors ? Element::tag('span', array('class' => 'help-block'), true, implode(', ', $errors)) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderButtonRow(string $buttons): string
    {
        if ($buttons) {
            $rightOffset = $this->options['offset'].' '.$this->options['right'];

            return
                '<div class="form-group"><div class="'.$rightOffset.'">'.
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

        $rightOffset = $this->options['offset'].' '.$this->options['right'];
        $wrapperClass = 'form-group';
        $errors = $this->renderErrorsRow($field->errors);

        if ($field->submitted && $field->constraints) {
            if (!$errors && $this->options['mark'] & static::MARK_SUCCESS) {
                $wrapperClass .= ' has-success';
            } elseif ($errors && $this->options['mark'] & static::MARK_ERROR) {
                $wrapperClass .= ' has-error';
            }
        }

        if ($field->inType(array('checkbox', 'radio')) || $field->isButton()) {
            return
                '<div class="'.$wrapperClass.'"><div class="'.$rightOffset.'">'.
                $input.$errors.
                '</div></div>'
            ;
        }

        $labelAttr = $field->label_attr + array('class' => 'control-label '.$this->options['left']);

        return
            '<div class="'.$wrapperClass.'">'.
            Element::tag('label', $labelAttr, true, $field->label).
            '<div class="'.$this->options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'
        ;
    }
}
