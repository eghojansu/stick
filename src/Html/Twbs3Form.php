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
    protected $options = array(
        'left' => 'col-sm-2',
        'right' => 'col-sm-10',
        'offset' => 'col-sm-offset-2',
        'mark' => self::MARK_ERROR,
    );

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
    public function addButton(string $name, string $type = null, string $label = null, array $attr = null): Form
    {
        if (!isset($attr['class'])) {
            $attr['class'] = 'link' === $type ? 'btn btn-default' : 'btn btn-primary';
        }

        return parent::addButton($name, $type, $label, $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function open(array $attr = null, bool $multipart = false): string
    {
        if (!isset($attr['class'])) {
            $attr['class'] = 'form-horizontal';
        }

        return parent::open($attr, $multipart);
    }

    /**
     * {@inheritdoc}
     */
    protected function inputRadio(string $field, $value, array $attr, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $default = array('class' => 'radio');

        return $this->html->tag('div', $wrapperAttr + $default, true, parent::inputRadio($field, $value, $attr, $options));
    }

    /**
     * {@inheritdoc}
     */
    protected function inputCheckbox(string $field, $value, array $attr, array $options): string
    {
        $wrapperAttr = $options['wrapper_attr'] ?? array();
        $default = array('class' => 'checkbox');

        return $this->html->tag('div', $wrapperAttr + $default, true, parent::inputCheckbox($field, $value, $attr, $options));
    }

    /**
     * {@inheritdoc}
     */
    protected function renderRow(string $input, string $type, string $name = null, array $options = null): string
    {
        if ('hidden' === $type) {
            return $input.PHP_EOL;
        }

        $rightOffset = $this->options['offset'].' '.$this->options['right'];

        if ('buttons' === $type) {
            return
                '<div class="form-group"><div class="'.$rightOffset.'">'.
                $input.
                '</div></div>'.
                PHP_EOL;
        }

        $errorAttr = array('class' => 'help-block');
        $errors = isset($this->errors[$name]) ? $this->html->tag('span', $errorAttr, true, implode(', ', $this->errors[$name])) : '';
        $wrapperClass = 'form-group';

        if ($this->submitted && isset($this->fields[$name]['options']['constraints'])) {
            if (($this->options['mark'] & self::MARK_SUCCESS) && !$errors) {
                $wrapperClass .= ' has-success';
            } elseif (($this->options['mark'] & self::MARK_ERROR) && $errors) {
                $wrapperClass .= ' has-error';
            }
        }

        if (in_array($type, array('checkbox', 'radio'))) {
            return
                '<div class="'.$wrapperClass.'"><div class="'.$rightOffset.'">'.
                $input.$errors.
                '</div></div>'.PHP_EOL;
        }

        $labelAttr = $options['label_attr'] + array('class' => 'control-label '.$this->options['left']);
        $label = $this->html->tag('label', $labelAttr, true, $options['label']);

        return
            '<div class="'.$wrapperClass.'">'.
            $label.
            '<div class="'.$this->options['right'].'">'.
            $input.$errors.
            '</div>'.
            '</div>'.PHP_EOL;
    }
}
