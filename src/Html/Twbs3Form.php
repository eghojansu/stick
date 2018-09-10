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
     * Left column class name.
     *
     * @var string
     */
    protected $_leftColClass = 'col-sm-2';

    /**
     * Right column offset class name.
     *
     * @var string
     */
    protected $_rightOffsetColClass = 'col-sm-offset-2';

    /**
     * Right column class name.
     *
     * @var string
     */
    protected $_rightColClass = 'col-sm-10';

    /**
     * Returns left column class name.
     *
     * @return string
     */
    public function getLeftColClass()
    {
        return $this->_leftColClass;
    }

    /**
     * Sets left column class name.
     *
     * @param string $_leftColClass
     *
     * @return Twbs3Form
     */
    public function setLeftColClass($_leftColClass)
    {
        $this->_leftColClass = $_leftColClass;

        return $this;
    }

    /**
     * Returns right column offset class name.
     *
     * @return string
     */
    public function getRightOffsetColClass()
    {
        return $this->_rightOffsetColClass;
    }

    /**
     * Sets right column offset class name.
     *
     * @param string $_rightOffsetColClass
     *
     * @return Twbs3Form
     */
    public function setRightOffsetColClass($_rightOffsetColClass)
    {
        $this->_rightOffsetColClass = $_rightOffsetColClass;

        return $this;
    }

    /**
     * Returns right column class name.
     *
     * @return string
     */
    public function getRightColClass()
    {
        return $this->_rightColClass;
    }

    /**
     * Sets right column class name.
     *
     * @param string $_rightColClass
     *
     * @return Twbs3Form
     */
    public function setRightColClass($_rightColClass)
    {
        $this->_rightColClass = $_rightColClass;

        return $this;
    }

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

        return $this->_html->element('div', true, parent::inputRadio($field, $value, $options), $wrapperAttr + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox($field, $value, array $options)
    {
        $wrapperAttr = App::pick($options, 'wrapper_attr', array());
        $default = array('class' => 'checkbox');

        return $this->_html->element('div', true, parent::inputCheckbox($field, $value, $options), $wrapperAttr + $default);
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
        $rightOffset = $this->_rightOffsetColClass.' '.$this->_rightColClass;

        if ('buttons' === $type) {
            return
                '<div class="form-group"><div class="'.$rightOffset.'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'help-block');
        $errors = isset($this->_errors[$field]) ? $this->_html->element('span', true, implode(', ', $this->_errors[$field]), $errorAttr) : '';
        $wrapperClass = 'form-group';

        if ($this->_submitted && isset($this->_fields[$field]['options']['constraints'])) {
            $wrapperClass .= $errors ? ' has-error' : ' has-success';
        }

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="'.$rightOffset.'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'control-label '.$this->_leftColClass);

        return
            '<div class="'.$wrapperClass.'">'.
            $this->_html->label($options['label'], $this->formName($field), $labelAttr).
            '<div class="'.$this->_rightColClass.'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
