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

namespace Fal\Stick\Database\Event;

use Fal\Stick\Database\Mapper;
use Fal\Stick\EventDispatcher\Event;

/**
 * Mapper event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperEvent extends Event
{
    /**
     * @var Mapper
     */
    protected $mapper;

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
     * Returns mapper.
     *
     * @return Mapper
     */
    public function getMapper(): Mapper
    {
        return $this->mapper;
    }
}
