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

namespace Fal\Stick\Template;

/**
 * Template interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface TemplateInterface
{
    /**
     * Find view path.
     *
     * @param string $view
     *
     * @return string
     *
     * @throws LogicException If view not found in any directory
     */
    public function findView(string $view): string;

    /**
     * Render view.
     *
     * @param string     $view
     * @param array|null $context
     *
     * @return string
     */
    public function render(string $view, array $context = null): string;
}
