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
 * Html helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Html
{
    /**
     * @var Core
     */
    private $fw;

    /**
     * Class constructor.
     *
     * @param Core $fw
     */
    public function __construct(Core $fw)
    {
        $this->fw = $fw;
    }

    /**
     * Returns html tag.
     *
     * @param string      $tag
     * @param array|null  $attr
     * @param bool        $pair
     * @param string|null $content
     *
     * @return string
     */
    public function tag(string $tag, array $attr = null, bool $pair = false, string $content = null): string
    {
        return '<'.$tag.$this->attr($attr).'>'.$content.($pair ? '</'.$tag.'>' : '');
    }

    /**
     * Convert attr to string.
     *
     * @param array|null $attr
     *
     * @return string
     */
    public function attr(array $attr = null): string
    {
        $str = '';

        foreach ((array) $attr as $prop => $val) {
            if (null === $val || false === $val) {
                continue;
            }

            if (is_numeric($prop)) {
                $str .= is_string($val) ? ' '.trim($val) : '';
            } else {
                $str .= ' '.$prop;

                if (true !== $val && '' !== $val) {
                    $strVal = is_scalar($val) ? trim((string) $val) : substr(json_encode($val), 1, -1);
                    $str .= '="'.addslashes($strVal).'"';
                }
            }
        }

        return $str;
    }

    /**
     * Build link list.
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
                $active = (int) ($active || preg_match('/class="\\b'.preg_quote($aClass[1], '/').'\\b"/', $child));
            }

            if (empty($aAttr['href'])) {
                $aAttr['href'] = $mItem['route'] ? $this->fw->path($mItem['route'], $mItem['args'], $mItem['query']) : '#';
            }

            $iAttr['class'] = trim(($iAttr['class'] ?? null).' '.$aClass[$active]) ?: null;

            $content = $this->tag('a', $aAttr, true, $label);
            $lists .= $this->tag('li', $iAttr, true, $content.$child);
        }

        return $this->tag('ul', $mConfig['root_attr'], true, $lists);
    }

    /**
     * Build pagination.
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
            'route' => $this->fw->get('ALIAS'),
            'route_data' => $this->fw->get('PARAMETERS'),
            'route_query' => $this->fw->get('GET') ?? array(),
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

            return $this->fw->path($mConfig['route'], $mConfig['route_data'], $query);
        };
        $child = function ($label, array $attr = null, array $wrapperAttr = null, string $tag = 'a') {
            $content = $this->tag($tag, $attr, true, (string) $label);

            return $this->tag('li', $wrapperAttr, true, $content);
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

        $pagination = $this->tag('ul', $mConfig['parent'], true, $lists);

        return $this->tag('nav', $mConfig['wrapper'], true, $pagination);
    }
}
