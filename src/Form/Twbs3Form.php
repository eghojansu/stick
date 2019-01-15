<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 19:47
 */

namespace Fal\Stick\Form;

/**
 * Twitter bootstrap 3 form.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs3Form extends Form
{
    const MARK_NONE = 0;
    const MARK_SUCCESS = 1;
    const MARK_ERROR = 2;

    /**
     * @var array
     */
    protected $_options = array(
        'left' => 'col-sm-2',
        'right' => 'col-sm-10',
        'offset' => 'col-sm-offset-2',
        'mark' => self::MARK_ERROR,
    );

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
    public function addButton($name, $type = null, $label = null, array $attr = null)
    {
        if (!isset($attr['class'])) {
            $attr['class'] = 'link' === $type ? 'btn btn-default' : 'btn btn-primary';
        }

        return parent::addButton($name, $type, $label, $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, $multipart = false)
    {
        if (!isset($attr['class'])) {
            $attr['class'] = 'form-horizontal';
        }

        return parent::open($attr, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio($field, $value, array $attr, array $options)
    {
        $wrapperAttr = isset($options['wrapper_attr']) ? $options['wrapper_attr'] : array();
        $default = array('class' => 'radio');

        return $this->_html->tag('div', $wrapperAttr + $default, true, parent::inputRadio($field, $value, $attr, $options));
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox($field, $value, array $attr, array $options)
    {
        $wrapperAttr = isset($options['wrapper_attr']) ? $options['wrapper_attr'] : array();
        $default = array('class' => 'checkbox');

        return $this->_html->tag('div', $wrapperAttr + $default, true, parent::inputCheckbox($field, $value, $attr, $options));
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow($input, $type, $name = null, array $options = null)
    {
        if ('hidden' === $type) {
            return $input.PHP_EOL;
        }

        $rightOffset = $this->_options['offset'].' '.$this->_options['right'];

        if ('buttons' === $type) {
            return
                '<div class="form-group"><div class="'.$rightOffset.'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'help-block');
        $errors = isset($this->_errors[$name]) ? $this->_html->tag('span', $errorAttr, true, implode(', ', $this->_errors[$name])) : '';
        $wrapperClass = 'form-group';

        if ($this->_submitted && isset($this->_fields[$name]['options']['constraints'])) {
            if (($this->_options['mark'] & self::MARK_SUCCESS) && !$errors) {
                $wrapperClass .= ' has-success';
            } elseif (($this->_options['mark'] & self::MARK_ERROR) && $errors) {
                $wrapperClass .= ' has-error';
            }
        }

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="'.$rightOffset.'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'control-label '.$this->_options['left']);
        $label = $this->_html->tag('label', $labelAttr, true, $options['label']);

        return
            '<div class="'.$wrapperClass.'">'.
            $label.
            '<div class="'.$this->_options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
