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

use Fal\Stick\Fw;
use Fal\Stick\HttpException;

/**
 * Helper to convert parameter to Mapper.
 *
 * Support mapper with composit primary keys, with requirement,
 * raw arguments should be in exact order as composit keys order.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class ParameterConverter
{
    /**
     * @var Fw
     */
    private $fw;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var array
     */
    private $mappers;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $keys;

    /**
     * @var array
     */
    private $resolved = array();

    /**
     * @var int
     */
    private $ptr = 0;

    /**
     * @var int
     */
    private $max;

    /**
     * Class constructor.
     *
     * @param Fw    $fw
     * @param mixed $handler
     * @param array $params
     */
    public function __construct(Fw $fw, $handler, array $params)
    {
        $this->fw = $fw;
        $this->params = $params;
        $this->keys = array_keys($params);
        $this->max = count($params);
        $this->mappers = $this->findMappers($handler);
    }

    /**
     * Create instance.
     *
     * @param Fw    $fw
     * @param mixed $handler
     * @param array $params
     *
     * @return ParameterConverter
     */
    public static function create(Fw $fw, $handler, array $params): ParameterConverter
    {
        return new self($fw, $handler, $params);
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve(): array
    {
        if (!$this->mappers || !$this->params) {
            return $this->params;
        }

        for (; $this->ptr < $this->max;) {
            $name = $this->keys[$this->ptr];

            if (isset($this->mappers[$name])) {
                $class = $this->mappers[$name];
                $this->resolved[$name] = $this->resolveMapper($class);
            } else {
                $this->resolved[$name] = $this->params[$name];
                ++$this->ptr;
            }
        }

        return $this->resolved;
    }

    /**
     * Resolve mapper parameter.
     *
     * @param string $class
     *
     * @return Mapper
     *
     * @throws HttpException if record not found
     */
    private function resolveMapper(string $class): Mapper
    {
        $mapper = $this->fw->createInstance($class);
        $keys = $mapper->keys(false);
        $kcount = count($keys);
        $vals = array_values(array_slice($this->params, $this->ptr, $kcount));
        $vcount = count($vals);
        $found = $mapper->find(...$vals);

        if ($found) {
            $this->ptr += $kcount;

            return $found;
        }

        throw new HttpException($this->fw->trans('message.record_not_found', array_combine(explode(',', '%'.implode('%,%', $keys).'%'), $vals), sprintf('Record of %s is not found.', $mapper->table())), 404);
    }

    /**
     * Find Mapper subclass args.
     *
     * @param mixed $handler
     *
     * @return array
     */
    private function findMappers($handler): array
    {
        $ref = is_array($handler) ? new \ReflectionMethod($handler[0], $handler[1]) : new \ReflectionFunction($handler);
        $mappers = array();

        foreach ($ref->getParameters() as $param) {
            $class = $param->getClass();
            $mapper = $class && is_subclass_of($class->name, Mapper::class);

            if ($mapper) {
                $mappers[$param->name] = $class->name;
            }
        }

        return $mappers;
    }
}
