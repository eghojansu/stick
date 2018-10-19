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

namespace Fal\Stick\Library\Html;

/**
 * Bootstrap 3 Horizontal Form.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Twbs3Form extends Form
{
    const MARK_NONE = 0;
    const MARK_SUCCESS = 1;
    const MARK_ERROR = 2;

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
     * Mark field state.
     *
     * @var int
     */
    protected $_markState = self::MARK_ERROR;

    /**
     * Returns mark state.
     *
     * @return int
     */
    public function getMarkState(): int
    {
        return $this->_markState;
    }

    /**
     * Set mark state.
     *
     * @param int $markState
     *
     * @return Twbs3Form
     */
    public function setMarkState(int $markState): Twbs3Form
    {
        $this->_markState = $markState;

        return $this;
    }

    /**
     * Returns left column class name.
     *
     * @return string
     */
    public function getLeftColClass(): string
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
    public function setLeftColClass(string $_leftColClass): Twbs3Form
    {
        $this->_leftColClass = $_leftColClass;

        return $this;
    }

    /**
     * Returns right column offset class name.
     *
     * @return string
     */
    public function getRightOffsetColClass(): string
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
    public function setRightOffsetColClass(string $_rightOffsetColClass): Twbs3Form
    {
        $this->_rightOffsetColClass = $_rightOffsetColClass;

        return $this;
    }

    /**
     * Returns right column class name.
     *
     * @return string
     */
    public function getRightColClass(): string
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
    public function setRightColClass(string $_rightColClass): Twbs3Form
    {
        $this->_rightColClass = $_rightColClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, bool $multipart = false): string
    {
        $default = array('class' => 'form-horizontal');

        return parent::open(((array) $attr) + $default, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputButton(string $label, string $type, string $name, array $attr = null): string
    {
        $default = array('class' => 'btn btn-default');

        return parent::inputButton($label, $type, $name, ((array) $attr) + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputInput(string $field, $value, array $options, string $type): string
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputInput($field, $value, $options, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputText(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputText($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputPassword(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputPassword($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(string $field, $value, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $default = array('class' => 'radio');

        return $this->_html->element('div', true, parent::inputRadio($field, $value, $options), $wrapperAttr + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(string $field, $value, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $default = array('class' => 'checkbox');

        return $this->_html->element('div', true, parent::inputCheckbox($field, $value, $options), $wrapperAttr + $default);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputTextarea(string $field, $value, array $options): string
    {
        $options['attr'] += array('class' => 'form-control');

        return parent::inputTextarea($field, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputSelect(string $name, $value, string $label, array $attr, array $items = null, array $oAttr = null): string
    {
        $default = array('class' => 'form-control');

        return parent::inputSelect($name, $value, $label, $attr + $default, $items, $oAttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow(string $type, string $input, string $field = null, array $options = null): string
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
            if (($this->_markState & self::MARK_SUCCESS) && !$errors) {
                $wrapperClass .= ' has-success';
            } elseif (($this->_markState & self::MARK_ERROR) && $errors) {
                $wrapperClass .= ' has-error';
            }
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
