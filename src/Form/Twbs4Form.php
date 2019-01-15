<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 19:50
 */

namespace Fal\Stick\Form;

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
    public function addField($name, $type = null, array $options = null, array $attr = null)
    {
        if (!isset($attr['class']) && (!$type || !in_array($type, array('radio', 'checkbox', 'hidden', 'choice')) || ('choice' === $type && !isset($options['expanded'])))) {
            $attr['class'] = 'form-control';
        }

        return parent::addField($name, $type, $options, $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, $multipart = false)
    {
        if (!isset($attr['class'])) {
            $attr['class'] = false;
        }

        return parent::open($attr, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    public function row($name, array $overrideAttr = null)
    {
        if (isset($this->_fields[$name]['attr']['class']) && !preg_match('/is\-(valid|invalid)/i', $this->_fields[$name]['attr']['class'])) {
            $this->_fields[$name]['attr']['class'] .= $this->validationClass($name);
        }

        return parent::row($name, $overrideAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow($input, $type, $name = null, array $options = null)
    {
        if ('hidden' === $type) {
            return $input.PHP_EOL;
        }

        if ('buttons' === $type) {
            return
                '<div class="form-group row"><div class="ml-auto '.$this->_options['right'].'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'invalid-feedback');
        $errors = isset($this->_errors[$name]) ? $this->_html->tag('div', $errorAttr, true, implode(', ', $this->_errors[$name])) : '';
        $wrapperClass = 'form-group row';

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="ml-auto '.$this->_options['right'].'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'col-form-label '.$this->_options['left']);
        $label = $this->_html->tag('label', $labelAttr, true, $options['label']);

        return
            '<div class="'.$wrapperClass.'">'.
            $label.
            '<div class="'.$this->_options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio($field, $value, array $attr, array $options)
    {
        $wrapperAttr = isset($options['wrapper_attr']) ? $options['wrapper_attr'] : array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $attr['id']);
        $attr['class'] = 'form-check-input'.$this->validationClass($field);
        $attr['type'] = 'radio';

        $add = array();
        $value = array_key_exists('value', $attr) ? $attr['value'] : null;

        if (!array_key_exists('checked', $attr)) {
            $submitted = $this->fieldValue($field);

            $add['checked'] = null === $value ? 'on' === $submitted : $submitted == $value;
        }

        $radio = $this->inputInput($field, $value, $attr + $add, $options);
        $label = $this->_html->tag('label', $labelAttr, true, $options['label']);

        return $this->_html->tag('div', $wrapperAttr + $wrapperDefault, true, $radio.$label);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox($field, $value, array $attr, array $options)
    {
        $wrapperAttr = isset($options['wrapper_attr']) ? $options['wrapper_attr'] : array();
        $wrapperDefault = array('class' => 'form-check');
        $labelAttr = array('class' => 'form-check-label', 'for' => $attr['id']);
        $attr['class'] = 'form-check-input'.$this->validationClass($field);
        $attr['type'] = 'checkbox';

        $add = array();
        $value = array_key_exists('value', $attr) ? $attr['value'] : null;

        if (!array_key_exists('checked', $attr)) {
            $submitted = $this->fieldValue($field);

            $add['checked'] = null === $value ? 'on' === $submitted : $submitted == $value;
        }

        $checkbox = $this->inputInput($field, $value, $attr + $add, $options);
        $label = $this->_html->tag('label', $labelAttr, true, $options['label']);

        return $this->_html->tag('div', $wrapperAttr + $wrapperDefault, true, $checkbox.$label);
    }

    /**
     * Returns validation class name.
     *
     * @param string $field
     *
     * @return string
     */
    protected function validationClass($field)
    {
        if ($this->_submitted && isset($this->_fields[$field]['options']['constraints'])) {
            return isset($this->_errors[$field]) ? ' is-invalid' : ' is-valid';
        }

        return '';
    }
}
