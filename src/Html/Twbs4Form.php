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

namespace Fal\Stick\Html;

/**
 * Twitter bootstrap 4 form.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs4Form extends Twbs3Form
{
    /**
     * {@inheritdoc}
     */
    public function add(string $name, string $type = null, array $options = null, array $attr = null): Form
    {
        if (!isset($attr['class']) && (!$type || !in_array($type, array('radio', 'checkbox', 'hidden', 'choice')) || ('choice' === $type && !isset($options['expanded'])))) {
            $attr['class'] = 'form-control';
        }

        return parent::add($name, $type, $options, $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, bool $multipart = false): string
    {
        if (!isset($attr['class'])) {
            $attr['class'] = false;
        }

        return parent::open($attr, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    public function row(string $name, array $overrideAttr = null): string
    {
        if (isset($this->fields[$name]['attr']['class']) && !preg_match('/is\-(valid|invalid)/i', $this->fields[$name]['attr']['class'])) {
            $this->fields[$name]['attr']['class'] .= $this->validationClass($name);
        }

        return parent::row($name, $overrideAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow(string $input, string $type, string $name = null, array $options = null): string
    {
        if ('hidden' === $type) {
            return $input.PHP_EOL;
        }

        if ('buttons' === $type) {
            return
                '<div class="form-group row"><div class="ml-auto '.$this->options['right'].'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'invalid-feedback');
        $errors = isset($this->errors[$name]) ? $this->html->tag('div', $errorAttr, true, implode(', ', $this->errors[$name])) : '';
        $wrapperClass = 'form-group row';

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="ml-auto '.$this->options['right'].'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'col-form-label '.$this->options['left']);
        $label = $this->html->tag('label', $labelAttr, true, $options['label']);

        return
            '<div class="'.$wrapperClass.'">'.
            $label.
            '<div class="'.$this->options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(string $field, $value, array $attr, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $attr['id']);
        $attr['class'] = 'form-check-input'.$this->validationClass($field);
        $attr['type'] = 'radio';

        $add = array();
        $raw = $value;

        if (!array_key_exists('checked', $attr)) {
            $raw = $this->rawValue($field);
            $add['checked'] = false;

            if ($this->submitted) {
                $add['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $radio = $this->inputInput($field, $raw, $attr + $add, $options);
        $label = $this->html->tag('label', $labelAttr, true, $options['label']);

        return $this->html->tag('div', $wrapperAttr + $wrapperDefault, true, $radio.$label);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(string $field, $value, array $attr, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $attr['id']);
        $attr['class'] = 'form-check-input'.$this->validationClass($field);
        $attr['type'] = 'checkbox';

        $add = array();
        $raw = $value;

        if (!array_key_exists('checked', $attr)) {
            $raw = $this->rawValue($field);
            $add['checked'] = false;

            if ($this->submitted) {
                $add['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $checkbox = $this->inputInput($field, $raw, $attr + $add, $options);
        $label = $this->html->tag('label', $labelAttr, true, $options['label']);

        return $this->html->tag('div', $wrapperAttr + $wrapperDefault, true, $checkbox.$label);
    }

    /**
     * Returns validation class name.
     *
     * @param string $field
     *
     * @return string
     */
    protected function validationClass(string $field): string
    {
        if ($this->submitted && isset($this->fields[$field]['options']['constraints'])) {
            return isset($this->errors[$field]) ? ' is-invalid' : ' is-valid';
        }

        return '';
    }
}
