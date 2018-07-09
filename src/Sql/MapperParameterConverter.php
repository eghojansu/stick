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

use Fal\Stick\ResponseException;
use Fal\Stick\Translator;

/**
 * Helper to convert parameter to Mapper.
 *
 * Support mapper with composit primary keys, with requirement,
 * raw arguments should be in exact order as composit keys order.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MapperParameterConverter
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ReflectionFunctionAbstract
     */
    private $ref;

    /**
     * Raw params.
     *
     * @var array
     */
    private $raw;

    /**
     * @var array
     */
    private $params;

    /**
     * Class constructor.
     *
     * @param Connection                 $db
     * @param Translator                 $translator
     * @param ReflectionFunctionAbstract $ref
     * @param array                      $raw
     * @param array                      $params
     */
    public function __construct(Connection $db, Translator $translator, \ReflectionFunctionAbstract $ref, array $raw, array $params)
    {
        $this->translator = $translator;
        $this->db = $db;
        $this->ref = $ref;
        $this->raw = $raw;
        $this->params = $params;
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve(): array
    {
        $resolved = [];
        $pos = 0;

        foreach ($this->ref->getParameters() as $param) {
            $resolved[$pos] = $this->wants($param) ? $this->doResolve($param) : $this->params[$pos];
            ++$pos;
        }

        return $resolved;
    }

    /**
     * Check if we want process these parameter.
     *
     * @param ReflectionParameter $param
     *
     * @return bool
     */
    private function wants(\ReflectionParameter $param): bool
    {
        $class = $param->getClass();

        return $class && is_subclass_of($class->name, Mapper::class) && isset($this->raw[$param->name]);
    }

    /**
     * Resolve mapper parameter.
     *
     * @param ReflectionParameter $param
     *
     * @return Mapper|null
     *
     * @throws ResponseException if primary keys insufficient or record not found
     */
    private function doResolve(\ReflectionParameter $param): Mapper
    {
        $classname = $param->getClass()->name;

        $mapper = new $classname($this->db);
        $keys = $mapper->getKeys();
        $kcount = count($keys);
        $vals = $this->pickstartsat($param->name, $kcount);
        $vcount = count($vals);

        if ($vcount !== $kcount) {
            throw new ResponseException('Insufficient primary keys value, expect value of "'.implode(', ', $keys).'".');
        }

        $use = array_values($vals);
        $mapper->find(...$use);

        if ($mapper->dry()) {
            $message = 'Record of '.$mapper->getTable().' not found.';
            $args = array_combine(explode(',', '%'.implode('%,%', $keys).'%'), $vals);
            $use = $this->translator->transAlt('message.record_not_found', $args, $message);

            throw new ResponseException($use, 404);
        }

        return $mapper;
    }

    /**
     * Pick raw values start at key.
     *
     * @param string $start
     * @param int    $count
     *
     * @return array
     */
    private function pickstartsat(string $start, int $count): array
    {
        $res = [];
        $used = 0;
        $started = false;

        foreach ($this->raw as $key => $value) {
            if (!$started) {
                $started = $key === $start;
            }

            if ($started) {
                if ($used++ >= $count) {
                    return $res;
                }

                $res[$key] = $value;
            }
        }

        return $res;
    }
}
