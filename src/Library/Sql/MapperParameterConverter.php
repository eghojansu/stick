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

namespace Fal\Stick\Library\Sql;

use Fal\Stick\App;
use Fal\Stick\HttpException;

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
     * @param App        $app
     * @param Connection $db
     * @param mixed      $handler
     * @param array      $params
     */
    public function __construct(App $app, Connection $db, $handler, array $params)
    {
        $this->app = $app;
        $this->db = $db;
        $this->params = $params;
        $this->keys = array_keys($params);
        $this->max = count($params);
        $this->mappers = $this->resolveMapperClasses($handler);
    }

    /**
     * Returns true if handler require mapper parameter.
     *
     * @return bool
     */
    public function hasMapper(): bool
    {
        return (bool) $this->mappers;
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve(): array
    {
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
        $mapper = new $class($this->app, $this->db);
        $keys = array_keys($mapper->keys());
        $kcount = count($keys);
        $vals = array_slice($this->params, $this->ptr, $kcount);
        $vcount = count($vals);

        $mapper->withId($vals);

        if ($mapper->dry()) {
            $message = 'Record of '.$mapper->getTable().' is not found.';
            $args = array_combine(explode(',', '%'.implode('%,%', $keys).'%'), $vals);
            $response = $this->app->transAlt('message.record_not_found', $args, $message);

            throw new HttpException($response, 404);
        }

        $this->ptr += $kcount;

        return $mapper;
    }

    /**
     * Find Mapper subclass args.
     *
     * @param mixed $handler
     *
     * @return array
     */
    private function resolveMapperClasses($handler): array
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
