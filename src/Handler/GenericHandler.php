<?php

namespace Gzhegow\Pipeline\Handler;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Exception\LogicException;


abstract class GenericHandler implements \Serializable
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var \Closure
     */
    public $closure;

    /**
     * @var array{
     *     0: object|class-string,
     *     1: string
     * }
     */
    public $method;
    /**
     * @var class-string
     */
    public $methodClass;
    /**
     * @var object
     */
    public $methodObject;
    /**
     * @var string
     */
    public $methodName;

    /**
     * @var callable|object|class-string
     */
    public $invokable;
    /**
     * @var callable|object
     */
    public $invokableObject;
    /**
     * @var class-string
     */
    public $invokableClass;

    /**
     * @var callable|string
     */
    public $function;


    /**
     * @return static
     */
    public static function from($from) // : static
    {
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php_errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromClosure($from)
            ?? static::tryFromMethod($from)
            ?? static::tryFromInvokable($from)
            ?? static::tryFromFunction($from);

        $errors = Lib::php_errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, null, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    protected static function tryFromInstance($instance) // : ?static
    {
        if (! is_a($instance, static::class)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromClosure($closure) // : ?static
    {
        if (! is_a($closure, \Closure::class)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . \Closure::class, $closure ]
            );
        }

        $instance = new static();
        $instance->closure = $closure;

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromMethod($method) // : ?static
    {
        if (! Lib::php_method_exists($method, null, $methodArray)) {
            return Lib::php_error(
                [ 'The `from` should be existing method', $method ]
            );
        }

        $instance = new static();

        $instance->method = $methodArray;
        $instance->methodName = $methodArray[ 1 ];

        $isObject = is_object($methodArray[ 0 ]);

        if ($isObject) {
            $instance->methodObject = $methodArray[ 0 ];

        } else {
            $instance->methodClass = $methodArray[ 0 ];
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromInvokable($invokable) // : ?static
    {
        $instance = null;

        if (is_object($invokable)) {
            if (! is_callable($invokable)) {
                return null;
            }

            $instance = new static();
            $instance->invokable = $invokable;
            $instance->invokableObject = $invokable;

        } elseif (null !== ($_invokableClass = Lib::parse_string_not_empty($invokable))) {
            if (! class_exists($_invokableClass)) {
                return null;
            }

            if (! method_exists($_invokableClass, '__invoke')) {
                return null;
            }

            $instance = new static();
            $instance->invokable = $_invokableClass;
            $instance->invokableClass = $_invokableClass;
        }

        if (null === $instance) {
            return Lib::php_error(
                [ 'The `from` should be existing invokable class or object', $invokable ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromFunction($function) // : ?static
    {
        if (null === ($_function = Lib::parse_string_not_empty($function))) {
            return Lib::php_error(
                [ 'The `from` should be existing function name', $function ]
            );
        }

        if (! function_exists($_function)) {
            return Lib::php_error(
                [ 'The `from` should be existing function name', $_function ]
            );
        }

        $instance = new static();
        $instance->function = $_function;

        return $instance;
    }


    private function __construct()
    {
    }


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        return array_filter($vars);
    }

    public function __unserialize(array $data) : void
    {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }


    public function getKey() : string
    {
        if (! isset($this->key)) {
            $key = null
                ?? $this->closure
                ?? $this->method
                ?? $this->invokable
                ?? $this->function;

            $key = Lib::debug_var_dump($key);

            $this->key = $key;
        }

        return $this->key;
    }
}
