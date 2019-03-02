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

namespace Fal\Stick\Database;

use Fal\Stick\Container\ContainerInterface;

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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve parameters.
     *
     * @return array
     */
    public function resolve(callable $handler, array $params): array
    {
        $mappers = static::findMappers($handler);

        if (!$mappers) {
            return $params;
        }

        $keys = array_keys($params);
        $max = count($params);
        $result = array();
        $ptr = 0;

        for (; $ptr < $max;) {
            $name = $keys[$ptr];

            if (isset($mappers[$name])) {
                $result[$name] = $this->resolveMapper($mappers[$name], $params, $ptr);
            } else {
                $result[$name] = $params[$name];
                ++$ptr;
            }
        }

        return $result;
    }

    /**
     * Resolve mapper parameter.
     *
     * @param string $class
     * @param array  $params
     * @param int    &$ptr
     *
     * @return Mapper
     *
     * @throws LogicException if record not found
     */
    protected function resolveMapper(string $class, array $params, int &$ptr): Mapper
    {
        $mapper = $this->container->get($class);
        $keys = $mapper->getSchema()->getKeys();
        $kcount = count($keys);
        $values = array_values(array_slice($params, $ptr, $kcount));

        if (!$mapper->find(...$values)->valid()) {
            throw new \LogicException(sprintf('Record not found (%s).', $mapper->getName()));
        }

        $ptr += $kcount;

        return $mapper;
    }

    /**
     * Find Mapper subclass args.
     *
     * @param mixed $handler
     *
     * @return array
     */
    protected static function findMappers(callable $handler): array
    {
        $ref = is_array($handler) ? new \ReflectionMethod($handler[0], $handler[1]) : new \ReflectionFunction($handler);
        $mappers = array();

        foreach ($ref->getParameters() as $param) {
            $class = $param->getClass();
            $mapper = $class && is_subclass_of($class->name, 'Fal\\Stick\\Database\\Mapper');

            if ($mapper) {
                $mappers[$param->name] = $class->name;
            }
        }

        return $mappers;
    }
}
