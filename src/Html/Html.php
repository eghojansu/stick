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
     * @var App
     */
    private $_app;

    /**
     * Class constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->_app = $app;
    }

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

        foreach ($attr as $prop => $value) {
            if (null === $value || false === $value) {
                continue;
            }

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

    /**
     * Returns pagination list.
     *
     * @param int        $page
     * @param int        $max
     * @param array|null $config
     *
     * @return string
     */
    public function pagination(int $page, int $max, array $config = null): string
    {
        if ($max < 1 || $page < 1) {
            return '';
        }

        $dConfig = array(
            'route' => $this->_app->get('ALIAS'),
            'route_data' => $this->_app->get('PARAMS'),
            'route_query' => (array) $this->_app->get('GET'),
            'page_query' => 'page',
            'adjacent' => 2,
            'parent' => array('class' => 'pagination'),
            'wrapper' => array('aria-label' => 'Page navigation'),
            'prev_label' => 'Prev',
            'next_label' => 'Next',
            'active_class' => 'active',
        );
        $mConfig = ((array) $config) + $dConfig;
        $urlPage = function ($page) use ($mConfig) {
            $query = array($mConfig['page_query'] => $page) + $mConfig['route_query'];

            return $this->_app->path($mConfig['route'], $mConfig['route_data'], $query);
        };
        $child = function ($label, array $attr = null, array $wrapperAttr = null, string $el = 'a') {
            $content = $this->element($el, true, (string) $label, $attr);

            return $this->element('li', true, $content, $wrapperAttr);
        };
        $adjacent = $mConfig['adjacent'];
        $rangeStart = $page <= $adjacent ? 1 : $page - $adjacent;
        $rangeEnd = $page > $max - $adjacent ? $max : $page + $adjacent;
        $aClass = array(null, $mConfig['active_class']);
        $lists = '';

        if ($rangeStart > 1) {
            $lists .= $child($mConfig['prev_label'], array(
                'href' => $urlPage($page - 1),
            ));
            $lists .= $child(1, array(
                'href' => $urlPage(1),
            ));
        }

        if ($rangeStart > 2) {
            $lists .= $child('&hellip;', null, array(
                'class' => 'gap',
            ), 'span');
        }

        for ($i = $rangeStart; $i <= $rangeEnd; ++$i) {
            $active = (int) ($i === $page);
            $lists .= $child($i, array(
                'href' => $urlPage($i),
            ), array(
                'class' => $aClass[$active],
            ));
        }

        if ($rangeEnd < $max - 1) {
            $lists .= $child('&hellip;', null, array(
                'class' => 'gap',
            ), 'span');
        }

        if ($rangeEnd < $max) {
            $lists .= $child($max, array(
                'href' => $urlPage($max),
            ));
            $lists .= $child($mConfig['next_label'], array(
                'href' => $urlPage($page + 1),
            ));
        }

        $pagination = $this->element('ul', true, $lists, $mConfig['parent']);

        return $this->element('nav', true, $pagination, $mConfig['wrapper']);
    }

    /**
     * Returns links list.
     *
     * @param array       $items
     * @param string|null $activeRoute
     * @param array|null  $config
     *
     * @return string
     */
    public function ulinks(array $items, string $activeRoute = null, array $config = null): string
    {
        if (empty($items)) {
            return '';
        }

        $dConfig = array(
            'root_attr' => null,
            'parent_attr' => null,
            'parent_item_attr' => null,
            'parent_wrapper_attr' => null,
            'active_class' => 'active',
        );
        $dItem = array(
            'route' => null,
            'args' => null,
            'query' => null,
            'attr' => null,
            'item_attr' => null,
            'wrapper_attr' => null,
            'items' => null,
        );
        $mConfig = ((array) $config) + $dConfig;
        $aClass = array(null, $mConfig['active_class']);
        $lists = '';

        foreach ($items as $label => $item) {
            $child = '';
            $mItem = (is_array($item) ? $item : array('route' => $item)) + $dItem;
            $aAttr = (array) $mItem['attr'];
            $iAttr = (array) $mItem['item_attr'];
            $active = (int) ($mItem['route'] && ($mItem['route'] === $activeRoute));

            if ($mItem['items']) {
                $cAttr = array('root_attr' => (array) $mItem['wrapper_attr']) + $mConfig;

                $cAttr['root_attr'] += (array) $mConfig['parent_wrapper_attr'];
                $aAttr += (array) $mConfig['parent_item_attr'];
                $iAttr += (array) $mConfig['parent_attr'];

                $child = $this->ulinks($mItem['items'], $activeRoute, $cAttr);
                $active = (int) ($active || preg_match('/class="\\b'.preg_quote($aClass[1]).'\\b"/', $child));
            }

            if (empty($aAttr['href'])) {
                $aAttr['href'] = $mItem['route'] ? $this->_app->path($mItem['route'], $mItem['args'], $mItem['query']) : '#';
            }

            $iAttr['class'] = trim(($iAttr['class'] ?? null).' '.$aClass[$active]) ?: null;

            $content = $this->element('a', true, $label, $aAttr);
            $lists .= $this->element('li', true, $content.$child, $iAttr);
        }

        return $this->element('ul', true, $lists, $mConfig['root_attr']);
    }
}
