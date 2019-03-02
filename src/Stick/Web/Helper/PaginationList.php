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

namespace Fal\Stick\Web\Helper;

use Fal\Stick\Util;
use Fal\Stick\Web\RequestStackInterface;
use Fal\Stick\Web\Router\RouterInterface;
use Fal\Stick\Web\UrlGeneratorInterface;

/**
 * Pagination list helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class PaginationList
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var RequestStackInterface
     */
    protected $requestStack;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Class constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStackInterface $requestStack
     * @param RouterInterface       $router
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStackInterface $requestStack, RouterInterface $router)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->router = $router;
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

        $match = $this->router->getRouteMatch();

        if (!isset($config['route'])) {
            $config['route'] = $match ? $match->getAlias() : null;
        }

        if (!isset($config['route_data'])) {
            $config['route_data'] = $match ? $match->getArguments() : null;
        }

        if (!isset($config['route_query'])) {
            $config['route_query'] = $this->requestStack->getCurrentRequest()->query->all();
        }

        $config += array(
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

            return $this->urlGenerator->generate($config['route'], $config['route_data'] + $query);
        };
        $child = function ($label, array $attr = null, array $wrapperAttr = null, $tag = 'a') {
            $content = Util::tag($tag, $attr, true, (string) $label);

            return Util::tag('li', $wrapperAttr, true, $content);
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

        $pagination = Util::tag('ul', $config['parent'], true, $lists);

        return Util::tag('nav', $config['wrapper'], true, $pagination);
    }
}
