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
    public function attr(array $attr): string
    {
        $str = '';

        foreach (array_filter($attr, App::class.'::notNullFalse') as $prop => $value) {
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
     * @param string|null $name
     *
     * @return string|null
     */
    public function fixId(string $name = null): ?string
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
    public function element(string $tag, bool $pair = false, string $content = null, array $attr = null): string
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
    public function input(string $type, string $name, $value = null, array $attr = null): string
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
    public function hidden(string $name, $value = null, array $attr = null): string
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
    public function text(string $name, $value = null, string $label = null, array $attr = null): string
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
    public function password(string $name, string $label = null, array $attr = null): string
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
    public function checkbox(string $name, $value = null, string $label = null, array $attr = null): string
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
    public function radio(string $name, $value = null, string $label = null, array $attr = null): string
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
    public function textarea(string $name, $value = null, string $label = null, array $attr = null): string
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
    public function select(string $name, $value = null, string $label = null, array $attr = null, array $items = null, array $oAttr = null): string
    {
        $default = array('id' => $this->fixId($name));
        $check = (array) $value;

        $attr['name'] = $name;

        $content = $label ? '<option value="">'.$label.'</option>' : '';

        foreach ((array) $items as $label => $val) {
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
    public function checkboxGroup(string $name, $value = null, array $attr = null, array $items = null, array $wrapperAttr = null): string
    {
        $nameArr = $name.'[]';
        $content = '';
        $check = (array) $value;

        foreach ((array) $items as $label => $val) {
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
    public function radioGroup(string $name, $value = null, array $attr = null, array $items = null, array $wrapperAttr = null): string
    {
        $content = '';

        foreach ((array) $items as $label => $val) {
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
    public function label(string $content, string $name = null, array $attr = null): string
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
    public function button(string $label, string $type = 'button', string $name = null, array $attr = null): string
    {
        $default = array('id' => $this->fixId($name));

        $attr['type'] = $type;
        $attr['name'] = $name;

        return $this->element('button', true, $label, $attr + $default);
    }
}
