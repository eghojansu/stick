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

use Fal\Stick\Fw;

/**
 * Menu list helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MenuList
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
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
    public function build(array $items, string $activeRoute = null, array $config = null): string
    {
        if (empty($items)) {
            return '';
        }

        if (null === $config) {
            $config = array();
        }

        $config += array(
            'root_attr' => null,
            'parent_attr' => null,
            'parent_item_attr' => null,
            'parent_wrapper_attr' => null,
            'active_class' => 'active',
        );
        $dItem = array(
            'route' => null,
            'args' => null,
            'attr' => null,
            'item_attr' => null,
            'wrapper_attr' => null,
            'items' => null,
        );
        $aClass = array(null, $config['active_class']);
        $lists = '';

        foreach ($items as $label => $item) {
            $child = '';
            $mItem = (is_array($item) ? $item : array('route' => $item)) + $dItem;
            $aAttr = (array) $mItem['attr'];
            $iAttr = (array) $mItem['item_attr'];
            $active = (int) ($mItem['route'] && ($mItem['route'] === $activeRoute));

            if ($mItem['items']) {
                $cAttr = array('root_attr' => (array) $mItem['wrapper_attr']) + $config;

                $cAttr['root_attr'] += (array) $config['parent_wrapper_attr'];
                $aAttr += (array) $config['parent_item_attr'];
                $iAttr += (array) $config['parent_attr'];

                $child = $this->build($mItem['items'], $activeRoute, $cAttr);
                $active = (int) ($active || preg_match('/class="\\b'.preg_quote($aClass[1], '/').'\\b"/', $child));
            }

            if (empty($aAttr['href'])) {
                $aAttr['href'] = $mItem['route'] ? $this->fw->path($mItem['route'], $mItem['args']) : '#';
            }

            $iAttr['class'] = trim(($iAttr['class'] ?? null).' '.$aClass[$active]) ?: null;

            $content = Element::tag('a', $aAttr, true, $label);
            $lists .= Element::tag('li', $iAttr, true, $content.$child);
        }

        return Element::tag('ul', $config['root_attr'], true, $lists);
    }
}
