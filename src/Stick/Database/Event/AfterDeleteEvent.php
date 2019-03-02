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
use Fal\Stick\Database\Row;

/**
 * After delete event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class AfterDeleteEvent extends MapperEvent
{
    /**
     * @var Row
     */
    protected $row;

    /**
     * Class constructor.
     *
     * @param Mapper   $mapper
     * @param Row|null $row
     */
    public function __construct(Mapper $mapper, Row $row = null)
    {
        parent::__construct($mapper);

        $this->row = $row;
    }

    /**
     * Returns deleted row.
     *
     * @return Row|null
     */
    public function getRow(): ?Row
    {
        return $this->row;
    }
}
