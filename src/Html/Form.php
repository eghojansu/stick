<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Html;

use Fal\Stick\App;
use Fal\Stick\Validation\Validator;

/**
 * Form helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Form
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var Html
     */
    protected $html;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * Form name.
     *
     * @var string
     */
    protected $name;

    /**
     * Submitted status.
     *
     * @var bool
     */
    protected $submitted = false;

    /**
     * Form method.
     *
     * @var string
     */
    protected $verb = 'POST';

    /**
     * Form data.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Submitted data.
     *
     * @var array
     */
    protected $submittedData;

    /**
     * Form fields.
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Form buttons.
     *
     * @var array
     */
    protected $buttons = array();

    /**
     * Form errors.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Class constructor.
     *
     * @param App       $app
     * @param Html      $html
     * @param Validator $validator
     */
    public function __construct(App $app, Html $html, Validator $validator)
    {
        $this->app = $app;
        $this->html = $html;
        $this->validator = $validator;

        if (!$this->name) {
            $this->setName(App::snakeCase(App::classname(static::class)));
        }

        $this->build();
    }

    /**
     * Returns the form name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the form name.
     *
     * @param string $name
     *
     * @return Form
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the form method.
     *
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * Sets the form method.
     *
     * @param string $verb
     *
     * @return Form
     */
    public function setVerb($verb)
    {
        $this->verb = strtoupper($verb);

        return $this;
    }

    /**
     * Returns form data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets form data.
     *
     * @param array $data
     *
     * @return Form
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns the submitted data.
     *
     * @return array
     */
    public function getSubmittedData()
    {
        if (null === $this->submittedData) {
            $key = 'REQUEST';

            if ('GET' === $this->verb) {
                $key = 'QUERY';
            } elseif ($this->app->exists($this->verb)) {
                $key = $this->verb;
            }

            $this->submittedData = $this->app->get($key.'.'.$this->name, array());
        }

        return $this->submittedData;
    }

    /**
     * Set submitted data.
     *
     * @param array|null $submittedData
     *
     * @return Form
     */
    public function setSubmittedData(array $submittedData = null)
    {
        $this->submittedData = $submittedData;

        return $this;
    }

    /**
     * Returns the form fields.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns the form buttons.
     *
     * @return array
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Returns the form errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
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
    public function add($field, $type = 'text', array $options = null)
    {
        $this->fields[$field] = compact('type', 'options') + array('rendered' => false);

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
    public function addButton($name, $type = 'submit', $label = null, array $attr = null)
    {
        $this->buttons[$name] = compact('type', 'label', 'attr');

        return $this;
    }

    /**
     * Returns form submitted state.
     *
     * @return bool
     */
    public function isSubmitted()
    {
        $data = $this->getSubmittedData() + array('_form' => null);

        $this->submitted = $this->verb === $this->app->get('VERB') && $data['_form'] === $this->name;

        return $this->submitted;
    }

    /**
     * Returns true if form validation success.
     *
     * @return bool
     *
     * @throws LogicException if the form is not submitted yet
     */
    public function valid()
    {
        if ($this->submitted) {
            list($rules, $messages) = $this->findRules();
            $valid = $this->validator->validate($this->submittedData, $rules, $messages);

            $no_validation_keys = array_diff(array_keys($this->fields), array_keys($rules));
            $no_validation = array_intersect_key($this->submittedData, array_flip($no_validation_keys));

            $this->data = $valid['data'] + $no_validation + $this->data;
            $this->errors = $valid['errors'];

            return $valid['success'];
        }

        throw new \LogicException('You can not validate unsubmitted form.');
    }

    /**
     * Returns form open tag.
     *
     * @param array|null $attr
     * @param bool       $multipart
     *
     * @return string
     */
    public function open(array $attr = null, $multipart = false)
    {
        $default = array(
            'enctype' => $multipart ? 'multipart/form-data' : null,
        );

        $attr['name'] = $this->name;
        $attr['method'] = $this->verb;

        return
            $this->html->element('form', false, null, $attr + $default).PHP_EOL.
            $this->html->hidden($this->formName('_form'), $this->name);
    }

    /**
     * Returns form close tag.
     *
     * @return string
     */
    public function close()
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
    public function row($name, array $overrideAttr = null)
    {
        $throw = !isset($this->fields[$name]);
        App::throws($throw, 'Field not exists: "'.$name.'".');

        $field = &$this->fields[$name];

        if ($field['rendered']) {
            return '';
        }

        $field['rendered'] = true;

        $type = strtolower($field['type']);
        $options = array_replace_recursive(((array) $field['options']) + array(
            'label' => $this->app->trans($name, null, App::titleCase($name)),
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
        $call = isset($types[$type]) ? $types[$type] : 'inputInput';

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
    public function rows(array $overrideAttr = null)
    {
        $fields = '';

        foreach ($this->fields as $name => $definitions) {
            $fields .= $this->row($name, $overrideAttr);
        }

        return $fields;
    }

    /**
     * Returns rendered buttons.
     *
     * @return string
     */
    public function buttons()
    {
        $buttons = '';

        foreach ($this->buttons as $name => $button) {
            $type = $button['type'];
            $label = $button['label'] ?: $this->app->trans($name, null, App::titleCase($name));

            $buttons .= $this->inputButton($label, $type, $name, $button['attr']);
        }

        return $this->renderRow('buttons', $buttons);
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
    public function render(array $attr = null, array $fieldAttr = null, array $buttonAttr = null)
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
     * @return Form
     */
    protected function build()
    {
        return $this;
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
    protected function renderRow($type, $input, $field = null, array $options = null)
    {
        if ('buttons' === $type) {
            return '<div>'.$input.'</div>'.PHP_EOL;
        }

        $errors = isset($this->errors[$field]) ? '<div>'.implode(', ', $this->errors[$field]).'</div>' : '';

        if (in_array($type, array('checkbox', 'radio'))) {
            return '<div>'.$input.$errors.'</div>'.PHP_EOL;
        }

        $label = $this->html->label($options['label'], $this->formName($field), $options['label_attr']);

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
    protected function inputButton($label, $type, $name, array $attr = null)
    {
        return $this->html->button($label, $type, $this->formName($name), $attr);
    }

    /**
     * Returns rendered common input type.
     *
     * @param string $field
     * @param string $value
     * @param array  $options
     * @param string $type
     *
     * @return string
     */
    protected function inputInput($field, $value, array $options, $type)
    {
        return $this->html->input($type, $this->formName($field), $value, $options['attr']);
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
    protected function inputText($field, $value, array $options)
    {
        return $this->html->text($this->formName($field), $value, $options['label'], $options['attr']);
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
    protected function inputHidden($field, $value, array $options)
    {
        return $this->html->hidden($this->formName($field), $value, $options['attr']);
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
    protected function inputPassword($field, $value, array $options)
    {
        return $this->html->password($this->formName($field), $options['label'], $options['attr']);
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
    protected function inputCheckbox($field, $value, array $options)
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

            if ($this->submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        return $this->html->checkbox($name, $raw, $options['label'], $options['attr'] + $default);
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
    protected function inputRadio($field, $value, array $options)
    {
        $default = array();
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        return $this->html->radio($this->formName($field), $raw, $options['label'], $options['attr'] + $default);
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
    protected function inputTextarea($field, $value, array $options)
    {
        return $this->html->textarea($this->formName($field), $value, $options['label'], $options['attr']);
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
    protected function inputCheckboxGroup($name, $value, array $attr, array $items = null, array $wrapperAttr = null)
    {
        $nameArr = $name.'[]';
        $content = '';
        $check = (array) $value;

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = in_array($val, $check);

            $content .= $this->inputCheckbox($nameArr, $val, array(
                'attr' => $attr,
                'label' => $label,
            ));
        }

        return $this->html->element('div', true, $content, $wrapperAttr);
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
    protected function inputRadioGroup($name, $value, array $attr, array $items = null, array $wrapperAttr = null)
    {
        $content = '';

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = $val === $value;

            $content .= $this->inputRadio($name, $val, array(
                'attr' => $attr,
                'label' => $label,
            ));
        }

        return $this->html->element('div', true, $content, $wrapperAttr);
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
    protected function inputSelect($name, $value, $label, array $attr, array $items = null, array $oAttr = null)
    {
        return $this->html->select($this->formName($name), $value, $label, $attr, $items, $oAttr);
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
    protected function inputChoice($field, $value, array $options)
    {
        $default = array(
            'item_attr' => null,
            'wrapper_attr' => array(),
            'items' => array(),
            'multiple' => false,
            'expanded' => false,
            'placeholder' => null,
        );
        $options += $default;

        $items = $options['items'];

        if ($items && is_callable($items)) {
            $items = $this->app->call($items, array($options));

            $throw = !is_array($items);
            App::throws($throw, 'The returned items should be an array.');
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
    protected function findRules()
    {
        $rules = array();
        $messages = array();

        foreach ($this->fields as $field => $definitions) {
            $options = ((array) $definitions['options']) + array(
                'messages' => null,
                'constraints' => null,
            );

            foreach ($options['messages'] ?: array() as $rule => $message) {
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
    protected function fieldValue($field)
    {
        if ($this->submitted) {
            return isset($this->submittedData[$field]) ? $this->submittedData[$field] : null;
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
    protected function rawValue($field)
    {
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }

    /**
     * Returns field name as the member of form data.
     *
     * @param string $field
     *
     * @return string
     */
    protected function formName($field)
    {
        return $this->name.'['.$field.']';
    }
}
