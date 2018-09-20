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
 * Bootstrap 4 Horizontal Form.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs4Form extends Twbs3Form
{
    /**
     * Returns validation class name.
     *
     * @param string $field
     *
     * @return string
     */
    private function validationClass(string $field): string
    {
        if ($this->_submitted && isset($this->_fields[$field]['options']['constraints'])) {
            return isset($this->_errors[$field]) ? ' is-invalid' : ' is-valid';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, bool $multipart = false): string
    {
        $default = array('class' => null);

        return parent::open(((array) $attr) + $default, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputInput(string $field, $value, array $options, string $type): string
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputInput($field, $value, $options, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputText(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputText($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputPassword(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputPassword($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputTextarea(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputTextarea($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(string $field, $value, array $options): string
    {
        $id = $options['radio_id'] ?? $this->_name.'_'.$field;
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label');
        $options['attr'] += array('class' => 'form-check-input'.$this->validationClass($field));

        $default = array('id' => $id);
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->_submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $radio = $this->_html->input('radio', $this->formName($field), $raw, $options['attr'] + $default);
        $label = $this->_html->label($options['label'], $id, $labelAttr);

        return $this->_html->element('div', true, $radio.$label, $wrapperAttr + $wrapperDefault);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadioGroup(string $name, $value, array $attr, array $items = null, array $wrapperAttr = null): string
    {
        $content = '';
        $ctr = 1;

        foreach ((array) $items as $label => $val) {
            $attr['checked'] = $val === $value;

            $content .= $this->inputRadio($name, $val, array(
                'attr' => $attr,
                'label' => $label,
                'radio_id' => $this->_name.'_'.$name.$ctr,
            ));
            ++$ctr;
        }

        return $this->_html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(string $field, $value, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label');
        $options['attr'] += array('class' => 'form-check-input'.$this->validationClass($field));

        $name = $this->formName($field);

        if (false !== ($pos = strpos($field, '['))) {
            $realname = substr($field, 0, $pos);
            $suffix = substr($field, $pos);
            $name = $this->formName($realname).$suffix;
        }

        $id = $options['checkbox_id'] ?? $this->_html->fixId($name);
        $default = array('id' => $id);
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->_submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $checkbox = $this->_html->input('checkbox', $name, $raw, $options['attr'] + $default);
        $label = $this->_html->label($options['label'], $id, $labelAttr);

        return $this->_html->element('div', true, $checkbox.$label, $wrapperAttr + $wrapperDefault);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckboxGroup(string $name, $value, array $attr, array $items = null, array $wrapperAttr = null): string
    {
        $nameArr = $name.'[]';
        $content = '';
        $ctr = 1;
        $check = (array) $value;

        foreach ((array) $items as $label => $val) {
            $attr['checked'] = in_array($val, $check);

            $content .= $this->inputCheckbox($nameArr, $val, array(
                'attr' => $attr,
                'label' => $label,
                'checkbox_id' => $this->_name.'_'.$name.$ctr,
            ));
            ++$ctr;
        }

        return $this->_html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow(string $type, string $input, string $field = null, array $options = null): string
    {
        if ('buttons' === $type) {
            return
                '<div class="form-group row"><div class="ml-auto '.$this->_rightColClass.'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'invalid-feedback');
        $errors = isset($this->_errors[$field]) ? $this->_html->element('div', true, implode(', ', $this->_errors[$field]), $errorAttr) : '';
        $wrapperClass = 'form-group row';

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="ml-auto '.$this->_rightColClass.'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'col-form-label '.$this->_leftColClass);

        return
            '<div class="'.$wrapperClass.'">'.
            $this->_html->label($options['label'], $this->formName($field), $labelAttr).
            '<div class="'.$this->_rightColClass.'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
