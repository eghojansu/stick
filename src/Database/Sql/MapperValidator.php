<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Database\Sql;

use Fal\Stick\Core;
use Fal\Stick\Validation\ValidatorInterface;
use Fal\Stick\Validation\ValidatorTrait;

/**
 * Mapper related validator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperValidator implements ValidatorInterface
{
    use ValidatorTrait;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Sql
     */
    protected $db;

    /**
     * Class constructor.
     *
     * @param Core $core
     * @param Sql  $db
     */
    public function __construct(Core $core, Sql $db)
    {
        $this->core = $core;
        $this->db = $db;
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
    protected function _exists($val, $table, $column)
    {
        $mapper = new Mapper($this->core, $this->db, $table);
        $mapper->load([$column => $val], ['limit' => 1]);

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
    protected function _unique($val, $table, $column, $fid = null, $id = null)
    {
        $mapper = new Mapper($this->core, $this->db, $table);
        $mapper->load([$column => $val], ['limit' => 1]);

        return $mapper->dry() || ($fid && (!$mapper->exists($fid) || $mapper->get($fid) == $id));
    }
}
