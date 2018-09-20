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

namespace Fal\Stick\Sql;

use Fal\Stick\Event;

/**
 * Allow mapper modification.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperEvent extends Event
{
    /**
     * Mapper instance.
     *
     * @var Mapper
     */
    private $mapper;

    /**
     * Class constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Returns mapper instance.
     *
     * @return Mapper
     */
    public function getMapper(): Mapper
    {
        return $this->mapper;
    }
}
