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

namespace Fal\Stick\Web\Form;

use Fal\Stick\Web\Request;
use Fal\Stick\Translation\TranslatorInterface;
use Fal\Stick\Util;
use Fal\Stick\Validation\Result;
use Fal\Stick\Validation\Validator;

/**
 * Form class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Form implements \ArrayAccess
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Result
     */
    protected $result;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * @var bool
     */
    protected $submitted = false;

    /**
     * @var array
     */
    protected $initialData = array();

    /**
     * @var array
     */
    protected $formData = array();

    /**
     * @var array
     */
    protected $validatedData = array();

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var array
     */
    protected $buttons = array();

    /**
     * Class constructor.
     *
     * @param Validator            $validator
     * @param TranslatorInterface  $translator
     * @param FormBuilderInterface $formBuilder
     */
    public function __construct(Validator $validator, TranslatorInterface $translator, FormBuilderInterface $formBuilder)
    {
        $this->validator = $validator;
        $this->translator = $translator;
        $this->formBuilder = $formBuilder;

        if (!$this->name) {
            $this->name = Util::snakeCase(Util::className($this));
        }
    }

    /**
     * {inheritdoc}.
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->validatedData) || array_key_exists($key, $this->initialData) || array_key_exists($key, $this->formData);
    }

    /**
     * {inheritdoc}.
     */
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->validatedData)) {
            return $this->validatedData[$key];
        }

        if (array_key_exists($key, $this->initialData)) {
            return $this->initialData[$key];
        }

        return $this->formData[$key] ?? null;
    }

    /**
     * {inheritdoc}.
     */
    public function offsetSet($key, $value)
    {
        $this->initialData[$key] = $value;
    }

    /**
     * {inheritdoc}.
     */
    public function offsetUnset($key)
    {
        unset($this->initialData[$key]);
    }

    /**
     * Returns form name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Assign form name.
     *
     * @param string $name
     *
     * @return Form
     */
    public function setName(string $name): Form
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns form method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Assign form method.
     *
     * @param string $method
     *
     * @return Form
     */
    public function setMethod(string $method): Form
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Returns form attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Assign form attributes.
     *
     * @param array $attributes
     *
     * @return Form
     */
    public function setAttributes(array $attributes): Form
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Returns initial data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->initialData;
    }

    /**
     * Assign initial data.
     *
     * @param array $initialData
     *
     * @return Form
     */
    public function setData(array $initialData): Form
    {
        $this->initialData = $initialData;

        return $this;
    }

    /**
     * Returns form data.
     *
     * @return array
     */
    public function getFormData(): array
    {
        if (!$this->request) {
            throw new \LogicException('Cannot get form data.');
        }

        if (!$this->formData) {
            if ('GET' === $this->method) {
                $this->formData = $this->request->query->all();
            } else {
                $this->formData = $this->request->request->all();
            }
        }

        return $this->formData;
    }

    /**
     * Returns validated data.
     *
     * @return array
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Handle request, assign data and options.
     *
     * @param Request    $request
     * @param array|null $data
     * @param array|null $options
     *
     * @return Form
     */
    public function handle(Request $request, array $data = null, array $options = null): Form
    {
        $this->request = $request;

        if ($data) {
            $this->setData($data);
        }

        $this->build($options ?? array());

        return $this;
    }

    /**
     * Returns true if form is submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        if (!$this->request) {
            throw new \LogicException('Cannot handle form without a request.');
        }

        $this->submitted = $this->request->isMethod($this->method);

        if ($this->submitted) {
            $data = $this->getFormData();

            $this->submitted = isset($data['_form']) && $data['_form'] === $this->name;
        }

        return $this->submitted;
    }

    /**
     * Returns true if form valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        if (!$this->submitted) {
            throw new \LogicException('Cannot validate unsubmitted form.');
        }

        list($rules, $messages, $data, $extra) = $this->prepareValidation($this->fields, $this->formData);

        $this->result = $this->validator->validate($data, $rules, $messages);
        $this->validatedData = $this->finishValidation($this->fields, $this->result, $extra);

        return $this->result->valid();
    }

    /**
     * Returns form result if any.
     *
     * @return Result|null
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * Returns true if field exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Returns true if button exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasButton(string $name): bool
    {
        return isset($this->buttons[$name]);
    }

    /**
     * Add form field.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function addField(string $name, string $type = 'text', array $options = null): Form
    {
        $this->fields[$name] = $this->createField($name, $type, $options);

        return $this;
    }

    /**
     * Add form button.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function addButton(string $name, string $type = 'submit', array $options = null): Form
    {
        $this->buttons[$name] = $this->createField($name, $type, $options, 'Fal\\Stick\\Web\\Form\\Button');

        return $this;
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
        if (!$attr) {
            $attr = $this->attributes;
        }

        $attr['method'] = $this->method;
        $attr['name'] = $this->name;

        if (!isset($attr['enctype']) && $multipart) {
            $attr['enctype'] = 'multipart/form-data';
        }

        return
            $this->formBuilder->open($attr).PHP_EOL.
            $this->formBuilder->renderField(new Field('_form', 'hidden', array(
                'value' => $this->name,
            )))
        ;
    }

    /**
     * Returns form close tag.
     *
     * @return string
     */
    public function close(): string
    {
        return $this->formBuilder->close();
    }

    /**
     * Render form field.
     *
     * @param string $name
     *
     * @return string
     */
    public function row(string $name): string
    {
        if (!isset($this->fields[$name]) && !isset($this->buttons[$name])) {
            throw new \LogicException(sprintf('Field or button not exists: %s.', $name));
        }

        return $this->renderField($this->fields[$name] ?? $this->buttons[$name]);
    }

    /**
     * Render form fields.
     *
     * @return string
     */
    public function rows(): string
    {
        $rows = '';

        foreach ($this->fields as $field) {
            $rows .= $this->renderField($field).PHP_EOL;
        }

        return rtrim($rows);
    }

    /**
     * Render form buttons.
     *
     * @return string
     */
    public function buttons(): string
    {
        $buttons = array();

        foreach ($this->buttons as $name => $button) {
            if (!$button->rendered) {
                $buttons[$name] = $button;
            }
        }

        return $this->formBuilder->renderButtons($buttons);
    }

    /**
     * Render form.
     *
     * @param array|null $attr
     *
     * @return string
     */
    public function render(array $attr = null): string
    {
        return $this->open($attr).PHP_EOL.
            $this->rows().PHP_EOL.
            $this->buttons().PHP_EOL.
            $this->close()
        ;
    }

    /**
     * Allow children to add fields etc.
     *
     * @param array $options
     */
    protected function build(array $options)
    {
        // override to add fields and button
    }

    /**
     * Render fields.
     *
     * @param Field $field
     *
     * @return string
     */
    protected function renderField(Field $field): string
    {
        if ($field->rendered) {
            return '';
        }

        $field->value = $this->offsetGet($field->name);
        $field->errors = $this->result->errors[$field->name] ?? null;
        $field->submitted = $this->submitted;

        $str = $this->formBuilder->renderField($field);
        $field->rendered = true;

        return $str;
    }

    /**
     * Create form field.
     *
     * @param string      $name
     * @param string      $type
     * @param array|null  $options
     * @param string|null $class
     *
     * @return Field
     */
    protected function createField(string $name, string $type, array $options = null, string $class = null): Field
    {
        if (!isset($options['id'])) {
            $options['id'] = $this->name.'_'.$name;
        }

        if (!isset($options['label'])) {
            $options['label'] = $this->translator->transAdv($name) ?? Util::titleCase($name);
        }

        if (!$class) {
            $class = 'Fal\\Stick\\Web\\Form\\Field';
        }

        return new $class($name, $type, $options);
    }

    /**
     * Prepare form validation.
     *
     * @param array $fields
     * @param array $formData
     *
     * @return array
     */
    protected function prepareValidation(array $fields, array $formData): array
    {
        $rules = array();
        $messages = array();
        $data = array();

        foreach ($fields as $name => $field) {
            $data[$name] = $field->transform($formData[$name] ?? null);

            if ($field->constraints) {
                $rules[$name] = $field->constraints;

                foreach ($field->messages ?? array() as $rule => $message) {
                    $messages[$name.'.'.$rule] = $message;
                }
            }
        }

        return array($rules, $messages, $data, array_diff_key($formData, $rules));
    }

    /**
     * Returns validated data.
     *
     * @param array  $fields
     * @param Result $result
     * @param array  $extra
     *
     * @return array
     */
    protected function finishValidation(array $fields, Result $result, array $extra): array
    {
        $data = array();

        foreach ($fields as $name => $field) {
            if ($result->data && array_key_exists($name, $result->data)) {
                $value = $result->data[$name];
            } else {
                $value = $extra[$name] ?? null;
            }

            $data[$name] = $field->reverseTransform($value);
        }

        return $data;
    }
}
