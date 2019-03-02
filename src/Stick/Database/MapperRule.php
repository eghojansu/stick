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

namespace Fal\Stick\Database;

use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Validation\RuleInterface;
use Fal\Stick\Validation\RuleTrait;

/**
 * Mapper related validator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperRule implements RuleInterface
{
    use RuleTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Check if given value exists.
     *
     * @param mixed  $val
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    protected function _exists($val, $table, $column): bool
    {
        $mapper = $this->container->get('Fal\\Stick\\Database\\Mapper', array('name' => $table));
        $mapper->first(array($column => $val));

        return $mapper->valid();
    }

    /**
     * Check if given value is unique.
     *
     * @param mixed       $val
     * @param string      $table
     * @param string      $column
     * @param string|null $fid
     * @param mixed       $id
     *
     * @return bool
     */
    protected function _unique($val, $table, $column, $fid = null, $id = null): bool
    {
        $mapper = $this->container->get('Fal\\Stick\\Database\\Mapper', array('name' => $table));
        $mapper->first(array($column => $val));

        return !$mapper->valid() || ($fid && (!$mapper->getSchema()->exists($fid) || $mapper->getSchema()->get($fid) == $id));
    }
}
