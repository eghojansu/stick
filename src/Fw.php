<?php

namespace Ekok\Stick;

class Fw
{
	private array $hive = array();
    private array $safe = array();

    public function __construct()
    {
        $this->hive = $this->safe = array(
            'RULES' => array(),
            'RULE_SELF' => substr(static::class, strrpos(static::class, '\\') + 1),
            'RULE_FACTORIES' => array(),
            'RULE_INSTANCES' => array(),
        );
    }

	public static function split(mixed $value, string $delim = null): array
	{
		return match(true) {
			is_array($value) => $value,
			is_string($value) => array_filter(
				preg_split(
					'/[' . preg_quote($delim ?? ',;|', '/') . ']/',
					$value,
					0,
					PREG_SPLIT_NO_EMPTY,
				),
			),
			default => (array) $value,
		};
	}

    public static function tap(mixed $value, callable $cb = null): mixed
    {
        if (null === $cb) {
            return new Tapped($value);
        }

        $cb($value);

        return $value;
    }

    public static function fnIsInternal(callable $cb): bool
    {
        return (new \ReflectionFunction($cb))->isInternal();
    }

    public static function fnSafe(callable $cb): callable
    {
        return (
            $cb instanceof \Closure
            || is_array($cb)
            || !static::fnIsInternal($cb)
        ) ? $cb : static fn(mixed $value) => $cb($value);
    }

    public static function fnThrough(): callable
    {
        return static fn($value) => $value;
    }

	public static function loop(
        mixed $value,
        callable $cb,
        bool $storing = true,
        mixed $carry = null,
        bool $assoc = true,
    ): Looped
	{
		return new Looped(
            is_iterable($value) ? $value : static::split($value),
            $cb,
            $storing,
            $carry,
            $assoc,
        );
	}

    public static function compare(mixed $items, callable $cb, bool $match = null): Looped
    {
        $compare = static::fnSafe($cb);
        $expect = $match ?? true;

        return static::loop(
            $items,
            static fn($value, $key, Looped $loop) => $loop->stop(
                $expect === $compare($value, $key, $loop)
            ),
            false,
        );
    }

    public static function some(mixed $items, callable $fn, bool $match = null): bool
    {
        return static::compare($items, $fn, $match)->stopped;
    }

    public static function every(mixed $items, callable $fn, bool $match = null): bool
    {
        return !static::some($items, $fn, $match);
    }

    public static function map(mixed $items, callable $cb): array
    {
        return static::loop($items, static::fnSafe($cb))->result;
    }

    public static function reduce(mixed $items, callable $cb, $carry = null): mixed
    {
        return static::loop(
            $items,
            static fn($value, $key, Looped $loop) => $cb(
                $loop->value,
                $value,
                $key,
                $loop,
            ),
            false,
            $carry,
        )->value;
    }

    public static function reduceArray(mixed $items, callable $fn): array
    {
        return static::reduce($items, $fn, array());
    }

    public static function coalesce(mixed $items, callable $cb = null): mixed
    {
        $call = $cb ?? static::fnThrough();

        return static::loop(
            $items,
            static fn($value, $key, Looped $loop) => $loop->last(
                $call($value, $key, $loop)
            )->stop(null !== $loop->value),
            false,
        )->value;
    }

    public static function filter(
        mixed $items,
        callable $cb,
        bool $match = null,
        bool $assoc = true,
        bool $byKey = false,
    ): array
    {
        $compare = static::fnSafe($cb);
        $expect = $match ?? true;

        return static::loop(
            $items,
            static fn($value, $key, Looped $loop) => $loop->store(
                $expect === (
                    $byKey ? $compare($key, $loop) : $compare($value, $key, $loop)
                )
            ),
            true,
            null,
            $assoc,
        )->result;
    }

    public static function only(mixed $items, array|string $keys, bool $match = null): array
    {
        $expected = static::split($keys);

        return static::filter(
            $items,
            static fn($key) => in_array($key, $expected),
            $match,
            true,
            true,
        );
    }

    public static function except(mixed $items, array|string $keys): array
    {
        return static::only($items, $keys, false);
    }

    public static function find(mixed $items, callable $cb, bool $match = null, bool $key = false): mixed
    {
        $loop = static::compare($items, $cb, $match);

        return $loop->stopped ? ($key ? $loop->key : $loop->value) : null;
    }

    public static function findIndex(mixed $items, callable $cb, bool $match = null): mixed
    {
        return static::find($items, $cb, $match, true);
    }

    public function each(mixed $value, callable $cb): static
    {
        static::loop($value, $cb, false);

        return $this;
    }

    public function ruleName(string $name): string
    {
        return strtolower($name);
    }

	/**
	 * Add class rule
     *
	 * @param string $name The name of the class to add the rule for
	 * @param array  $rule The container can be fully configured using rules provided by associative arrays
     *
     * @return static
	 */
	public function ruleAdd(string $name, \Closure|array|string|bool $rule = null): static
    {
        $named = $this->ruleName($name);
        $apply = match(true) {
            $rule => array('shared' => $rule), // A class $name marked as shared,
            $rule instanceof \Closure => array('factory' => $rule), // A class has its own factory
            is_string($rule) => array('class' => $name), // A $name is instance of $rule
            default => $rule ?? array(), // fallback with inheritence is true
        };

        if (isset($apply['class']) && ($apply['inherit'] ?? true)) {
            $apply = array_replace_recursive(
                $this->ruleGet($apply['class']),
                $apply,
            );
        }

        $this->hive['RULES'][$named] = array_replace_recursive(
            $this->ruleGet($named),
            $apply,
        );

        //Clear any existing instance or cache for this class
        unset(
            $this->hive['RULE_INSTANCES'][$named],
            $this->hive['RULE_FACTORIES'][$named],
        );

		return $this;
	}

	/**
     * Add rules as array
     *
     * @param array Rules in a single array [name => $rule] format
     *
     * @return
     */
	public function ruleMerge(array ...$rules): static
    {
        return $this->each(
            $rules,
            fn($rule, $name) => $this->ruleAdd($name, $rule),
        );
	}

	/**
	 * Returns the rule that will be applied to the class $name when calling create()
     *
	 * @param string name The name of the class to get the rules for
     *
	 * @return array The rules for the specified class
	 */
	public function ruleGet(string $name): array
    {
        return (
            $this->hive['RULES'][$name] ??
            $this->hive['RULES'][$this->ruleName($name)] ??
            static::coalesce(
                $this->hive['RULES'],
                static fn(array $rule, string $named) => (
                    $named !== '*' // It's not the default rule
                    && empty($rule['class']) // It's not a named instance, the rule is applied to a class name
                    && is_subclass_of($name, $named) // The rule is applied to a parent class
                    && ($rule['inherit'] ?? true) // And that rule should be inherited to subclasses
                ) ? $rule : null,
            ) ??
            // No rule has matched, return the default rule if it's set
            $this->hive['RULES']['*'] ??
            array()
        );
	}

	/**
	 * Returns a fully constructed object based on $name using $args and $share as constructor arguments if supplied
	 *
     * @param string name The name of the class to instantiate
	 * @param array $args An array with any additional arguments to be passed into the constructor upon instantiation
	 * @param array $share a list of defined in shareInstances for objects higher up the object graph, should only be used internally
	 *
     * @return object A fully constructed object based on the specified input arguments
	 */
	public function ruled(string $name, array $args = null, array $share = null): object
    {
        return (
            $this->hive['RULE_INSTANCES'][$name] ??
            $this->hive['RULE_INSTANCES'][$this->ruleName($name)] ??
            $this->ruleSelf($name) ??
            $this->ruleInvalidate($name, $args, $share)
        );
	}

    public function ruleInvalidate(string $name, array $args = null, array $share = null): object
    {
        $named = $this->ruleName($name);
        $factory = (
            $this->hive['RULE_FACTORIES'][$name] ??
            $this->hive['RULE_FACTORIES'][$named] ??
            $this->ruleFactory($named, $rule = $this->ruleGet($name))
        );

        if (isset($rule)) {
            $this->hive['RULE_FACTORIES'][$named] = $factory;
        }

        return $factory($args ?? array(), $share ?? array());
    }

    private function ruleSelf(string $name): object|null
    {
        return (
            is_a($name, static::class)
            || $name == $this->hive['RULE_SELF']
            || 0 === strcasecmp($name, $this->hive['RULE_SELF'])
        ) ? $this : null;
    }

	/**
	 * Returns a closure for creating object $name based on $rule, caching the reflection object for later use
	 *
     * @param string $name the Name of the class to get the closure for
	 * @param array $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
	 *
     * @return callable A closure
	 */
	private function ruleFactory(string $name, array $rule): \Closure
    {
		// Reflect the class and constructor, this should only ever be done once per class and get cached
		$class = isset($rule['factory']) ? null : new \ReflectionClass($rule['class'] ?? $name);
		$constructor = $class ? $class->getConstructor() : new \ReflectionFunction($rule['factory']);

		// Create parameter generating function in order to cache reflection on the parameters. This way $reflect->getParameters() only ever gets called once
		$params = $constructor ? $this->ruleParams($constructor, $rule) : null;
		$factory = match(true) {
            null === $class => static fn(array $args, array $share) => $rule['factory'](...$params($args, $share)),
            //PHP throws a fatal error rather than an exception when trying to instantiate an interface, detect it and throw an exception instead
            $class->isInterface() => static fn() => throw new \InvalidArgumentException('Cannot instantiate interface'),
            // Get a closure based on the type of object being created: Shared, normal or constructorless
            // This class has dependencies, call the $params closure to generate them based on $args and $share
            !!$params => static fn(array $args, array $share) => new $class->name(...$params($args, $share)),
            // No constructor arguments, just instantiate the class
            default => static fn() => new $class->name,
		};

		if ($rule['shared'] ?? false) {
            $factory = function (array $args, array $share) use ($class, $name, $constructor, $params, $factory) {
                // Has its own factory or
                // Internal classes may not be able to be constructed without calling the constructor and will not suffer from cyclic references, construct them normally.
                if (null === $class || $class->isInternal()) {
                    $this->hive['RULE_INSTANCES'][$name] = $factory($args, $share);
                } else {
                    //Otherwise, create the class without calling the constructor
                    $this->hive['RULE_INSTANCES'][$name] = $class->newInstanceWithoutConstructor();

                    // Now call this constructor after constructing all the dependencies. This avoids problems with cyclic references
                    if ($constructor) {
                        $constructor->invokeArgs($this->hive['RULE_INSTANCES'][$name], $params($args, $share));
                    }
                }

                return $this->hive['RULE_INSTANCES'][$name];
            };
        }

		// If there are shared instances, create them and merge them with shared instances higher up the object graph
		if (isset($rule['shares'])) {
            $factory = fn(array $args, array $share) => $factory(
                $args,
                $this->reduce(
                    $rule['shares'],
                    function(array $share, $instance) {
                        $share[] = $this->ruled($instance, null, $share);

                        return $share;
                    },
                    $share,
                ),
            );
        }

        if (empty($rule['calls'])) {
            return $factory;
        }

		// When $rule['calls'] is set, wrap the closure in another closure which will call the required methods after constructing the object
		// By putting this in a closure, the loop is never executed unless call is actually set
        return fn(array $args, array $share) => $this->reduce(
            $rule['calls'],
            function(object $object, array|string $call, int|string $key, Looped $loop) use ($class, $name, $rule, $share) {
                if (is_numeric($key) && is_array($call) && is_string($call[0] ?? null)) {
                    $args = $call[1] ?? array();
                    $method = $call[0];
                } elseif(is_callable($call)) {
                    $args = array();
                    $method = $call;
                } else {
                    $args = (array) $call;
                    $method = $key;
                }

                if (!$method) {
                    return $object;
                }

                if ($method instanceof \Closure) {
                    return $method($object);
                }

                if (($pos = strpos($method, '#')) > 0) {
                    $method = trim(substr($method, 0, $pos));
                }

                $chain = str_starts_with($method, '>');

                if ($chain) {
                    $method = substr($method, 1);
                }

                $ref = $loop->carry['class'] ?? $class ?? new \ReflectionClass($object);

                // Generate the method arguments using getParams() and call the returned closure
                $params = $this->ruleParams(
                    $ref->getMethod($method), array(
                        'shares' => $rule['shares'] ?? array(),
                    ),
                )(($this->ruleExpand($args)), $share);

                $target = $object->{$method}(...$params);

                if ($chain) {
                    if ($rule['shared'] ?? false) {
                        $this->hive['RULE_INSTANCES'][$name] = $target;
                    }

                    if (is_object($target)) {
                        $loop->add('class', new \ReflectionClass($target));
                    }

                    return $target;
                }

                return $object;
            },
            // Construct the object using the original closure
            $factory($args, $share),
        );
	}

	/**
	 * Returns a closure that generates arguments for $method based on $rule and any $args passed into the closure
	 * @param object $method An instance of ReflectionMethod (see: {@link http:// php.net/manual/en/class.reflectionmethod.php})
	 * @param array $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
	 * @return callable A closure that uses the cached information to generate the arguments for the method
	 */
	private function ruleParams(\ReflectionFunctionAbstract $method, array $rule): \Closure
    {
        $paramsInfo = $this->ruleParamMaps($method);

		// Return a closure that uses the cached information to generate the arguments for the method
		return function (array $args, array $share) use ($paramsInfo, $rule) {
			// If the rule has construtParams set, construct any classes reference and use them as $args
			if (isset($rule['params'])) {
                $mixedArgs = array_merge(
                    $args,
                    $this->ruleExpand($rule['params'], $share),
                );
            } else {
                $mixedArgs = $args;
            }

            $subs = $rule['substitutions'] ?? array();
            $posArgs = array_filter(
                $mixedArgs,
                'is_numeric',
                ARRAY_FILTER_USE_KEY,
            );

            return static::reduceArray(
                $paramsInfo,
                function(
                    array $params,
                    array $info,
                    string $name,
                ) use (
                    &$posArgs,
                    $mixedArgs,
                    $share,
                    $subs,
                ) {
                    list($types, $defaultValue, $nullable, $variadic) = $info;

                    // first-class named args, nulls ignored
                    if (isset($mixedArgs[$name])) {
                        $params[] = $mixedArgs[$name];
                    } elseif (
                        $types
                        && $this->ruleParamMatch(
                            $types,
                            $nullable,
                            $subs,
                            $share,
                            $posArgs,
                            $match,
                        )
                    ) {
                        // match by loops
                        $params[] = $match;
                    } elseif ($posArgs) {
                        // fifo
                        $params[] = $this->ruleExpand(array_shift($posArgs));
                    } elseif ($variadic) {
                        // ends of param matching, takes all
                        $params = [...$params, ...$posArgs];
                    } else {
                        // defaults appliance
                        $params[] = $defaultValue;
                    }

                    return $params;
                },
            );
		};
	}

    private function ruleParamMaps(\ReflectionFunctionAbstract $method): array
    {
        return array_reduce(
            $method->getParameters(),
            fn($maps, \ReflectionParameter $param) => $maps + array(
                $param->name => array(
                    $param->hasType() ? $this->ruleParamTypes($param->getType()) : null,
                    $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                    $param->allowsNull(),
                    $param->isVariadic(),
                ),
            ),
            array(),
        );
    }

    private function ruleParamTypes(\ReflectionType $type): array
    {
        return match(true) {
            $type instanceof \ReflectionNamedType => array(
                $type->getName() => $type->isBuiltin(),
            ),
            $type instanceof \ReflectionIntersectionType => array(
                implode('&', array_map(
                    static fn(\ReflectionNamedType $type) => $type->getName(),
                    $type->getTypes(),
                )) => false,
            ),
            $type instanceof \ReflectionUnionType => array_reduce(
                $type->getTypes(),
                static fn (array $maps, \ReflectionNamedType $type) => $maps + array(
                    $type->getName() => $type->isBuiltin(),
                ),
                array(),
            ),
        };
    }

	/**
	* Looks through the array $search for any object which can be used to fulfil $param
	*/
    private function ruleParamMatch(
        array $types,
        bool $nullable,
        array $subs,
        array $share,
        array &$args,
        array &$value = null,
    ): bool
    {
        $match = false;
        $matchArgs = static function(array $args, bool $builtin, string $type) use ($nullable) {
            // arguments matching
            $pos = static::findIndex(
                $args,
                static fn($value) => $builtin ?
                    call_user_func('is_' . $type, $value) :
                    $value instanceof $type,
            );

            if (null === $pos && null === $args[0] && $nullable) {
                // give-up for further matching
                $pos = 0;
            }

            return $pos;
        };
        $value = $this->find(
            $types,
            function(
                bool $builtin,
                string $type,
            ) use (
                $matchArgs,
                $nullable,
                $subs,
                $share,
                &$args,
                &$match,
            ) {
                if (
                    $args
                    && ($match = null !== ($pos = $matchArgs($args, $builtin, $type)))
                ) {
                    return array_splice($args, $pos, 1)[0];
                }

                if (
                    $share
                    && ($match = null !== ($pos = $matchArgs($share, $builtin, $type)))
                ) {
                    return $share[$pos];
                }

                if ($builtin) {
                    // no attempt todo
                    return null;
                }

                $match = true;

                if (isset($subs[$type])) {
                    return $this->ruleExpand($subs[$type], $share, true);
                }

                return $nullable ? null : $this->ruled($type, array(), $share);
            },
        );

        return $match;
    }

	/**
	 * Looks for Dice::INSTANCE, Dice::GLOBAL or Dice::CONSTANT array keys in $param and when found returns an object based on the value see {@link https:// r.je/dice.html#example3-1}
	 *
     * @param mixed $param Either a string or an array,
	 * @param array $share Array of instances from 'shareInstances', required for calls to `create`
	 * @param bool $createFromString
	 *
     * @return mixed
	 */
	private function ruleExpand(
        mixed $param,
        array $share = [],
        bool $createFromString = false,
    ): mixed
    {
        return match(gettype($param)) {
            'string' => match(true) {
                $this->ruleExpandable($param) => $this->ruleExpandExpression($param),
                $createFromString => $this->ruled($param),
                default => str_replace('\\{', '{', $param),
            },
            'array' => array_map(
                fn(mixed $param) => $this->ruleExpand($param, $share),
                $param,
            ),
            default => $param,
        };
	}

    private function ruleExpandable(string $param): bool
    {
        return (
            ($open = strpos($param, '{'))
            && (
                // first char
                $open < 1
                // non-escape
                || '\\' != $param[$open - 1]
            )
            && false === strpos($param, '}')
        );
    }

    private function ruleExpandExpression(string $param): string
    {
        return preg_replace_callback(
            '/{([^}]+)}/',
            fn(array $matches) => $this->get($matches[1]),
            $param,
        );
    }
}
