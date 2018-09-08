<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Sql;

use Fal\Stick\App;
use Fal\Stick\ResponseException;

/**
 * Helper to convert parameter to Mapper.
 *
 * Support mapper with composit primary keys, with requirement,
 * raw arguments should be in exact order as composit keys order.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class MapperParameterConverter
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ReflectionFunctionAbstract
     */
    private $ref;

    /**
     * @var array
     */
    private $params;

    /**
     * Class constructor.
     *
     * @param App        $app
     * @param Connection $db
     * @param mixed      $handler
     * @param array      $params
     */
    public function __construct(App $app, Connection $db, $handler, array $params)
    {
        $this->app = $app;
        $this->db = $db;
        $this->ref = is_array($handler) ? new \ReflectionMethod($handler[0], $handler[1]) : new \ReflectionFunction($handler);
        $this->params = $params;
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve()
    {
        $resolved = array();

        foreach ($this->ref->getParameters() as $param) {
            if ($this->wants($param)) {
                $resolved[] = $this->doResolve($param);
            } elseif (isset($this->params[$param->name])) {
                $resolved[] = $this->params[$param->name];
            } else {
                $resolved[] = null;
            }
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
    private function wants(\ReflectionParameter $param)
    {
        $class = $param->getClass();

        return $class && is_subclass_of($class->name, Mapper::class) && isset($this->params[$param->name]);
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
    private function doResolve(\ReflectionParameter $param)
    {
        $classname = $param->getClass()->name;

        $mapper = new $classname($this->db);
        $keys = array_keys($mapper->keys());
        $kcount = count($keys);
        $vals = $this->pickstartsat($param->name, $kcount);
        $vcount = count($vals);

        if ($vcount !== $kcount) {
            $message = 'Insufficient primary keys value, expect value of "'.implode(', ', $keys).'".';

            throw new ResponseException(500, $message);
        }

        call_user_func_array(array($mapper, 'withId'), array($vals));

        if ($mapper->dry()) {
            $message = 'Record of '.$mapper->getTable().' is not found.';
            $args = array_combine(explode(',', '%'.implode('%,%', $keys).'%'), $vals);
            $response = $this->app->transAlt('message.record_not_found', $args, $message);

            throw new ResponseException(404, $response);
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
    private function pickstartsat($start, $count)
    {
        $res = array();
        $used = 0;
        $started = false;

        foreach ($this->params as $key => $value) {
            if (!$started) {
                $started = $key === $start;
            }

            if ($started) {
                if ($used++ >= $count) {
                    return $res;
                }

                $res[] = $value;
            }
        }

        return $res;
    }
}
