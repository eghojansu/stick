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

use Fal\Stick\Core;
use Fal\Stick\Html;
use Fal\Stick\Validation\Validator;

/**
 * Form helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Form
{
    /**
     * @var Core
     */
    protected $_fw;

    /**
     * @var Validator
     */
    protected $_validator;

    /**
     * @var Html
     */
    protected $_html;

    /**
     * Form name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Submitted status.
     *
     * @var bool
     */
    protected $_submitted = false;

    /**
     * Form method.
     *
     * @var string
     */
    protected $_verb = 'POST';

    /**
     * Initial data.
     *
     * @var array
     */
    protected $_data;

    /**
     * Data after transformed.
     *
     * @var array
     */
    protected $_formData;

    /**
     * Submitted data.
     *
     * @var array
     */
    protected $_submittedData;

    /**
     * Submitted data after reverse transformed.
     *
     * @var array
     */
    protected $_normalizedData;

    /**
     * Validated data.
     *
     * @var array
     */
    protected $_validatedData;

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * Form fields.
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Form buttons.
     *
     * @var array
     */
    protected $_buttons = array();

    /**
     * Form errors.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Class constructor.
     *
     * @param Core        $fw
     * @param Validator $validator
     * @param Html      $html
     */
    public function __construct(Core $fw, Validator $validator, Html $html)
    {
        $this->_fw = $fw;
        $this->_html = $html;
        $this->_validator = $validator;

        if (!$this->_name) {
            $this->_name = $fw->snakeCase($fw->className($this));
        }
    }

    /**
     * Returns name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Sets name.
     *
     * @param string $name
     *
     * @return Form
     */
    public function setName(string $name): Form
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Returns method.
     *
     * @return string
     */
    public function getVerb(): string
    {
        return $this->_verb;
    }

    /**
     * Sets method.
     *
     * @param string $verb
     *
     * @return Form
     */
    public function setVerb(string $verb): Form
    {
        $this->_verb = strtoupper($verb);

        return $this;
    }

    /**
     * Returns data.
     *
     * @param bool $submitted
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->_validatedData ?? $this->_normalizedData ?? $this->_data;
    }

    /**
     * Sets data.
     *
     * @param array $data
     *
     * @return Form
     */
    public function setData(array $data): Form
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets options.
     *
     * @param array $options
     *
     * @return Form
     */
    public function setOptions(array $options): Form
    {
        foreach ($options as $option => $value) {
            $this->_options[$option] = $value;
        }

        return $this;
    }

    /**
     * Returns field.
     *
     * @param string $field
     *
     * @return array|null
     */
    public function getField(string $field): ?array
    {
        return $this->_fields[$field] ?? null;
    }

    /**
     * Returns fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->_fields;
    }

    /**
     * Returns buttons.
     *
     * @return array
     */
    public function getButtons(): array
    {
        return $this->_buttons;
    }

    /**
     * Returns field error.
     *
     * @param string $field
     *
     * @return array|null
     */
    public function getError(string $field): ?array
    {
        return $this->_errors[$field] ?? null;
    }

    /**
     * Returns errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Add field.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     * @param array|null $attr
     *
     * @return Form
     */
    public function add(string $name, string $type = null, array $options = null, array $attr = null): Form
    {
        $attr['type'] = $type ?? 'text';
        $attr['name'] = $this->formName($name);

        if (!isset($attr['id'])) {
            $attr['id'] = $this->fixId($attr['name']);
        }

        if (!isset($options['label'])) {
            $options['label'] = $this->label($name);
        }

        if (!isset($options['label_attr']['for'])) {
            $options['label_attr']['for'] = $attr['id'];
        }

        if (!isset($options['constraints'])) {
            $options['constraints'] = null;
        }

        if (!isset($options['messages'])) {
            $options['messages'] = array();
        }

        if (!isset($attr['placeholder']) && !in_array($type, array('choice', 'radio', 'checkbox', 'hidden'))) {
            $attr['placeholder'] = $options['label'];
        }

        if ($type && in_array($type, array('choice', 'textarea'))) {
            unset($attr['type']);
        }

        $this->_fields[$name] = array(
            'type' => $type ?? 'text',
            'options' => $options + array(
                'transformer' => null,
                'reverse_transformer' => null,
            ),
            'attr' => $attr,
            'rendered' => false,
        );

        return $this;
    }

    /**
     * Add button.
     *
     * @param string      $name
     * @param string|null $type
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return Form
     */
    public function addButton(string $name, string $type = null, string $label = null, array $attr = null): Form
    {
        $attr['name'] = $this->formName($name);

        if (!isset($attr['id'])) {
            $attr['id'] = $this->fixId($attr['name']);
        }

        $this->_buttons[$name] = array(
            'type' => $type ?? 'button',
            'attr' => $attr,
            'label' => $label ?? $this->label($name),
        );

        return $this;
    }

    /**
     * Shortcut to call setData, prepare, isSubmitted and valid.
     *
     * @param array|null $options
     * @param array|null $data
     *
     * @return bool
     */
    public function posted(array $options = null, array $data = null): bool
    {
        if ($this->setData($data ?? array())->prepare($options)->isSubmitted()) {
            return $this->valid();
        }

        return false;
    }

    /**
     * Prepare form.
     *
     * @param array|null $options
     *
     * @return Form
     */
    public function prepare(array $options = null): Form
    {
        $this->build($options ?? array());
        $this->transformData();

        return $this;
    }

    /**
     * Returns true if form is submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        $this->_submittedData = (array) $this->_fw->get($this->_verb.'.'.$this->_name) + array('_form' => null);
        $this->_submitted = $this->_verb === $this->_fw->get('VERB') && $this->_submittedData['_form'] === $this->_name;
        $this->reverseTransformData();

        return $this->_submitted;
    }

    /**
     * Returns true if validation success.
     *
     * @return bool
     *
     * @throws LogicException if form is not submitted yet
     */
    public function valid(): bool
    {
        if (!$this->_submitted) {
            throw new \LogicException('Cannot validate unsubmitted form.');
        }

        list($rules, $messages) = $this->findRules();
        $result = $this->_validator->validate($this->_normalizedData ?? array(), $rules, $messages);

        $no_validation_keys = array_diff(array_keys($this->_fields), array_keys($rules));
        $no_validation_data = array_intersect_key($this->_normalizedData ?? array(), array_flip($no_validation_keys));

        $this->_validatedData = $result['data'] + $no_validation_data + ($this->_formData ?? array());
        $this->_errors = $result['errors'];

        return $result['success'];
    }

    /**
     * Returns open tag.
     *
     * @param array|null $attr
     * @param bool       $multipart
     *
     * @return string
     */
    public function open(array $attr = null, bool $multipart = false): string
    {
        $default = array(
            'enctype' => $multipart ? 'multipart/form-data' : null,
        );

        $attr['name'] = $this->_name;
        $attr['method'] = $this->_verb;

        return
            $this->_html->tag('form', $attr + $default).PHP_EOL.
            $this->_html->tag('input', array(
                'type' => 'hidden',
                'name' => $this->formName('_form'),
                'value' => $this->_name,
            ));
    }

    /**
     * Returns close tag.
     *
     * @return string
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * Returns rendered field.
     *
     * @param string     $name
     * @param array|null $overrideAttr
     *
     * @return string
     *
     * @throws LogicException if field not exists
     */
    public function row(string $name, array $overrideAttr = null): string
    {
        if (!isset($this->_fields[$name])) {
            throw new \LogicException(sprintf('Field "%s" does not exists.', $name));
        }

        $field = &$this->_fields[$name];

        if ($field['rendered']) {
            return '';
        }

        $field['rendered'] = true;

        $value = $this->fieldValue($name);
        $input = 'input'.$field['type'];

        if (!method_exists($this, $input)) {
            $input = 'inputInput';
        }

        $content = $this->$input($name, $value, $field['attr'], $field['options']);
        $row = $this->renderRow($content, $field['type'], $name, $field['options']);

        unset($field);

        return $row;
    }

    /**
     * Returns rendered fields.
     *
     * @param array|null $overrideAttr
     *
     * @return string
     */
    public function rows(array $overrideAttr = null): string
    {
        $fields = '';

        foreach ($this->_fields as $name => $definitions) {
            $fields .= $this->row($name, $overrideAttr);
        }

        return $fields;
    }

    /**
     * Returns rendered buttons.
     *
     * @return string
     */
    public function buttons(): string
    {
        $buttons = '';

        foreach ($this->_buttons as $name => $button) {
            $label = $button['label'] ?: $this->label($name);

            $buttons .= ' '.$this->inputButton($label, $name, $button['attr'], $button['type']);
        }

        return $this->renderRow(trim($buttons), 'buttons');
    }

    /**
     * Returns rendered form.
     *
     * @param array|null $attr
     * @param array|null $fieldAttr
     * @param array|null $buttonAttr
     *
     * @return string
     */
    public function render(array $attr = null, array $fieldAttr = null, array $buttonAttr = null): string
    {
        return
            $this->open($attr).PHP_EOL.
            $this->rows($fieldAttr).PHP_EOL.
            $this->buttons($buttonAttr).PHP_EOL.
            $this->close();
    }

    /**
     * Returns the formatted row.
     *
     * @param string      $input
     * @param string      $type
     * @param string|null $name
     * @param array|null  $options
     *
     * @return string
     */
    protected function renderRow(string $input, string $type, string $name = null, array $options = null): string
    {
        if ('hidden' === $type) {
            return $input.PHP_EOL;
        }

        if ('buttons' === $type) {
            return '<div>'.$input.'</div>'.PHP_EOL;
        }

        $errors = isset($this->_errors[$name]) ? $this->_html->tag('div', null, true, implode(', ', $this->_errors[$name])) : '';

        if (in_array($type, array('checkbox', 'radio'))) {
            return '<div>'.$input.$errors.'</div>'.PHP_EOL;
        }

        $label = $this->_html->tag('label', $options['label_attr'], true, $options['label']);

        return '<div>'.
            $label.
            ' <span>'.$input.$errors.'</span>'.
            '</div>'.
            PHP_EOL;
    }

    /**
     * Returns rendered input button.
     *
     * @param string $label
     * @param string $name
     * @param array  $attr
     * @param string $type
     *
     * @return string
     */
    protected function inputButton(string $label, string $name, array $attr, string $type = 'button'): string
    {
        $tag = 'button';
        $add = array();

        if ('link' === $type) {
            $tag = 'a';
            $add = array('href' => '#');
        }

        return $this->_html->tag($tag, $attr + $add, true, $label);
    }

    /**
     * Returns rendered common input tag.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function inputInput(string $field, $value, array $attr, array $options): string
    {
        $add = array('value' => $value);

        return $this->_html->tag('input', $attr + $add);
    }

    /**
     * Returns the rendered input password.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function inputPassword(string $field, $value, array $attr, array $options): string
    {
        $add = array('value' => null);

        return $this->inputInput($field, $value, $add + $attr, $options);
    }

    /**
     * Returns the rendered input checkbox.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function inputCheckbox(string $field, $value, array $attr, array $options): string
    {
        $add = array();
        $attr['type'] = 'checkbox';
        $value = $this->_fw->pick('value', $attr);

        if (!array_key_exists('checked', $attr)) {
            $submitted = $this->fieldValue($field);

            $add['checked'] = null === $value ? 'on' === $submitted : $submitted == $value;
        }

        $input = $this->inputInput($field, $value, $attr + $add, $options);

        return $this->_html->tag('label', null, true, $input.' '.$options['label']);
    }

    /**
     * Returns the rendered input radio.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function inputRadio(string $field, $value, array $attr, array $options): string
    {
        $add = array();
        $attr['type'] = 'radio';
        $value = $this->_fw->pick('value', $attr);

        if (!array_key_exists('checked', $attr)) {
            $submitted = $this->fieldValue($field);

            $add['checked'] = null === $value ? 'on' === $submitted : $submitted == $value;
        }

        $input = $this->inputInput($field, $value, $attr + $add, $options);

        return $this->_html->tag('label', null, true, $input.' '.$options['label']);
    }

    /**
     * Returns the rendered input textarea.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function inputTextarea(string $field, $value, array $attr, array $options): string
    {
        return $this->_html->tag('textarea', $attr, true, $value);
    }

    /**
     * Returns the rendered input choice.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     *
     * @throws LogicException if the returned items callable is not an array
     */
    protected function inputChoice(string $field, $value, array $attr, array $options): string
    {
        $options += array(
            'item_attr' => array(),
            'wrapper_attr' => array(),
            'items' => array(),
            'multiple' => false,
            'expanded' => false,
            'placeholder' => '',
        );

        $items = &$options['items'];

        if ($items && is_callable($items)) {
            $items = (array) $this->_fw->call($items, array($options));
        }

        if ($options['expanded']) {
            if ($options['multiple']) {
                return $this->checkboxGroup($field, $value, $attr, $options);
            }

            return $this->radioGroup($field, $value, $attr, $options);
        }

        $attr['multiple'] = $options['multiple'];
        unset($items);

        return $this->select($field, $value, $attr, $options);
    }

    /**
     * Returns rendered select input.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function select(string $name, $value, array $attr, array $options): string
    {
        $check = (array) $value;

        $content = $options['placeholder'] ? '<option value="">'.$options['placeholder'].'</option>' : '';

        foreach ($options['items'] as $label => $val) {
            $add = array(
                'value' => $val,
                'selected' => in_array($val, $check),
            );

            $content .= $this->_html->tag('option', $add + $options['item_attr'], true, $label);
        }

        return $this->_html->tag('select', $attr, true, $content);
    }

    /**
     * Returns the rendered group of input checkbox.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function checkboxGroup(string $name, $value, array $attr, array $options): string
    {
        $nameArr = $this->formName($name).'[]';
        $content = '';
        $check = (array) $this->_fw->cast($value);
        $ctr = 0;
        $id = $attr['id'];

        foreach ($options['items'] as $label => $val) {
            $attr['value'] = $val;
            $attr['checked'] = in_array($val, $check);
            $attr['name'] = $nameArr;
            $attr['id'] = $options['checkbox_id'] ?? $id.'_'.$ctr++;

            $content .= $this->inputCheckbox($name, $val, $attr, array(
                'label' => $label,
            ));
        }

        return $this->_html->tag('div', $options['wrapper_attr'], true, $content);
    }

    /**
     * Returns the rendered group of input radio.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $attr
     * @param array  $options
     *
     * @return string
     */
    protected function radioGroup(string $name, $value, array $attr, array $options): string
    {
        $content = '';
        $check = $this->_fw->cast($value);
        $ctr = 0;
        $id = $attr['id'];

        foreach ($options['items'] as $label => $val) {
            $attr['value'] = $val;
            $attr['checked'] = $val === $check;
            $attr['id'] = $options['radio_id'] ?? $id.'_'.$ctr++;

            $content .= $this->inputRadio($name, $val, $attr, array(
                'label' => $label,
            ));
        }

        return $this->_html->tag('div', $options['wrapper_attr'], true, $content);
    }

    /**
     * Collect rule constraints and messages.
     *
     * @return array
     */
    protected function findRules(): array
    {
        $rules = array();
        $messages = array();

        foreach ($this->_fields as $field => $def) {
            foreach ($def['options']['messages'] as $rule => $message) {
                $messages[$field.'.'.$rule] = $message;
            }

            $rules[$field] = $def['options']['constraints'];
        }

        return array(array_filter($rules), $messages);
    }

    /**
     * To add field logic.
     *
     * @param array $options
     */
    protected function build(array $options)
    {
        // to override by children
    }

    /**
     * Run each field transformer from original data.
     */
    protected function transformData(): void
    {
        foreach ($this->_fields as $name => $field) {
            $value = $this->_data[$name] ?? null;

            if (is_callable($field['options']['transformer'])) {
                $value = $this->_fw->call($field['options']['transformer'], array($value));
            }

            $this->_formData[$name] = $value;
        }
    }

    /**
     * Run each field reverse transfomer from submitted data.
     */
    protected function reverseTransformData(): void
    {
        foreach ($this->_fields as $name => $field) {
            $value = $this->_submittedData[$name] ?? null;

            if (is_callable($field['options']['reverse_transformer'])) {
                $value = $this->_fw->call($field['options']['reverse_transformer'], array($value));
            }

            $this->_normalizedData[$name] = $value;
        }
    }

    /**
     * Returns the field value.
     *
     * @param string $field
     *
     * @return mixed
     */
    protected function fieldValue(string $field)
    {
        if ($this->_submitted) {
            return $this->_fw->pick($field, $this->_normalizedData, $this->_submittedData[$field] ?? null);
        }

        return $this->rawValue($field);
    }

    /**
     * Returns raw value.
     *
     * @param string $field
     *
     * @return mixed
     */
    protected function rawValue(string $field)
    {
        return $this->_fw->pick($field, $this->_formData, $this->_data[$field] ?? null);
    }

    /**
     * Returns field name as the member of form data.
     *
     * @param string $field
     *
     * @return string
     */
    protected function formName(string $field): string
    {
        return $this->_name.'['.$field.']';
    }

    /**
     * Convert form name to id.
     *
     * @param string|null $name
     *
     * @return string|null
     */
    protected function fixId(string $name = null): ?string
    {
        return $name ? rtrim(str_replace(array('"', "'", '[', ']'), array('', '', '_', '_'), $name), '_') : null;
    }

    /**
     * Returns label for name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function label(string $name): string
    {
        $default = ucwords(str_replace('_', ' ', $this->_fw->snakeCase($name)));

        return $this->_fw->trans($name, null, $default);
    }

    /**
     * Returns member of data.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->_fw->pick($key, array($this->_validatedData, $this->_normalizedData, $this->_data), null, true);
    }
}
