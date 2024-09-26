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
        Pipeline $pipeline, $result = null, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($middleware);

        $result = $this->callUserFuncArray(
            $callable,
            [
                0            => $pipeline,
                1            => $result,
                2            => $input,
                3            => $context,
                //
                'middleware' => $middleware,
                'pipeline'   => $pipeline,
                'result'     => $result,
                'input'      => $input,
                'context'    => $context,
            ]
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
        $result = null, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractActionCallable($action);

        if ($action->pipeline) {
            $args = [
                0 => $input,
                1 => $context,
                2 => $result,
            ];

        } else {
            $args = [
                0 => $result,
                1 => $input,
                2 => $context,
            ];
        }

        $args[ 'action' ] = $action;
        $args[ 'pipeline' ] = $pipeline;
        $args[ 'result' ] = $result;
        $args[ 'input' ] = $input;
        $args[ 'context' ] = $context;

        $result = $this->callUserFuncArray(
            $callable,
            $args
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
        \Throwable $throwable, $result = null, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($fallback);

        $result = $this->callUserFuncArray(
            $callable,
            [
                0           => $throwable,
                1           => $result,
                2           => $input,
                3           => $context,
                //
                'fallback'  => $fallback,
                'pipeline'  => $pipeline,
                'throwable' => $throwable,
                'result'    => $result,
                'input'     => $input,
                'context'   => $context,
            ]
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }


    /**
     * @return callable
     */
    protected function extractActionCallable(GenericAction $action)
    {
        $fn = null;

        if ($action->pipeline) {
            $fn = [ $action->pipeline, 'run' ];

        } else {
            $fn = $this->extractHandlerCallable($action);
        }

        if (! is_callable($fn)) {
            throw new RuntimeException(
                'Unable to extract callable from action. '
                . 'Handler: ' . Lib::php_dump($action)
            );
        }

        return $fn;
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
                . 'Handler: ' . Lib::php_dump($handler)
            );
        }

        return $fn;
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
}
