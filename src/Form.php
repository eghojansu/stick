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

namespace Fal\Stick;

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
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $verb = 'POST';

    /**
     * @var array
     */
    protected $attr = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $buttons = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $submitted = [];

    /**
     * @var bool
     */
    protected $raw = true;

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
            $this->setName(Helper::snakecase(Helper::classname(static::class)));
        }

        $this->build();
    }

    /**
     * Add fields.
     *
     * @param string     $field
     * @param string     $type
     * @param array|null $options
     *
     * @return Form
     */
    public function add(string $field, string $type = null, array $options = null): Form
    {
        $this->fields[$field] = [$type ?? 'text', $options];

        return $this;
    }

    /**
     * Add button.
     *
     * @param string     $label
     * @param array|null $attr
     * @param string     $element
     *
     * @return Form
     */
    public function addButton(string $label, array $attr = null, string $element = 'button'): Form
    {
        $this->buttons[] = [$element, $label, $attr];

        return $this;
    }

    /**
     * Check is form submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        if ($this->verb === $this->app['VERB'] &&
            ($this->app[$this->verb]['fname'] ?? null) === $this->name) {
            $this->raw = false;
            $this->submitted = $this->app[$this->verb];

            return true;
        }

        return false;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name.
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
     * Get verb.
     *
     * @return string
     */
    public function getVerb(): string
    {
        return $this->verb;
    }

    /**
     * Set verb.
     *
     * @param string $verb
     *
     * @return Form
     */
    public function setVerb(string $verb): Form
    {
        $this->verb = strtoupper($verb);

        return $this;
    }

    /**
     * Get attr.
     *
     * @return array
     */
    public function getAttr(): array
    {
        return $this->attr;
    }

    /**
     * Set attr.
     *
     * @param array $attr
     *
     * @return Form
     */
    public function setAttr(array $attr): Form
    {
        $this->attr = $attr;

        return $this;
    }

    /**
     * Validate form.
     *
     * @return bool
     */
    public function valid(): bool
    {
        if ($this->raw) {
            throw new \LogicException('You can not validate unsubmitted form.');
        }

        list($rules, $messages) = $this->findRules();
        $valid = $this->validator->validate($this->submitted, $rules, $messages);

        $this->submitted = $valid['data'];
        $this->errors = $valid['errors'];

        return $valid['success'];
    }

    /**
     * Return errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return submitted.
     *
     * @return array
     */
    public function submitted(): array
    {
        return $this->submitted;
    }

    /**
     * Open form.
     *
     * @param array $attr
     *
     * @return string
     */
    public function open(array $attr = null): string
    {
        return
            '<form'.$this->html->attr(['method' => $this->verb] + ((array) $attr) + $this->attr).'>'.
            $this->html->inputBase('fname', $this->name, null, 'hidden');
    }

    /**
     * Close form.
     *
     * @return string
     */
    public function close(): string
    {
        return '</form>';
    }

    /**
     * Return form row.
     *
     * @param string $field
     *
     * @return string
     */
    public function row(string $field): string
    {
        if (!isset($this->fields[$field])) {
            throw new \LogicException('Field '.$field.' does not exists.');
        }

        list($type, $options) = $this->fields[$field];

        return $this->html->formRow($field, $this->value($field), $type, $options['label'] ?? null, $options);
    }

    /**
     * Render form.
     *
     * @return string
     */
    public function render(): string
    {
        return
            $this->open().
            implode(PHP_EOL, array_map([$this, 'row'], array_keys($this->fields))).
            array_reduce($this->buttons, function ($str, $args) {
                return $str.$this->html->element(...$args);
            }, '').
            $this->close()
        ;
    }

    /**
     * Build fields.
     *
     * @return Form
     */
    protected function build(): Form
    {
        return $this;
    }

    /**
     * Collect rules and messages from field options.
     *
     * @return array
     */
    protected function findRules(): array
    {
        $rules = [];
        $messages = [];

        foreach ($this->fields as $field => [$type, $options]) {
            $messages += $options['messages'] ?? [];

            if (isset($options['constraint'])) {
                $rules[$field] = $options['constraint'];
            }
        }

        return [$rules, $messages];
    }

    /**
     * Get submitted value.
     *
     * @param string $field
     *
     * @return mixed
     */
    protected function value(string $field)
    {
        return $this->submitted[$field] ?? $this->data[$field] ?? null;
    }
}
