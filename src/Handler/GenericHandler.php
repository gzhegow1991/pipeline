<?php

namespace Gzhegow\Pipeline\Handler;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Exception\LogicException;


abstract class GenericHandler implements \Serializable
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var bool
     */
    protected $isClosure = false;
    /**
     * @var \Closure
     */
    protected $closureObject;

    /**
     * @var bool
     */
    protected $isMethod = false;
    /**
     * @var class-string
     */
    protected $methodClass;
    /**
     * @var object
     */
    protected $methodObject;
    /**
     * @var string
     */
    protected $methodName;

    /**
     * @var bool
     */
    protected $isInvokable = false;
    /**
     * @var callable|object
     */
    protected $invokableObject;
    /**
     * @var class-string
     */
    protected $invokableClass;

    /**
     * @var bool
     */
    protected $isFunction = false;
    /**
     * @var callable|string
     */
    protected $functionStringInternal;
    /**
     * @var callable|string
     */
    protected $functionStringNonInternal;


    private function __construct()
    {
    }


    /**
     * @return static|bool|null
     */
    public static function from($from, array $context = [], array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromInstance($from, $refs)
            ?? static::fromFunction($from, $context, $refs)
            ?? static::fromMethod($from, $context, $refs)
            ?? static::fromClosure($from, $context, $refs)
            ?? static::fromInvokableObject($from, $context, $refs)
            ?? static::fromInvokableClass($from, $context, $refs);

        if (! $withErrors) {
            if (null === $instance) {
                throw $refs[ 0 ];
            }
        }

        return $instance;
    }

    /**
     * @return static|bool|null
     */
    public static function fromInstance($from, array $refs = [])
    {
        if ($from instanceof static) {
            return Lib::refsResult($refs, $from);
        }

        return Lib::refsError(
            $refs,
            new LogicException(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            )
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromClosure($from, array $context = [], array $refs = [])
    {
        if (! ($from instanceof \Closure)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of \Closure', $from ]
                )
            );
        }

        $arguments = $context[ 'arguments' ] ?? [];

        $instance = new static();
        $instance->args = $arguments;

        $instance->isClosure = true;
        $instance->closureObject = $from;

        $phpId = spl_object_id($from);

        $instance->key = "{ object # \Closure # {$phpId} }";

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromMethod($from, array $context = [], array $refs = [])
    {
        if (! Lib::php()->type_method_string($methodString, $from, [ &$methodArray ])) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be existing method', $from ]
                )
            );
        }

        $arguments = $context[ 'arguments' ] ?? [];

        [ $objectOrClass, $methodName ] = $methodArray;

        $instance = new static();
        $instance->args = $arguments;

        $instance->isMethod = true;

        if (is_object($objectOrClass)) {
            $object = $objectOrClass;

            $phpClass = get_class($object);
            $phpId = spl_object_id($object);

            $key0 = "\"{ object # {$phpClass} # {$phpId} }\"";

            $instance->methodObject = $object;

        } else {
            $objectClass = $objectOrClass;

            $key0 = '"' . $objectClass . '"';

            $instance->methodClass = $objectClass;
        }

        $key1 = "\"{$methodName}\"";

        $instance->methodName = $methodName;

        $instance->key = "[ {$key0}, {$key1} ]";

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromInvokableObject($from, array $context = [], array $refs = [])
    {
        if (! is_object($from)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be object', $from ]
                )
            );
        }

        if (! method_exists($from, '__invoke')) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be invokable object', $from ]
                )
            );
        }

        $arguments = $context[ 'arguments' ] ?? [];

        $instance = new static();
        $instance->args = $arguments;

        $instance->isInvokable = true;
        $instance->invokableObject = $from;

        $phpClass = get_class($from);
        $phpId = spl_object_id($from);

        $instance->key = "\"{ object # {$phpClass} # {$phpId} }\"";

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromInvokableClass($from, array $context = [], array $refs = [])
    {
        if (! Lib::type()->string_not_empty($_invokableClass, $from)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be non-empty string', $from ]
                )
            );
        }

        if (! class_exists($_invokableClass)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be existing class', $from ]
                )
            );
        }

        if (! method_exists($_invokableClass, '__invoke')) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be invokable class', $from ]
                )
            );
        }

        $arguments = $context[ 'arguments' ] ?? [];

        $instance = new static();
        $instance->args = $arguments;

        $instance->isInvokable = true;
        $instance->invokableClass = $_invokableClass;

        $instance->key = "\"{$_invokableClass}\"";

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromFunction($function, array $context = [], array $refs = [])
    {
        $thePhp = Lib::php();

        if (! Lib::type()->string_not_empty($_function, $function)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be existing function name', $function ]
                )
            );
        }

        if (! function_exists($_function)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be existing function name', $_function ]
                )
            );
        }

        $arguments = $context[ 'arguments' ] ?? [];

        $instance = new static();
        $instance->args = $arguments;

        $instance->isFunction = true;

        $isInternal = $thePhp->type_callable_string_function_internal($_functionInternal, $_function);

        if ($isInternal) {
            $instance->functionStringInternal = $_function;

        } else {
            $instance->functionStringNonInternal = $_function;
        }

        $instance->key = "\"{$_function}\"";

        return Lib::refsResult($refs, $instance);
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
        return $this->key;
    }


    public function getArgs() : array
    {
        return $this->args;
    }


    public function isClosure() : bool
    {
        return $this->isClosure;
    }

    public function hasClosureObject() : ?\Closure
    {
        return $this->closureObject;
    }

    public function getClosureObject() : \Closure
    {
        return $this->closureObject;
    }



    public function isMethod() : bool
    {
        return $this->isMethod;
    }

    /**
     * @return \class-string|null
     */
    public function hasMethodClass() : ?string
    {
        return $this->methodClass;
    }

    /**
     * @return \class-string
     */
    public function getMethodClass() : string
    {
        return $this->methodClass;
    }


    public function hasMethodObject() : ?object
    {
        return $this->methodObject;
    }

    public function getMethodObject() : object
    {
        return $this->methodObject;
    }


    public function hasMethodName() : ?string
    {
        return $this->methodName;
    }

    public function getMethodName() : string
    {
        return $this->methodName;
    }


    public function isInvokable() : bool
    {
        return $this->isInvokable;
    }

    /**
     * @return callable|object|null
     */
    public function hasInvokableObject() : ?object
    {
        return $this->invokableObject;
    }

    /**
     * @return callable|object
     */
    public function getInvokableObject() : object
    {
        return $this->invokableObject;
    }

    /**
     * @return callable|object|null
     */
    public function hasInvokableClass() : ?string
    {
        return $this->invokableObject;
    }

    /**
     * @return \class-string
     */
    public function getInvokableClass() : string
    {
        return $this->invokableClass;
    }


    public function isFunction() : bool
    {
        return $this->isFunction;
    }


    /**
     * @return callable|string|null
     */
    public function hasFunctionStringInternal() : ?string
    {
        return $this->functionStringInternal;
    }

    /**
     * @return callable|string
     */
    public function getFunctionStringInternal() : string
    {
        return $this->functionStringInternal;
    }


    /**
     * @return callable|string|null
     */
    public function hasFunctionStringNonInternal() : ?string
    {
        return $this->functionStringNonInternal;
    }

    /**
     * @return callable|string
     */
    public function getFunctionStringNonInternal() : string
    {
        return $this->functionStringNonInternal;
    }
}
