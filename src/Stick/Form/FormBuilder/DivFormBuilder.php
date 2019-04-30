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

use Fal\Stick\Fw;
use Fal\Stick\Form\Field;
use Fal\Stick\Html\Element;
use Fal\Stick\Form\FormBuilderInterface;

/**
 * Basic form builder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class DivFormBuilder implements FormBuilderInterface
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Class constructor.
     *
     * @param Fw    $fw
     * @param array $options
     */
    public function __construct(Fw $fw, array $options = null)
    {
        $this->fw = $fw;
        $this->setOptions((array) $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): FormBuilderInterface
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr): string
    {
        return Element::tag('form', $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * {@inheritdoc}
     */
    public function renderField(Field $field): string
    {
        if ($field->isButton()) {
            $this->prepareButton($field);

            $content = $this->inputButton($field);
        } else {
            $this->prepareField($field);

            $input = 'input'.$field->type;
            $content = method_exists($this, $input) ? $this->$input($field) : $this->inputInput($field);
        }

        return $this->renderFieldRow($field, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function renderButtons(array $buttons): string
    {
        $str = '';

        foreach ($buttons as $button) {
            $this->prepareButton($button);

            $str .= ' '.$this->inputButton($button);
        }

        return $this->renderButtonRow(trim($str));
    }

    /**
     * Returns errors row.
     *
     * @param array|null $errors
     *
     * @return string
     */
    protected function renderErrorsRow(array $errors = null): string
    {
        return $errors ? Element::tag('div', null, true, implode(', ', $errors)) : '';
    }

    /**
     * Returns button row.
     *
     * @param string $buttons
     *
     * @return string
     */
    protected function renderButtonRow(string $buttons): string
    {
        return $buttons ? '<div>'.$buttons.'</div>' : '';
    }

    /**
     * Returns field row.
     *
     * @param Field  $field
     * @param string $input
     *
     * @return string
     */
    protected function renderFieldRow(Field $field, string $input): string
    {
        if ($field->isType('hidden')) {
            return $input;
        }

        $errors = $this->renderErrorsRow($field->errors);

        if ($field->inType(array('checkbox', 'radio')) || $field->isButton()) {
            return '<div>'.$input.$errors.'</div>';
        }

        return '<div>'.
            Element::tag('label', $field->label_attr, true, $field->label).
            ' <span>'.$input.$errors.'</span>'.
            '</div>'
        ;
    }

    /**
     * Prepare button before render.
     *
     * @param Field $button
     */
    protected function prepareButton(Field $button): void
    {
    }

    /**
     * Prepare field before render.
     *
     * @param Field $field
     */
    protected function prepareField(Field $field): void
    {
        if (!isset($field->attr['type'])) {
            $field->attr['type'] = $field->type;
        }

        if (!isset($field->attr['name'])) {
            $field->attr['name'] = $field->name;
        }

        if (!isset($field->attr['id']) && $field->id) {
            $field->attr['id'] = $field->id;
        }

        if (!isset($field->attr['placeholder']) && $field->label && !$field->inType(array('choice', 'radio', 'checkbox', 'hidden'))) {
            $field->attr['placeholder'] = $field->label;
        }

        if (!isset($field->label_attr['for']) && isset($field->attr['id'])) {
            $field->label_attr['for'] = $field->attr['id'];
        }
    }

    /**
     * Render input button.
     *
     * @param Field $button
     *
     * @return string
     */
    protected function inputButton(Field $button): string
    {
        $tag = 'button';
        $attr = $button->attr(array(
            'type' => $button->type,
        ));

        if ($button->isType('link')) {
            $tag = 'a';
            $attr['type'] = null;

            if (!isset($attr['href'])) {
                $attr['href'] = '#';
            }
        }

        return Element::tag($tag, $attr, true, $button->label);
    }

    /**
     * Render input field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputInput(Field $field): string
    {
        $attr = $field->attr(null, array(
            'value' => $field->value,
        ));

        return Element::tag('input', $attr);
    }

    /**
     * Render textarea field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputTextarea(Field $field): string
    {
        return Element::tag('textarea', $field->attr(), true, $field->value);
    }

    /**
     * Render password field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputPassword(Field $field): string
    {
        $attr = $field->attr(array(
            'value' => null,
        ));

        return Element::tag('input', $attr);
    }

    /**
     * Render checkbox field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputCheckbox(Field $field): string
    {
        $attr = $field->attr(array(
            'type' => 'checkbox',
        ));

        if (!array_key_exists('checked', $attr)) {
            $attr['checked'] = isset($attr['value']) ? $field->value == $attr['value'] : 'on' === $field->value;
        }

        $input = Element::tag('input', $attr);

        return Element::tag('label', null, true, $input.' '.$field->label);
    }

    /**
     * Render radio field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputRadio(Field $field): string
    {
        $attr = $field->attr(array(
            'type' => 'radio',
        ));

        if (!array_key_exists('checked', $attr)) {
            $attr['checked'] = isset($attr['value']) ? $field->value == $attr['value'] : 'on' === $field->value;
        }

        $input = Element::tag('input', $attr);

        return Element::tag('label', null, true, $input.' '.$field->label);
    }

    /**
     * Render choice field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function inputChoice(Field $field): string
    {
        if ($field->expanded) {
            if ($field->multiple) {
                return $this->checkboxGroup($field);
            }

            return $this->radioGroup($field);
        }

        return $this->dropdownGroup($field);
    }

    /**
     * Render dropdown/select field.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function dropdownGroup(Field $field): string
    {
        $check = (array) $field->value;
        $attr = $field->attr(null, array(
            'multiple' => $field->multiple,
        ));
        $itemAttr = $field->item_attr;
        $content = $field->placeholder ? '<option value="">'.$field->placeholder.'</option>' : '';

        foreach ($this->choiceItem($field) as $label => $value) {
            $itemAttr['value'] = $value;
            $itemAttr['selected'] = in_array($value, $check);

            $content .= Element::tag('option', $itemAttr, true, $label);
        }

        return Element::tag('select', $attr, true, $content);
    }

    /**
     * Render checkbox fields group.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function checkboxGroup(Field $field): string
    {
        $content = '';
        $check = (array) $this->fw->cast($field->value);
        $ctr = 0;
        $id = $field->attr['id'];
        $item = clone $field;

        $item->attr['name'] = $item->name.'[]';

        foreach ($this->choiceItem($field) as $label => $value) {
            $item->attr['value'] = $item->value = $value;
            $item->attr['checked'] = in_array($value, $check);
            $item->attr['id'] = $item->checkbox_id ?: $id.'_'.$ctr++;
            $item->label = $label;

            $content .= $this->inputCheckbox($item);
        }

        return Element::tag('div', $field->wrapper_attr, true, $content);
    }

    /**
     * Render radio fields group.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function radioGroup(Field $field): string
    {
        $content = '';
        $check = $this->fw->cast($field->value);
        $ctr = 0;
        $id = $field->attr['id'];
        $item = clone $field;

        foreach ($this->choiceItem($field) as $label => $value) {
            $item->attr['value'] = $item->value = $value;
            $item->attr['checked'] = $value === $check;
            $item->attr['id'] = $item->radio_id ?: $id.'_'.$ctr++;
            $item->label = $label;

            $content .= $this->inputRadio($item);
        }

        return Element::tag('div', $field->wrapper_attr, true, $content);
    }

    /**
     * Returns choice items.
     *
     * @param Field $field
     *
     * @return array
     */
    protected function choiceItem(Field $field): array
    {
        if (is_string($items = $field->items)) {
            $items = $this->fw->grab($items);
        }

        if (is_callable($items)) {
            $items = $items($this->fw, $field);
        }

        if (!is_array($items)) {
            throw new \LogicException('Choice items should be an array or a callable that returns array.');
        }

        return $items;
    }
}
