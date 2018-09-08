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
 * Bootstrap 3 Horizontal Form.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs3Form extends Form
{
    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, $multipart = false)
    {
        $default = array('class' => 'form-horizontal');

        return parent::open(((array) $attr) + $default, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputButton($label, $type, $name, array $attr = null)
    {
        $default = array('class' => 'btn btn-default');

        return parent::inputButton($label, $type, $name, ((array) $attr) + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputInput($field, $value, array $options, $type)
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputInput($field, $value, $options, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputText($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputText($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputPassword($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputPassword($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio($field, $value, array $options)
    {
        $wrapperAttr = App::pick($options, 'wrapper_attr', array());
        $default = array('class' => 'radio');

        return $this->html->element('div', true, parent::inputRadio($field, $value, $options), $wrapperAttr + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox($field, $value, array $options)
    {
        $wrapperAttr = App::pick($options, 'wrapper_attr', array());
        $default = array('class' => 'checkbox');

        return $this->html->element('div', true, parent::inputCheckbox($field, $value, $options), $wrapperAttr + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputTextarea($field, $value, array $options)
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputTextarea($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputSelect($name, $value, $label, array $attr, array $items = null, array $oAttr = null)
    {
        $default = array('class' => 'form-control');

        return parent::inputSelect($name, $value, $label, $attr + $default, $items, $oAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow($type, $input, $field = null, array $options = null)
    {
        if ('buttons' === $type) {
            return
                '<div class="form-group"><div class="col-sm-offset-2 col-sm-10">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'help-block');
        $errors = isset($this->errors[$field]) ? $this->html->element('span', true, implode(', ', $this->errors[$field]), $errorAttr) : '';
        $wrapperClass = 'form-group';

        if ($this->submitted && isset($this->fields[$field]['options']['constraints'])) {
            $wrapperClass .= $errors ? ' has-error' : ' has-success';
        }

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="col-sm-offset-2 col-sm-10">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'control-label col-sm-2');

        return
            '<div class="'.$wrapperClass.'">'.
            $this->html->label($options['label'], $this->formName($field), $labelAttr).
            '<div class="col-sm-10">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
