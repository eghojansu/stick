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
use Fal\Stick\Security\Auth;

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
     * @var Auth
     */
    protected $auth;

    /**
     * Class constructor.
     *
     * @param Fw        $fw
     * @param Auth|null $auth
     */
    public function __construct(Fw $fw, Auth $auth = null)
    {
        $this->fw = $fw;
        $this->auth = $auth;
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
            'parent_wrapper_attr' => null,
            'global_attr' => null,
            'global_item_attr' => null,
            'active_class' => 'active',
        );
        $dItem = array(
            'route' => null,
            'args' => null,
            'attr' => null,
            'item_attr' => null,
            'wrapper_attr' => null,
            'items' => null,
            'roles' => null,
        );
        $lists = '';

        foreach ($items as $label => $item) {
            $child = '';
            $mItem = (is_array($item) ? $item : array('route' => $item)) + $dItem;
            $aAttr = Element::mergeAttr($config['global_attr'], $mItem['attr']);
            $iAttr = Element::mergeAttr($config['global_item_attr'], $mItem['item_attr']);
            $active = $mItem['route'] && $mItem['route'] === $activeRoute;

            if ($mItem['roles'] && $this->auth && !$this->auth->isGranted($mItem['roles'])) {
                continue;
            }

            if ($mItem['items']) {
                $cAttr = array('root_attr' => (array) $mItem['wrapper_attr']) + $config;
                $cAttr['root_attr'] += (array) $config['parent_wrapper_attr'];

                $aAttr = Element::mergeAttr($config['parent_item_attr'], $aAttr);
                $iAttr = Element::mergeAttr($config['parent_attr'], $iAttr);

                $child = $this->build($mItem['items'], $activeRoute, $cAttr);
                $active = $active || preg_match(
                    '/class="\\b'.preg_quote($config['active_class'], '/').'\\b"/',
                    $child
                );
            }

            if (empty($aAttr['href'])) {
                $aAttr['href'] = $mItem['route'] ? $this->fw->path($mItem['route'], $mItem['args']) : '#';
            }

            if (
                $active &&
                $activeClass = trim(($iAttr['class'] ?? null).' '.$config['active_class'])
            ) {
                $iAttr['class'] = $activeClass;
            }

            $content = Element::tag('a', $aAttr, true, $label);
            $lists .= Element::tag('li', $iAttr, true, $content.$child);
        }

        return $lists ? Element::tag('ul', $config['root_attr'], true, $lists) : '';
    }
}
