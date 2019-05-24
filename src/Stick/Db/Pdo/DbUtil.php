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

use Fal\Stick\Fw;

/**
 * Db utility.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class DbUtil
{
    const PARAM_FLOAT = -1;

    /**
     * Returns default value from table schema.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function defaultValue($value)
    {
        return is_string($value) ? Fw::cast(preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $value)) : $value;
    }

    /**
     * Extract column definition.
     *
     * @param string $type
     *
     * @return array
     */
    public static function extractType(string $type): array
    {
        if (preg_match('/^([^\(]+)\((.+)\)$/', $type, $match)) {
            return array(
                'data_type' => $match[1],
                'constraint' => $match[2],
            );
        }

        return array(
            'data_type' => $type,
            'constraint' => null,
        );
    }

    /**
     * Returns variable's pdo type.
     *
     * @param mixed       $val
     * @param string|null $type
     * @param bool        $hybrid
     *
     * @return int
     */
    public static function type($val, string $type = null, bool $hybrid = true): int
    {
        if (null === $type) {
            $type = gettype($val);
        }

        $converts = array(
            'bool' => \PDO::PARAM_BOOL,
            'null' => \PDO::PARAM_NULL,
            'int\b|integer' => \PDO::PARAM_INT,
            'blob|byte|image|binary' => \PDO::PARAM_LOB,
            'float|real|double|decimal|numeric' => $hybrid ? self::PARAM_FLOAT : \PDO::PARAM_STR,
        );

        foreach ($converts as $pattern => $pdoType) {
            if (preg_match('/'.$pattern.'/i', $type)) {
                return $pdoType;
            }
        }

        return \PDO::PARAM_STR;
    }

    /**
     * Returns php value.
     *
     * @param mixed    $val
     * @param int|null $type
     *
     * @return mixed
     */
    public static function value($val, int $type = null)
    {
        switch ($type ?? self::type($val)) {
            case \PDO::PARAM_NULL:
                return null;
            case \PDO::PARAM_BOOL:
                return (bool) $val;
            case \PDO::PARAM_LOB:
                return (string) $val;
            case \PDO::PARAM_STR:
                return (string) $val;
            case \PDO::PARAM_INT:
            case self::PARAM_FLOAT:
                return $val + 0;
            default:
                return $val;
        }
    }
}
