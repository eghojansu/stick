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
 * Html helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Html
{
    /**
     * Returns translated attributes as string.
     *
     * @param array $attr
     *
     * @return string
     */
    public function attr(array $attr)
    {
        $str = '';

        foreach (array_filter($attr, App::class.'::filterNullFalse') as $prop => $value) {
            if (is_numeric($prop)) {
                $str .= is_string($value) ? ' '.trim($value) : '';
            } else {
                $str .= ' '.$prop;

                if (true !== $value && '' !== $value) {
                    $strVal = is_scalar($value) ? trim((string) $value) : substr(json_encode($value), 1, -1);
                    $str .= '="'.addslashes($strVal).'"';
                }
            }
        }

        return $str;
    }

    /**
     * Translate field name to id.
     *
     * @param string $name
     *
     * @return string
     */
    public function fixId($name = null)
    {
        $search = array('"', "'", '[', ']');
        $replace = array('', '', '_', '_');

        return $name ? rtrim(str_replace($search, $replace, $name), '_') : null;
    }

    /**
     * Returns html element.
     *
     * @param string      $tag
     * @param bool        $pair
     * @param string|null $content
     * @param array|null  $attr
     *
     * @return string
     */
    public function element($tag, $pair = false, $content = null, array $attr = null)
    {
        $closeTag = $pair ? '</'.$tag.'>' : '';

        return '<'.$tag.$this->attr((array) $attr).'>'.$content.$closeTag;
    }

    /**
     * Returns input element.
     *
     * @param  string     type
     * @param  string     name
     * @param mixed      $value
     * @param array|null $attr
     *
     * @return string
     */
    public function input($type, $name, $value = null, array $attr = null)
    {
        $default = array('id' => $this->fixId($name));

        $attr['type'] = $type;
        $attr['name'] = $name;
        $attr['value'] = $value;

        return $this->element('input', false, null, $attr + $default);
    }

    /**
     * Returns input[type=hidden] element.
     *
     * @param string     $name
     * @param mixed      $value
     * @param array|null $attr
     *
     * @return string
     */
    public function hidden($name, $value = null, array $attr = null)
    {
        return $this->input('hidden', $name, $value, $attr);
    }

    /**
     * Returns input[type=text] element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return string
     */
    public function text($name, $value = null, $label = null, array $attr = null)
    {
        $default = array('placeholder' => $label);

        return $this->input('text', $name, $value, ((array) $attr) + $default);
    }

    /**
     * Returns input[type=password] element.
     *
     * @param string      $name
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return string
     */
    public function password($name, $label = null, array $attr = null)
    {
        $default = array('placeholder' => $label);

        return $this->input('password', $name, null, ((array) $attr) + $default);
    }

    /**
     * Returns input[type=checkbox] element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return string
     */
    public function checkbox($name, $value = null, $label = null, array $attr = null)
    {
        $default = array('id' => null);

        return $this->label($this->input('checkbox', $name, $value, ((array) $attr) + $default).' '.$label);
    }

    /**
     * Returns input[type=radio] element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return string
     */
    public function radio($name, $value = null, $label = null, array $attr = null)
    {
        $default = array('id' => null);

        return $this->label($this->input('radio', $name, $value, ((array) $attr) + $default).' '.$label);
    }

    /**
     * Returns textarea element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $label
     * @param array|null  $attr
     *
     * @return string
     */
    public function textarea($name, $value = null, $label = null, array $attr = null)
    {
        $default = array('id' => $this->fixId($name), 'placeholder' => $label);

        $attr['name'] = $name;

        return $this->element('textarea', true, $value, $attr + $default);
    }

    /**
     * Returns select element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $label
     * @param array|null  $attr
     * @param array|null  $items
     * @param array|null  $oAttr
     *
     * @return string
     */
    public function select($name, $value = null, $label = null, array $attr = null, array $items = null, array $oAttr = null)
    {
        $default = array('id' => $this->fixId($name));
        $check = (array) $value;

        $attr['name'] = $name;

        $content = $label ? '<option value="">'.$label.'</option>' : '';

        foreach ($items ?: array() as $label => $val) {
            $oAttr['value'] = $val;
            $oAttr['selected'] = in_array($val, $check);

            $content .= $this->element('option', true, $label, $oAttr);
        }

        return $this->element('select', true, $content, $attr + $default);
    }

    /**
     * Returns checkboxes.
     *
     * @param string      $name
     * @param string|null $value
     * @param array|null  $attr
     * @param array|null  $items
     * @param array|null  $wrapperAttr
     *
     * @return string
     */
    public function checkboxGroup($name, $value = null, array $attr = null, array $items = null, array $wrapperAttr = null)
    {
        $nameArr = $name.'[]';
        $content = '';
        $check = (array) $value;

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = in_array($val, $check);

            $content .= $this->checkbox($nameArr, $val, $label, $attr);
        }

        return $this->element('div', true, $content, $wrapperAttr);
    }

    /**
     * Returns radios.
     *
     * @param string      $name
     * @param string|null $value
     * @param array|null  $attr
     * @param array|null  $items
     * @param array|null  $wrapperAttr
     *
     * @return string
     */
    public function radioGroup($name, $value = null, array $attr = null, array $items = null, array $wrapperAttr = null)
    {
        $content = '';

        foreach ($items ?: array() as $label => $val) {
            $attr['checked'] = $val === $value;

            $content .= $this->radio($name, $val, $label, $attr);
        }

        return $this->element('div', true, $content, $wrapperAttr);
    }

    /**
     * Returns label element.
     *
     * @param string $content
     * @param string $name
     * @param array  $attr
     *
     * @return string
     */
    public function label($content, $name = null, array $attr = null)
    {
        $attr['for'] = $this->fixId($name);

        return $this->element('label', true, $content, $attr);
    }

    /**
     * Returns button element.
     *
     * @param string $label
     * @param string $type
     * @param string $name
     * @param array  $attr
     *
     * @return string
     */
    public function button($label, $type = 'button', $name = null, array $attr = null)
    {
        $default = array('id' => $this->fixId($name));

        $attr['type'] = $type;
        $attr['name'] = $name;

        return $this->element('button', true, $label, $attr + $default);
    }
}
