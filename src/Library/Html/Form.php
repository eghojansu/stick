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

namespace Fal\Stick\Library\Html;

use Fal\Stick\Fw;
use Fal\Stick\Library\Str;
use Fal\Stick\Library\Validation\Validator;
use Fal\Stick\Magic;

/**
 * Form helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Form extends Magic implements \IteratorAggregate
{
    /**
     * @var Fw
     */
    protected $_fw;

    /**
     * @var Html
     */
    protected $_html;

    /**
     * @var Validator
     */
    protected $_validator;

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
     * Form data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Submitted data.
     *
     * @var array
     */
    protected $_submittedData;

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
     * @param Fw        $fw
     * @param Html      $html
     * @param Validator $validator
     */
    public function __construct(Fw $fw, Html $html, Validator $validator)
    {
        $this->_fw = $fw;
        $this->_html = $html;
        $this->_validator = $validator;

        if (!$this->_name) {
            $this->setName(Str::snakeCase(Str::classname(static::class)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * {@inheritdoc}
     */
    public function &get(string $key)
    {
        if (!$this->exists($key)) {
            $this->_data[$key] = null;
        }

        return $this->_data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $val): Magic
    {
        $this->_data[$key] = $val;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): Magic
    {
        unset($this->_data[$key]);

        return $this;
    }

    /**
     * Retrieve external iterator for data.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_data);
    }

    /**
     * Returns the form name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Sets the form name.
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
     * Returns the form method.
     *
     * @return string
     */
    public function getVerb(): string
    {
        return $this->_verb;
    }

    /**
     * Sets the form method.
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
     * Returns form data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * Sets form data.
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
     * Returns the submitted data.
     *
     * @return array
     */
    public function getSubmittedData(): array
    {
        if (null === $this->_submittedData) {
            $this->_submittedData = $this->_fw->get($this->_verb.'.'.$this->_name) ?? array();
        }

        return $this->_submittedData;
    }

    /**
     * Set submitted data.
     *
     * @param array|null $submittedData
     *
     * @return Form
     */
    public function setSubmittedData(array $submittedData = null): Form
    {
        $this->_submittedData = $submittedData;

        return $this;
    }

    /**
     * Returns the form fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->_fields;
    }

    /**
     * Returns true if field exists.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function fieldExists(string $name): bool
    {
        return isset($this->_fields[$name]);
    }

    /**
     * Returns the form buttons.
     *
     * @return array
     */
    public function getButtons(): array
    {
        return $this->_buttons;
    }

    /**
     * Returns true if button exists.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function buttonExists(string $name): bool
    {
        return isset($this->_buttons[$name]);
    }

    /**
     * Returns the form errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Add form field.
     *
     * @param string     $field
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function add(string $field, string $type = 'text', array $options = null): Form
    {
        $this->_fields[$field] = compact('type', 'options') + array('rendered' => false);

        return $this;
    }

    /**
     * Add form button.
     *
     * @param string      $name
     * @param string      $type
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return Form
     */
    public function addButton(string $name, string $type = 'submit', string $label = null, array $attr = null): Form
    {
        $this->_buttons[$name] = compact('type', 'label', 'attr');

        return $this;
    }

    /**
     * Build form.
     *
     * @param array|null $options
     * @param array|null $data
     *
     * @return Form
     */
    public function build(array $options = null, array $data = null): Form
    {
        if ($data) {
            $this->setData($data);
        }

        $this->doBuild((array) $options);

        return $this;
    }

    /**
     * Returns form submitted state.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        $data = $this->getSubmittedData() + array('_form' => null);

        $this->_submitted = $this->_verb === $this->_fw->get('VERB') && $data['_form'] === $this->_name;

        return $this->_submitted;
    }

    /**
     * Returns true if form validation success.
     *
     * @return bool
     *
     * @throws LogicException if the form is not submitted yet
     */
    public function valid(): bool
    {
        if (!$this->_submitted) {
            throw new \LogicException('You can not validate unsubmitted form.');
        }

        list($rules, $messages) = $this->findRules();
        $valid = $this->_validator->validate($this->_submittedData, $rules, $messages);

        $no_validation_keys = array_diff(array_keys($this->_fields), array_keys($rules));
        $no_validation = array_intersect_key($this->_submittedData, array_flip($no_validation_keys));

        $this->_data = $valid['data'] + $no_validation + $this->_data;
        $this->_errors = $valid['errors'];

        return $valid['success'];
    }

    /**
     * Returns form open tag.
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
            $this->_html->element('form', false, null, $attr + $default).PHP_EOL.
            $this->_html->hidden($this->formName('_form'), $this->_name);
    }

    /**
     * Returns form close tag.
     *
     * @return string
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * Returns rendered form field.
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
            throw new \LogicException('Field not exists: "'.$name.'".');
        }

        $field = &$this->_fields[$name];

        if ($field['rendered']) {
            return '';
        }

        $field['rendered'] = true;

        $type = strtolower($field['type']);
        $options = array_replace_recursive(((array) $field['options']) + array(
            'label' => $this->_fw->trans($name, null, Str::titleCase($name)),
            'label_attr' => array(),
            'attr' => array(),
        ), array('attr' => (array) $overrideAttr));
        $value = $this->fieldValue($name);
        $types = array(
            'text' => 'inputText',
            'hidden' => 'inputHidden',
            'password' => 'inputPassword',
            'textarea' => 'inputTextarea',
            'checkbox' => 'inputCheckbox',
            'radio' => 'inputRadio',
            'choice' => 'inputChoice',
        );
        $call = $types[$type] ?? 'inputInput';

        $input = $this->$call($name, $value, $options, $type);

        return $this->renderRow($type, $input, $name, $options);
    }

    /**
     * Returns rendered form fields.
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
            $type = $button['type'];
            $label = $button['label'] ?: $this->_fw->trans($name, null, Str::titleCase($name));

            $buttons .= ' '.$this->inputButton($label, $type, $name, $button['attr']);
        }

        return $this->renderRow('buttons', trim($buttons));
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
            $this->close().PHP_EOL;
    }

    /**
     * Build this form.
     *
     * Override this method to add fields.
     *
     * @param array $options
     */
    protected function doBuild(array $options)
    {
    }

    /**
     * Returns the formatted row.
     *
     * @param string      $type
     * @param string      $input
     * @param string|null $field
     * @param array|null  $options
     *
     * @return string
     */
    protected function renderRow(string $type, string $input, string $field = null, array $options = null): string
    {
        if ('buttons' === $type) {
            return '<div>'.$input.'</div>'.PHP_EOL;
        }

        $errors = isset($this->_errors[$field]) ? '<div>'.implode(', ', $this->_errors[$field]).'</div>' : '';

        if (in_array($type, array('checkbox', 'radio'))) {
            return '<div>'.$input.$errors.'</div>'.PHP_EOL;
        }

        $label = $this->_html->label($options['label'], $this->formName($field), $options['label_attr']);

        return '<div>'.
            $label.
            ' <span>'.
            $input.$errors.
            '</span></div>'.
            PHP_EOL;
    }

    /**
     * Returns rendered input button.
     *
     * @param string     $label
     * @param string     $type
     * @param string     $name
     * @param array|null $attr
     *
     * @return string
     */
    protected function inputButton(string $label, string $type, string $name, array $attr = null): string
    {
        if ('a' === $type) {
            return $this->_html->element('a', true, $label, ((array) $attr) + array('href' => '#'));
        }

        return $this->_html->button($label, $type, $this->formName($name), $attr);
    }

    /**
     * Returns rendered common input type.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     * @param string $type
     *
     * @return string
     */
    protected function inputInput(string $field, $value, array $options, string $type): string
    {
        return $this->_html->input($type, $this->formName($field), $value, $options['attr']);
    }

    /**
     * Returns the rendered input text.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputText(string $field, $value, array $options): string
    {
        return $this->_html->text($this->formName($field), $value, $options['label'], $options['attr']);
    }

    /**
     * Returns the rendered input hidden.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputHidden(string $field, $value, array $options): string
    {
        return $this->_html->hidden($this->formName($field), $value, $options['attr']);
    }

    /**
     * Returns the rendered input password.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputPassword(string $field, $value, array $options): string
    {
        return $this->_html->password($this->formName($field), $options['label'], $options['attr']);
    }

    /**
     * Returns the rendered input checkbox.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputCheckbox(string $field, $value, array $options): string
    {
        $default = array();
        $raw = $value;
        $name = $this->formName($field);

        if (false !== ($pos = strpos($field, '['))) {
            $realname = substr($field, 0, $pos);
            $suffix = substr($field, $pos);
            $name = $this->formName($realname).$suffix;
        }

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->_submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        return $this->_html->checkbox($name, $raw, $options['label'], $options['attr'] + $default);
    }

    /**
     * Returns the rendered input radio.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputRadio(string $field, $value, array $options): string
    {
        $default = array();
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->_submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        return $this->_html->radio($this->formName($field), $raw, $options['label'], $options['attr'] + $default);
    }

    /**
     * Returns the rendered input textarea.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    protected function inputTextarea(string $field, $value, array $options): string
    {
        return $this->_html->textarea($this->formName($field), $value, $options['label'], $options['attr']);
    }

    /**
     * Returns the rendered group of input checkbox.
     *
     * @param string     $name
     * @param mixed      $value
     * @param array      $attr
     * @param array|null $items
     * @param array|null $wrapperAttr
     *
     * @return string
     */
    protected function inputCheckboxGroup(string $name, $value, array $attr, array $items = null, array $wrapperAttr = null): string
    {
        $nameArr = $name.'[]';
        $content = '';
        $check = (array) $value;

        foreach ((array) $items as $label => $val) {
            $attr['checked'] = in_array($val, $check);

            $content .= $this->inputCheckbox($nameArr, $val, array(
                'attr' => $attr,
                'label' => $label,
            ));
        }

        return $this->_html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * Returns the rendered group of input radio.
     *
     * @param string     $name
     * @param mixed      $value
     * @param array      $attr
     * @param array|null $items
     * @param array|null $wrapperAttr
     *
     * @return string
     */
    protected function inputRadioGroup(string $name, $value, array $attr, array $items = null, array $wrapperAttr = null): string
    {
        $content = '';

        foreach ((array) $items as $label => $val) {
            $attr['checked'] = $val === $value;

            $content .= $this->inputRadio($name, $val, array(
                'attr' => $attr,
                'label' => $label,
            ));
        }

        return $this->_html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * Returns rendered select input.
     *
     * @param string     $name
     * @param mixed      $value
     * @param string     $label
     * @param array      $attr
     * @param array|null $items
     * @param array|null $oAttr
     *
     * @return string
     */
    protected function inputSelect(string $name, $value, string $label, array $attr, array $items = null, array $oAttr = null): string
    {
        return $this->_html->select($this->formName($name), $value, $label, $attr, $items, $oAttr);
    }

    /**
     * Returns the rendered input choice.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     *
     * @throws LogicException if the returned items callable is not an array
     */
    protected function inputChoice(string $field, $value, array $options): string
    {
        $default = array(
            'item_attr' => null,
            'wrapper_attr' => array(),
            'items' => array(),
            'multiple' => false,
            'expanded' => false,
            'placeholder' => '',
        );
        $options += $default;

        $items = $options['items'];

        if ($items && is_callable($items)) {
            $items = $this->_fw->call($items, array($options));

            if (!is_array($items)) {
                throw new \LogicException('The returned items should be an array.');
            }
        }

        if ($options['expanded']) {
            if ($options['multiple']) {
                return $this->inputCheckboxGroup($field, $value, $options['attr'], $items, $options['wrapper_attr']);
            }

            return $this->inputRadioGroup($field, $value, $options['attr'], $items, $options['wrapper_attr']);
        }

        $options['attr']['multiple'] = $options['multiple'];

        return $this->inputSelect($field, $value, $options['placeholder'], $options['attr'], $items, $options['item_attr']);
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

        foreach ($this->_fields as $field => $definitions) {
            $options = ((array) $definitions['options']) + array(
                'messages' => null,
                'constraints' => null,
            );

            foreach ((array) $options['messages'] as $rule => $message) {
                $messages[$field.'.'.$rule] = $message;
            }

            if ($options['constraints']) {
                $rules[$field] = $options['constraints'];
            }
        }

        return array($rules, $messages);
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
            return $this->_submittedData[$field] ?? null;
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
        return $this->_data[$field] ?? null;
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
}
