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

namespace Fal\Stick;

/**
 * HTML element helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Html
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Defaults attributes.
     *
     * Groups:
     *     * label
     *     * base
     *     * checkbox
     *     * radio
     *     * textarea
     *     * select
     *     * pagination
     *
     * Any group name can be used.
     *
     * @var array
     */
    protected $defaults = [
        'pagination' => [
            'pages' => 0,
            'page' => 0,
            'adjacent' => 2,
            'url' => '',
            'query' => null,
            'qname' => 'page',
            'active_class' => 'active',
            'prev_attr' => [],
            'prev_label' => 'Prev',
            'next_attr' => [],
            'next_label' => 'Next',
            'nav_attr' => ['aria-label' => 'Page navigation'],
            'ul_attr' => ['class' => 'pagination'],
            'li_attr' => [],
            'a_attr' => [],
            'gap_attr' => [],
            'gap_label' => '&hellip;',
        ],
    ];

    /**
     * Always include these attributes.
     *
     * Group name same as $defaults.
     *
     * @var array
     */
    protected $always = [];

    /**
     * Template.
     *
     * @var array
     */
    protected $template = [
        'form_row' => '{label}{input}',
        'checkbox_radio' => '<label>{input} {label}</label>',
    ];

    /**
     * Class constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set defaults group config.
     *
     * @param string $group
     * @param array  $config
     *
     * @return Html
     */
    public function defaults(string $group, array $config): Html
    {
        $this->defaults[$group] = array_replace_recursive($this->defaults[$group] ?? [], $config);

        return $this;
    }

    /**
     * Set always group config.
     *
     * @param string $group
     * @param array  $config
     *
     * @return Html
     */
    public function always(string $group, array $config): Html
    {
        $this->always[$group] = array_replace_recursive($this->always[$group] ?? [], $config);

        return $this;
    }

    /**
     * Set template.
     *
     * @param string $name
     * @param string $template
     *
     * @return Html
     */
    public function template(string $name, string $template): Html
    {
        $this->template[$name] = $template;

        return $this;
    }

    /**
     * Attributes to string.
     *
     * @param array $attr
     *
     * @return string
     */
    public function attr(array $attr): string
    {
        $str = '';

        foreach ($attr as $key => $value) {
            if (null === $value || false === $value || '' === $value) {
                continue;
            }

            $str .= ' '.$key;

            if (true === $value) {
                continue;
            }

            $str .= '="'.addslashes(is_scalar($value) ? trim((string) $value) : substr(json_encode($value), 1, -1)).'"';
        }

        return $str;
    }

    /**
     * String to label.
     *
     * @param string $str
     *
     * @return string
     */
    public function strToLabel(string $str): string
    {
        return $this->translator->transAlt($str, null, ucwords(strtr($str, '_', ' ')));
    }

    /**
     * Generate pagination.
     *
     * @param array $setup see available setup item on defaults pagination setup
     *
     * @return string
     */
    public function pagination(array $setup): string
    {
        if (!isset($setup['pages']) || !isset($setup['page']) ||
            0 == $setup['pages'] ||
            $setup['page'] < 1) {
            return '';
        }

        $use = $setup + $this->defaults['pagination'];
        $query = $use['query'] ?? $_GET;

        $rangeStart = $use['page'] <= $use['adjacent'] ? 1 : $use['page'] - $use['adjacent'];
        $rangeEnd = $use['page'] > $use['pages'] - $use['adjacent'] ? $use['pages'] : $use['page'] + $use['adjacent'];

        $str = '<nav'.$this->attr($use['nav_attr']).'>'.
                '<ul'.$this->attr($use['ul_attr']).'>';

        if ($use['page'] > 1) {
            $str .= '<li'.$this->attr($use['prev_attr']).'>'.
                '<a'.$this->attr([
                    'href' => $use['url'].'?'.http_build_query([$use['qname'] => $use['page'] - 1] + $query),
                ] + $use['a_attr']).'>'.$use['prev_label'].'</a>'.
            '</li>';

            if ($rangeStart > 1) {
                $str .= '<li'.$this->attr($use['prev_attr']).'>'.
                    '<a'.$this->attr([
                        'href' => $use['url'].'?'.http_build_query([$use['qname'] => 1] + $query),
                    ] + $use['a_attr']).'>1</a>'.
                '</li>';

                if ($rangeStart > 2) {
                    $str .= '<li'.$this->attr($use['gap_attr']).'>'.
                        '<span>'.$use['gap_label'].'</span>'.
                    '</li>';
                }
            }
        }

        $active = $use['active_class'];

        for ($page = $rangeStart; $page <= $rangeEnd; ++$page) {
            $attr = $use['li_attr'] + ['class' => ''];

            if ($page == $use['page']) {
                $attr['class'] .= ' '.$active;
            }

            $str .= '<li'.$this->attr($attr).'>'.
                '<a'.$this->attr([
                    'href' => $use['url'].'?'.http_build_query([$use['qname'] => $page] + $query),
                ] + $use['a_attr']).'>'.$page.'</a>'.
            '</li>';
        }

        if ($use['page'] < $use['pages']) {
            if ($rangeEnd < $use['pages']) {
                if ($rangeEnd < $use['pages'] - 1) {
                    $str .= '<li'.$this->attr($use['gap_attr']).'>'.
                        '<span>'.$use['gap_label'].'</span>'.
                    '</li>';
                }

                $str .= '<li'.$this->attr($use['next_attr']).'>'.
                    '<a'.$this->attr([
                        'href' => $use['url'].'?'.http_build_query([$use['qname'] => $use['pages']] + $query),
                    ] + $use['a_attr']).'>'.$use['pages'].'</a>'.
                '</li>';
            }

            $str .= '<li'.$this->attr($use['next_attr']).'>'.
                '<a'.$this->attr([
                    'href' => $use['url'].'?'.http_build_query([$use['qname'] => $use['page'] + 1] + $query),
                ] + $use['a_attr']).'>'.$use['next_label'].'</a>'.
            '</li>';
        }

        $str .= '</ul></nav>';

        return $str;
    }

    /**
     * Generate recursive ul list.
     *
     * @param array        $items
     * @param string       $activeUri
     * @param string       $activeClass
     * @param Closure|null $dm
     * @param Closure|null $modifier
     * @param Closure|null $subModifier
     *
     * @return string
     */
    public function ulist(array $items, string $activeUri = '', string $activeClass = 'active', \Closure $dm = null, \Closure $modifier = null, \Closure $subModifier = null): string
    {
        $str = '';
        $appendClass = ' '.$activeClass;

        foreach ($items as $uri => $item) {
            if ((isset($item['hide']) && $item['hide']) ||
                ($dm && $dm($uri, $item))) {
                continue;
            }

            $label = $item['label'] ?? $uri;

            if ($modifier && $modified = $modifier($label, $item)) {
                list($label, $item) = $modified;
            }

            $li = $this->resolveAttr('ulist_li', null, ['class' => '']);
            $a = $this->resolveAttr('ulist_a', ['href' => '#' === $uri[0] ? '#!' : $uri]);
            $sub = '';

            if (isset($item['items']) && is_array($item['items'])) {
                $sub = $this->ulist($item['items'], $activeUri, $activeClass, $dm, $modifier, $subModifier);

                if (!$sub) {
                    continue;
                }

                if ($subModifier && $modified = $subModifier($label, $item, $sub, $li, $a)) {
                    list($label, $item, $sub, $li, $a) = $modified;
                }

                if (preg_match('/\bclass="[^"]*'.$activeClass.'"/', $sub)) {
                    $li['class'] .= $appendClass;
                }
            }

            if ($activeUri === $uri && false === strpos($li['class'], $appendClass)) {
                $li['class'] .= $appendClass;
            }

            $str .= '<li'.$this->attr($li).'>'.
                '<a'.$this->attr($a).'>'.
                    $label.
                '</a>'.
                $sub.
            '</li>';
        }

        return $str ? '<ul'.$this->attr($this->resolveAttr(...[
            'ulist_ul',
        ])).'>'.$str.'</ul>' : '';
    }

    /**
     * Ace bootstrap row input group.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string|null $type
     * @param string|null $label
     * @param array|null  $options
     *
     * @return string
     */
    public function formRow(string $name, $value = null, string $type = null, string $label = null, array $options = null): string
    {
        $useLabel = $label ?? $this->strToLabel($name);
        $useAttr = ($options['attr'] ?? []) + [
            'id' => $name,
            'placeholder' => $useLabel,
        ];

        switch ($type) {
            case 'choice':
                $input = $this->inputChoice($name, $value, $useAttr, $options['config'] ?? null);
                break;
            case 'checkbox':
            case 'radio':
                $input = $this->renderCheckboxRadio($type, $name, $value, $useAttr, $useLabel);
                $useLabel = '';
                break;
            case 'textarea':
                $input = $this->inputTextarea($name, $value, $useAttr);
                break;
            default:
                $input = $this->inputBase($name, $value, $useAttr, $type, $options['config'] ?? null);
                break;
        }

        $labelStr = $this->inputLabel($useLabel, $options['label_attr'] ?? [], $name);

        return str_replace(['{input}', '{label}'], [$input, $labelStr], $this->template['form_row']);
    }

    /**
     * Render html element.
     *
     * @param string      $element
     * @param string|null $content
     * @param array|null  $attr
     * @param bool        $close
     *
     * @return string
     */
    public function element(string $element, string $content = null, array $attr = null, bool $close = true): string
    {
        return '<'.$element.$this->attr($this->resolveAttr(...[
            $element,
            null,
            null,
            $attr,
        ])).'>'.$content.($close ? '</'.$element.'>' : '');
    }

    /**
     * Render label element.
     *
     * @param string      $label
     * @param array|null  $attr
     * @param string|null $for
     *
     * @return string
     */
    public function inputLabel(string $label, array $attr = null, string $for = null): string
    {
        return $this->element('label', $label, ['for' => $for] + ((array) $attr));
    }

    /**
     * Render input element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param array|null  $attr
     * @param string|null $type
     * @param array|null  $config
     *
     * @return string
     */
    public function inputBase(string $name, $value = null, array $attr = null, string $type = null, array $config = null): string
    {
        $use = ((array) $config) + [
            'no_value' => false,
        ];
        $useValue = (null === $value || 'password' === $type || $use['no_value']) ? null : htmlspecialchars($value);

        return '<input'.$this->attr($this->resolveAttr(...[
            $type ?: 'base',
            ['name' => $name, 'type' => $type ?: 'text'],
            ['value' => $useValue],
            $attr,
        ])).'>';
    }

    /**
     * Render checkbox element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param array|null  $attr
     * @param string|null $label
     *
     * @return string
     */
    public function inputCheckbox(string $name, $value = null, array $attr = null, string $label = null): string
    {
        return $this->renderCheckboxRadio('checkbox', $name, $value, $attr, $label);
    }

    /**
     * Render radio element.
     *
     * @param string      $name
     * @param mixed       $value
     * @param array|null  $attr
     * @param string|null $label
     *
     * @return string
     */
    public function inputRadio(string $name, $value = null, array $attr = null, string $label = null): string
    {
        return $this->renderCheckboxRadio('radio', $name, $value, $attr, $label);
    }

    /**
     * Render textarea element.
     *
     * @param string     $name
     * @param mixed      $value
     * @param array|null $attr
     *
     * @return string
     */
    public function inputTextarea(string $name, $value = null, array $attr = null): string
    {
        $useValue = (null === $value) ? null : htmlspecialchars($value);

        return '<textarea'.$this->attr($this->resolveAttr(...[
            'textarea',
            ['name' => $name],
            null,
            $attr,
        ])).'>'.$useValue.'</textarea>';
    }

    /**
     * Render choice input.
     *
     * @param string     $name
     * @param mixed      $selected
     * @param array|null $attr
     * @param array|null $config
     *
     * @return string
     */
    public function inputChoice(string $name, $selected = null, array $attr = null, array $config = null): string
    {
        $use = ((array) $config) + [
            'source' => null,
            'placeholder' => $this->translator->transAlt('choice.'.$name, null, 'Choose --', 'choose'),
            'multiple' => false,
            'expanded' => false,
        ];

        if ($use['expanded']) {
            if ($use['multiple']) {
                return $this->renderChoice('checkbox', $name.'[]', $selected, $attr, $use['source']);
            }

            return $this->renderChoice('radio', $name, $selected, $attr, $use['source']);
        }

        return $this->renderSelect($name, $selected, $attr, $use['multiple'], $use['placeholder'], $use['source']);
    }

    /**
     * Render checkbox or radio group.
     *
     * @param string $type
     * @param string $name
     * @param mixed  $checked
     * @param array  $attr
     * @param mixed  $source
     *
     * @return string
     */
    protected function renderChoice(string $type, string $name, $checked, array $attr = null, $source = null): string
    {
        if (!$source) {
            return '';
        }

        $str = '';
        $checkedValue = (array) $checked;

        foreach (is_callable($source) ? $source() : $source as $label => $value) {
            $input = '<input'.$this->attr($this->resolveAttr(...[
                $type,
                ['name' => $name, 'type' => $type, 'value' => $value, 'checked' => in_array($value, $checkedValue)],
                null,
                $attr,
            ])).'>';
            $str .= str_replace(['{input}', '{label}'], [$input, $label], $this->template['checkbox_radio']);
        }

        return $str;
    }

    /**
     * Render select element.
     *
     * @param string $name
     * @param mixed  $selected
     * @param array  $attr
     * @param bool   $multiple
     * @param string $placeholder
     * @param mixed  $source
     *
     * @return string
     */
    protected function renderSelect(string $name, $selected, array $attr = null, $multiple = false, string $placeholder = null, $source = null): string
    {
        $useName = $name.($multiple ? '[]' : '');
        $selectedValue = (array) $selected;
        $str = '<option value="">'.$placeholder.'</option>';

        if ($source) {
            foreach (is_callable($source) ? $source() : $source as $label => $value) {
                $str .= '<option'.$this->attr($this->resolveAttr(...[
                    'option',
                    ['value' => $value, 'selected' => in_array($value, $selectedValue)],
                ])).'>'.$label.'</option>';
            }
        }

        return '<select'.$this->attr($this->resolveAttr(...[
            'select',
            ['name' => $useName, 'multiple' => $multiple],
            null,
            $attr,
        ])).'>'.$str.'</select>';
    }

    /**
     * Render radio or checkbox element.
     *
     * @param string      $type
     * @param string      $name
     * @param mixed       $value
     * @param array|null  $attr
     * @param string|null $label
     *
     * @return string
     */
    protected function renderCheckboxRadio(string $type, string $name, $value = null, array $attr = null, string $label = null): string
    {
        $checked = ($attr['value'] ?? '1') == $value;
        $input = '<input'.$this->attr($this->resolveAttr(...[
            $type,
            ['name' => $name, 'type' => $type, 'checked' => $checked],
            ['value' => '1'],
            $attr,
        ])).'>';
        $labelStr = $label ?? $this->strToLabel($name);

        return str_replace(['{input}', '{label}'], [$input, $labelStr], $this->template['checkbox_radio']);
    }

    /**
     * Resolve attr value.
     *
     * @param string     $group
     * @param array|null $always
     * @param array|null $defaults
     * @param array|null $attr
     *
     * @return array
     */
    protected function resolveAttr(string $group, array $always = null, array $defaults = null, array $attr = null): array
    {
        $result = ((array) $always) + ((array) $attr) + ((array) $defaults) + ($this->defaults[$group] ?? []);

        foreach ($this->always[$group] ?? [] as $key => $value) {
            if (isset($result[$key])) {
                if (is_array($result[$key])) {
                    $result[$key] = $value;
                } else {
                    $result[$key] .= ' '.$value;
                }
            }
        }

        return $result;
    }

    /**
     * Proxy to element.
     *
     * @param string $method
     * @param array  $args
     *
     * @return string
     */
    public function __call($method, array $args)
    {
        return $this->element($method, ...$args);
    }
}
