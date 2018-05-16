<?php declare(strict_types=1);

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

final class MapperValidator extends AbstractValidator
{
    protected $messages = [
        'exists' => null,
        'unique' => 'This value is already used.',
    ];

    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    protected function _exists($val, string $table, string $column): bool
    {
        return $this->mapper->withTable($table)->load([$column=>$val])->valid();
    }

    protected function _unique($val, string $table, string $column, string $fid = null, $id = null): bool
    {
        $mapper = $this->mapper->withTable($table)->load([$column=>$val]);

        return $mapper->dry() || ($fid && (!$mapper->exists($fid) || $mapper->get($fid) == $id));
    }
}
