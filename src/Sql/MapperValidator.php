<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Sql;

use Fal\Stick\Validation\AbstractValidator;

/**
 * Mapper related validator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class MapperValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $messages = [
        'exists' => null,
        'unique' => 'This value is already used.',
    ];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Class constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
        return (new Mapper($this->connection, $table))->load([$column => $val])->valid();
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
        $mapper = (new Mapper($this->connection, $table))->load([$column => $val]);

        return $mapper->dry() || ($fid && (!$mapper->exists($fid) || $mapper->get($fid) == $id));
    }
}
