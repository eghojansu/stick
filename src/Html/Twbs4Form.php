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
    private function validationClass($field)
    {
        if ($this->submitted && isset($this->fields[$field]['options']['constraints'])) {
            return isset($this->errors[$field]) ? ' is-invalid' : ' is-valid';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, $multipart = false)
    {
        $default = array('class' => null);

        return parent::open(((array) $attr) + $default, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputInput($field, $value, array $options, $type)
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputInput($field, $value, $options, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputText($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputText($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputPassword($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputPassword($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputTextarea($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control'.$this->validationClass($field));

        return parent::inputTextarea($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio($field, $value, array $options)
    {
        $id = App::pick($options, 'radio_id', $this->name.'_'.$field);
        $wrapperAttr = App::pick($options, 'wrapper_attr', array());
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label');
        $options['attr'] += array('class' => 'form-check-input'.$this->validationClass($field));

        $default = array('id' => $id);
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $radio = $this->html->input('radio', $this->formName($field), $raw, $options['attr'] + $default);
        $label = $this->html->label($options['label'], $id, $labelAttr);

        return $this->html->element('div', true, $radio.$label, $wrapperAttr + $wrapperDefault);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadioGroup($name, $value, array $attr, array $items = null, array $wrapperAttr = null)
    {
        $content = '';
        $ctr = 1;

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = $val === $value;

            $content .= $this->inputRadio($name, $val, array(
                'attr' => $attr,
                'label' => $label,
                'radio_id' => $this->name.'_'.$name.$ctr,
            ));
            ++$ctr;
        }

        return $this->html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox($field, $value, array $options)
    {
        $wrapperAttr = App::pick($options, 'wrapper_attr', array());
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label');
        $options['attr'] += array('class' => 'form-check-input'.$this->validationClass($field));

        $name = $this->formName($field);

        if (false !== ($pos = strpos($field, '['))) {
            $realname = substr($field, 0, $pos);
            $suffix = substr($field, $pos);
            $name = $this->formName($realname).$suffix;
        }

        $id = App::pick($options, 'checkbox_id', $this->html->fixId($name));
        $default = array('id' => $id);
        $raw = $value;

        if (!array_key_exists('checked', $options['attr'])) {
            $raw = $this->rawValue($field);
            $default['checked'] = false;

            if ($this->submitted) {
                $default['checked'] = $raw ? $raw === $value : 'on' === $value;
            }
        }

        $checkbox = $this->html->input('checkbox', $name, $raw, $options['attr'] + $default);
        $label = $this->html->label($options['label'], $id, $labelAttr);

        return $this->html->element('div', true, $checkbox.$label, $wrapperAttr + $wrapperDefault);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckboxGroup($name, $value, array $attr, array $items = null, array $wrapperAttr = null)
    {
        $nameArr = $name.'[]';
        $content = '';
        $ctr = 1;
        $check = (array) $value;

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = in_array($val, $check);

            $content .= $this->inputCheckbox($nameArr, $val, array(
                'attr' => $attr,
                'label' => $label,
                'checkbox_id' => $this->name.'_'.$name.$ctr,
            ));
            ++$ctr;
        }

        return $this->html->element('div', true, $content, $wrapperAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow($type, $input, $field = null, array $options = null)
    {
        if ('buttons' === $type) {
            return
                '<div class="form-group row"><div class="ml-auto '.$this->rightColClass.'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'invalid-feedback');
        $errors = isset($this->errors[$field]) ? $this->html->element('div', true, implode(', ', $this->errors[$field]), $errorAttr) : '';
        $wrapperClass = 'form-group row';

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="ml-auto '.$this->rightColClass.'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'col-form-label '.$this->leftColClass);

        return
            '<div class="'.$wrapperClass.'">'.
            $this->html->label($options['label'], $this->formName($field), $labelAttr).
            '<div class="'.$this->rightColClass.'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
