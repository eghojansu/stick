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

use Fal\Stick\Fw;
use Fal\Stick\Util\Option;
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
     * @var Fw
     */
    protected $fw;

    /**
     * @var FormBuilderInterface
     */
    public $formBuilder;

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
    protected $submitted;

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
     * @var array
     */
    protected $ignores = array();

    /**
     * @var array
     */
    protected $options;

    /**
     * Class constructor.
     *
     * @param Fw                   $fw
     * @param Validator            $validator
     * @param FormBuilderInterface $formBuilder
     * @param array|null           $data
     * @param array|null           $options
     * @param string|null          $name
     */
    public function __construct(Fw $fw, Validator $validator, FormBuilderInterface $formBuilder, array $data = null, array $options = null, string $name = null)
    {
        $this->fw = $fw;
        $this->validator = $validator;
        $this->formBuilder = $formBuilder;
        $this->options = $options ?? array();

        if (!$this->name) {
            $this->name = $name ?? $fw->snakeCase($fw->classname($this));
        }

        $this->setData($data ?? array());
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
        $this->method = strtoupper($method);

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
     * @return array|null
     */
    public function getFormData(): ?array
    {
        if (!$this->formData) {
            $this->formData = $this->fw->get($this->method);
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
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
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
        $this->options = $options;

        return $this;
    }

    /**
     * Returns ignores.
     *
     * @return array
     */
    public function getIgnores(): array
    {
        return array_keys($this->ignores);
    }

    /**
     * Sets ignores.
     *
     * @param string|array $ignores
     *
     * @return Form
     */
    public function setIgnores($ignores): Form
    {
        $this->ignores = array_fill_keys($this->fw->split($ignores), true);

        return $this;
    }

    /**
     * Returns true if form is submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        if (null === $this->submitted) {
            $option = new Option();
            $this->configureOption($option);
            $this->build($option->resolve($this->options));
            $this->prepareData();

            $this->submitted = $this->method === $this->fw->get('VERB');

            if ($this->submitted) {
                $data = $this->getFormData();

                $this->submitted = isset($data['_form']) && $data['_form'] === $this->name;
            }
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

        return $this->result->isSuccess();
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
    public function has(string $name): bool
    {
        return isset($this->fields[$name]) || isset($this->buttons[$name]);
    }

    /**
     * Returns field.
     *
     * @param string $name
     *
     * @return Field|null
     */
    public function get(string $name): ?Field
    {
        return $this->fields[$name] ?? $this->buttons[$name] ?? null;
    }

    /**
     * Set form field.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function set(string $name, string $type = 'text', array $options = null): Form
    {
        $field = $this->createField($name, $type, $options);

        if ($field->isButton()) {
            $this->buttons[$name] = $field;
        } else {
            $this->fields[$name] = $field;
        }

        return $this;
    }

    /**
     * An alias for set.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function add(string $name, string $type = 'text', array $options = null): Form
    {
        return $this->set($name, $type, $options);
    }

    /**
     * Remove field.
     *
     * @param string $name
     *
     * @return Form
     */
    public function rem(string $name): Form
    {
        unset($this->fields[$name], $this->buttons[$name]);

        return $this;
    }

    /**
     * Returns fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns buttons.
     *
     * @return array
     */
    public function getButtons(): array
    {
        return $this->buttons;
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
        if (isset($this->fields[$name])) {
            $field = $this->fields[$name];
        } elseif (isset($this->buttons[$name])) {
            $field = $this->buttons[$name];
        } else {
            throw new \LogicException(sprintf('Field or button not exists: %s.', $name));
        }

        return $this->renderField($field);
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
     * @param Option $option
     */
    protected function build(Option $option)
    {
        // override to add fields and button
    }

    /**
     * Allow children to configure option.
     *
     * @param Option $option
     */
    protected function configureOption(Option $option)
    {
        // override to configure defaults option
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
        $field->errors = $this->result ? $this->result->getError($field->name) : array();
        $field->submitted = $this->submitted ?? false;

        $str = $this->formBuilder->renderField($field);
        $field->rendered = true;

        return $str;
    }

    /**
     * Create form field.
     *
     * @param string     $name
     * @param string     $type
     * @param array|null $options
     *
     * @return Field
     */
    protected function createField(string $name, string $type, array $options = null): Field
    {
        if (!isset($options['id'])) {
            $options['id'] = $this->name.'_'.$name;
        }

        if (!isset($options['label'])) {
            $options['label'] = $this->fw->trans($name, null, true);
        }

        return new Field($name, $type, $options);
    }

    /**
     * Prepare form data after build.
     */
    protected function prepareData(): void
    {
        foreach (array_intersect_key($this->initialData, $this->fields) as $key => $value) {
            $this->initialData[$key] = $this->fields[$key]->transform($value);
        }
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

                foreach ($field->messages as $rule => $message) {
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
        $validated = $result->context->getValidated();

        foreach ($fields as $name => $field) {
            if (isset($this->ignores[$name])) {
                continue;
            }

            $data[$name] = $field->reverseTransform($validated[$name] ?? $extra[$name] ?? null);
        }

        return $data;
    }
}
