<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;


class PipelineProcessor implements PipelineProcessorInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;


    public function __construct(PipelineFactoryInterface $factory)
    {
        $this->factory = $factory;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        [ $list ] = Lib::array_kwargs($parameters);

        $handler = new $class(...$list);

        return $handler;
    }


    /**
     * @return array{ 0?: mixed }
     */
    public function callMiddleware(
        GenericMiddleware $middleware,
        Pipeline $pipeline, $input = null, $context = null, $inputOriginal = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($middleware);

        $callableArgs = [
            0 => $pipeline,
            1 => $input,
            2 => $context,
            3 => $inputOriginal,
        ];
        $callableArgs += [
            'middleware'    => $middleware,
            'pipeline'      => $pipeline,
            'input'         => $input,
            'context'       => $context,
            'inputOriginal' => $inputOriginal,
        ];

        $result = $this->callUserFuncArray(
            $callable,
            $callableArgs
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }

    /**
     * @return array{ 0?: mixed }
     */
    public function callAction(
        GenericAction $action,
        Pipeline $pipeline,
        $input = null, $context = null, $inputOriginal = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($action);

        $callableArgs = [
            0 => $input,
            1 => $context,
            2 => $inputOriginal,
        ];
        $callableArgs += [
            'action'        => $action,
            'pipeline'      => $pipeline,
            'input'         => $input,
            'context'       => $context,
            'inputOriginal' => $inputOriginal,
        ];

        $result = $this->callUserFuncArray(
            $callable,
            $callableArgs
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }

    /**
     * @return array{ 0?: mixed }
     */
    public function callFallback(
        GenericFallback $fallback,
        Pipeline $pipeline,
        \Throwable $throwable, $input = null, $context = null, $inputOriginal = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($fallback);

        $callableArgs = [
            0 => $throwable,
            1 => $input,
            2 => $context,
            3 => $inputOriginal,
        ];
        $callableArgs += [
            'fallback'      => $fallback,
            'pipeline'      => $pipeline,
            'throwable'     => $throwable,
            'input'         => $input,
            'context'       => $context,
            'inputOriginal' => $inputOriginal,
        ];

        $result = $this->callUserFuncArray(
            $callable,
            $callableArgs
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }


    protected function callUserFunc($fn, ...$args) // : mixed
    {
        $result = call_user_func($fn, ...$args);

        return $result;
    }

    protected function callUserFuncArray($fn, array $args) // : mixed
    {
        [ $list ] = Lib::array_kwargs($args);

        $result = call_user_func_array($fn, $list);

        return $result;
    }


    /**
     * @return callable
     */
    protected function extractHandlerCallable(GenericHandler $handler)
    {
        $fn = null;

        if ($handler->closure) {
            $fn = $handler->closure;

        } elseif ($handler->method) {
            $object = null
                ?? $handler->methodObject
                ?? $this->newHandlerObject($handler->methodClass);

            $method = $handler->methodName;

            $fn = [ $object, $method ];

        } elseif ($handler->invokable) {
            $object = null
                ?? $handler->invokableObject
                ?? $this->newHandlerObject($handler->invokableClass);

            $fn = $object;

        } elseif ($handler->function) {
            $fn = $handler->function;
        }

        if (! is_callable($fn)) {
            throw new RuntimeException(
                'Unable to extract callable from handler. '
                . 'Handler: ' . Lib::php_var_dump($handler)
            );
        }

        return $fn;
    }
}
