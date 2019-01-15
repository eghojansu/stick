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
use Fal\Stick\Event\GetControllerArgumentsEvent;

/**
 * Helper to convert parameter to Mapper.
 *
 * Support mapper with composit primary keys, with requirement,
 * raw arguments should be in exact order as composit keys order.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ParameterConverter
{
    /**
     * @var Core
     */
    protected $core;

    /**
     * @var array
     */
    protected $mappers;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $keys;

    /**
     * @var array
     */
    protected $resolved = [];

    /**
     * @var int
     */
    protected $ptr = 0;

    /**
     * @var int
     */
    protected $max;

    /**
     * Class constructor.
     *
     * @param Core  $core
     * @param mixed $handler
     * @param array $params
     */
    public function __construct(Core $core, $handler, array $params)
    {
        $this->core = $core;
        $this->params = $params;
        $this->keys = array_keys($params);
        $this->max = count($params);
        $this->mappers = $this->findMappers($handler);
    }

    /**
     * Create instance.
     *
     * @param GetControllerArgumentsEvent $event
     *
     * @return ParameterConverter
     */
    public static function fromEvent(GetControllerArgumentsEvent $event)
    {
        return new self($event->getCore(), $event->getController(), $event->getArguments());
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve()
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
                $this->ptr++;
            }
        }

        return $this->resolved;
    }

    /**
     * Resolve mapper parameter.
     *
     * @param string $class
     *
     * @throws HttpException if record not found
     *
     * @return Mapper
     */
    protected function resolveMapper($class)
    {
        $mapper = $this->core->service($class, false);
        $keys = $mapper->keys(false);
        $kcount = count($keys);
        $vals = array_values(array_slice($this->params, $this->ptr, $kcount));
        $vcount = count($vals);
        $found = $mapper->find($vals);

        if ($found) {
            $this->ptr += $kcount;

            return $found;
        }

        throw new \RuntimeException(sprintf('Record is not found: "%s"', $this->core->className($class)));
    }

    /**
     * Find Mapper subclass args.
     *
     * @param mixed $handler
     *
     * @return array
     */
    protected function findMappers($handler)
    {
        $ref = is_array($handler) ? new \ReflectionMethod($handler[0], $handler[1]) : new \ReflectionFunction($handler);
        $mappers = [];

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
