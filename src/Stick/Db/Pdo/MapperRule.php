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

namespace Fal\Stick\Db\Pdo;

use Fal\Stick\Validation\RuleTrait;
use Fal\Stick\Validation\RuleInterface;

/**
 * Mapper rule.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperRule implements RuleInterface
{
    use RuleTrait;

    /**
     * @var Db
     */
    protected $db;

    /**
     * Class constructor.
     *
     * @param Db $db
     */
    public function __construct(Db $db)
    {
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
    protected function _exists($val, string $table, string $column): bool
    {
        $mapper = new Mapper($this->db, $table);
        $mapper->findOne(array($column => $val));

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
    protected function _unique($val, string $table, string $column, string $fid = null, $id = null): bool
    {
        $mapper = new Mapper($this->db, $table);
        $mapper->findOne(array($column => $val));

        return !$mapper->valid() || ($fid && (!$mapper->schema->has($fid) || $mapper->get($fid) == $id));
    }
}
