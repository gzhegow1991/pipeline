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
    protected $functionString;


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

        Lib::php()->errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromClosure($from)
            ?? static::tryFromMethod($from)
            ?? static::tryFromInvokable($from)
            ?? static::tryFromFunction($from);

        $errors = Lib::php()->errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, $last);
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
            return Lib::php()->error(
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
            return Lib::php()->error(
                [ 'The `from` should be instance of: ' . \Closure::class, $closure ]
            );
        }

        $instance = new static();
        $instance->isClosure = true;
        $instance->closureObject = $closure;

        $phpId = spl_object_id($closure);

        $instance->key = "{ object # \Closure # {$phpId} }";

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromMethod($method) // : ?static
    {
        $thePhp = Lib::php();

        if (! $thePhp->type_method_string($methodString, $method, [ &$methodArray ])) {
            return $thePhp->error(
                [ 'The `from` should be existing method', $method ]
            );
        }

        [ $objectOrClass, $methodName ] = $methodArray;

        $instance = new static();

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

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function tryFromInvokable($invokable) // : ?static
    {
        $instance = null;

        if (is_object($invokable)) {
            if (! method_exists($invokable, '__invoke')) {
                return null;
            }

            $instance = new static();
            $instance->isInvokable = true;
            $instance->invokableObject = $invokable;

            $phpClass = get_class($invokable);
            $phpId = spl_object_id($invokable);

            $instance->key = "\"{ object # {$phpClass} # {$phpId} }\"";

        } else {
            $_invokableClass = Lib::parse()->string_not_empty($invokable);

            if (null !== $_invokableClass) {
                if (! class_exists($_invokableClass)) {
                    return null;
                }

                if (! method_exists($_invokableClass, '__invoke')) {
                    return null;
                }

                $instance = new static();
                $instance->isInvokable = true;
                $instance->invokableClass = $_invokableClass;

                $instance->key = "\"{$_invokableClass}\"";
            }
        }

        if (null === $instance) {
            return Lib::php()->error(
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
        if (null === ($_function = Lib::parse()->string_not_empty($function))) {
            return Lib::php()->error(
                [ 'The `from` should be existing function name', $function ]
            );
        }

        if (! function_exists($_function)) {
            return Lib::php()->error(
                [ 'The `from` should be existing function name', $_function ]
            );
        }

        $instance = new static();
        $instance->isFunction = true;
        $instance->functionString = $_function;

        $instance->key = "\"{$_function}\"";

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
        return $this->key;
    }


    public function isClosure() : bool
    {
        return $this->isClosure;
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
     * @return callable|string
     */
    public function getFunctionString() : string
    {
        return $this->functionString;
    }
}
