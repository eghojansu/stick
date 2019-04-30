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
 * Pagination list helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class PaginationList
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * Class constructor.
     *
     * @param Fw  $fw
     * @param Tag $tag
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
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
    public function build(int $page, int $max, array $config = null): string
    {
        if ($max < 1 || $page < 1) {
            return '';
        }

        if (null === $config) {
            $config = array();
        }

        $config += array(
            'route' => $this->fw->get('ALIAS'),
            'route_data' => $this->fw->get('PARAMS') ?? array(),
            'route_query' => $this->fw->get('GET') ?? array(),
            'page_query' => 'page',
            'adjacent' => 2,
            'parent' => array('class' => 'pagination'),
            'wrapper' => array('aria-label' => 'Page navigation'),
            'prev_label' => 'Prev',
            'next_label' => 'Next',
            'active_class' => 'active',
        );
        $urlPage = function ($page) use ($config) {
            $query = array($config['page_query'] => $page) + $config['route_query'];

            return $this->fw->path($config['route'], $config['route_data'] + $query);
        };
        $child = function ($label, array $attr = null, array $wrapperAttr = null, $tag = 'a') {
            $content = Element::tag($tag, $attr, true, (string) $label);

            return Element::tag('li', $wrapperAttr, true, $content);
        };
        $adjacent = $config['adjacent'];
        $rangeStart = $page <= $adjacent ? 1 : $page - $adjacent;
        $rangeEnd = $page > $max - $adjacent ? $max : $page + $adjacent;
        $aClass = array(null, $config['active_class']);
        $lists = '';

        if ($rangeStart > 1) {
            $lists .= $child($config['prev_label'], array(
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
            $lists .= $child($config['next_label'], array(
                'href' => $urlPage($page + 1),
            ));
        }

        $pagination = Element::tag('ul', $config['parent'], true, $lists);

        return Element::tag('nav', $config['wrapper'], true, $pagination);
    }
}
