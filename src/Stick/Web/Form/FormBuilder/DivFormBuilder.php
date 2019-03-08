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

use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Util;
use Fal\Stick\Web\Form\Button;
use Fal\Stick\Web\Form\Field;
use Fal\Stick\Web\Form\FormBuilderInterface;

/**
 * Basic form builder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class DivFormBuilder implements FormBuilderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     * @param array              $options
     */
    public function __construct(ContainerInterface $container, array $options = null)
    {
        $this->container = $container;
        $this->setOptions($options ?? array());
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
        return Util::tag('form', $attr);
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
        if ($field instanceof Button) {
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
        return $errors ? Util::tag('div', null, true, implode(', ', $errors)) : '';
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

        if ($field->inType(array('checkbox', 'radio')) || $field instanceof Button) {
            return '<div>'.$input.$errors.'</div>';
        }

        return '<div>'.
            Util::tag('label', $field->label_attr, true, $field->label).
            ' <span>'.$input.$errors.'</span>'.
            '</div>'
        ;
    }

    /**
     * Prepare button before render.
     *
     * @param Button $button
     */
    protected function prepareButton(Button $button): void
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
     * @param Button $button
     *
     * @return string
     */
    protected function inputButton(Button $button): string
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

        return Util::tag($tag, $attr, true, $button->label);
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

        return Util::tag('input', $attr);
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
        return Util::tag('textarea', $field->attr(), true, $field->value);
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

        return Util::tag('input', $attr);
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

        $input = Util::tag('input', $attr);

        return Util::tag('label', null, true, $input.' '.$field->label);
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

        $input = Util::tag('input', $attr);

        return Util::tag('label', null, true, $input.' '.$field->label);
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

            $content .= Util::tag('option', $itemAttr, true, $label);
        }

        return Util::tag('select', $attr, true, $content);
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
        $check = (array) Util::cast($field->value);
        $ctr = 0;
        $id = $field->attr['id'];
        $item = clone $field;

        $item->attr['name'] = $item->name.'[]';

        foreach ($this->choiceItem($field) as $label => $value) {
            $item->attr['value'] = $item->value = $value;
            $item->attr['checked'] = in_array($value, $check);
            $item->attr['id'] = $item->checkbox_id ?? $id.'_'.$ctr++;
            $item->label = $label;

            $content .= $this->inputCheckbox($item);
        }

        return Util::tag('div', $field->wrapper_attr, true, $content);
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
        $check = Util::cast($field->value);
        $ctr = 0;
        $id = $field->attr['id'];
        $item = clone $field;

        foreach ($this->choiceItem($field) as $label => $value) {
            $item->attr['value'] = $item->value = $value;
            $item->attr['checked'] = $value === $check;
            $item->attr['id'] = $item->radio_id ?? $id.'_'.$ctr++;
            $item->label = $label;

            $content .= $this->inputRadio($item);
        }

        return Util::tag('div', $field->wrapper_attr, true, $content);
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
            $items = $this->container->grab($items);
        }

        if (is_callable($items)) {
            $items = $this->container->call($items, array($field));
        }

        if ($items && !is_array($items)) {
            throw new \LogicException('Choice items should be an array or a callable that returns array.');
        }

        return $items ?: array();
    }
}
