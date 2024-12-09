<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


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
     * @return array{ 0?: mixed }
     */
    public function callMiddleware(
        GenericHandlerMiddleware $middleware,
        PipelineProcessInterface $process, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($middleware);

        $processState = $process->getState();

        $callableArgs = [
            0 => $process,
            1 => $input,
            2 => $context,
            3 => $processState,
        ];
        $callableArgs += [
            'middleware' => $middleware,
            'process'    => $process,
            'input'      => $input,
            'context'    => $context,
            'state'      => $processState,
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
        GenericHandlerAction $action,
        PipelineProcessInterface $process,
        $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($action);

        $processState = $process->getState();

        $callableArgs = [
            0 => $input,
            1 => $context,
            2 => $processState,
        ];
        $callableArgs += [
            'action'  => $action,
            'process' => $process,
            'input'   => $input,
            'context' => $context,
            'state'   => $processState,
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
        GenericHandlerFallback $fallback,
        PipelineProcessInterface $process,
        \Throwable $throwable, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($fallback);

        $processState = $process->getState();

        $callableArgs = [
            0 => $throwable,
            1 => $input,
            2 => $context,
            3 => $processState,
        ];
        $callableArgs += [
            'fallback'  => $fallback,
            'process'   => $process,
            'throwable' => $throwable,
            'input'     => $input,
            'context'   => $context,
            'state'     => $processState,
        ];

        $result = $this->callUserFuncArray(
            $callable,
            $callableArgs
        );

        return (null !== $result)
            ? [ $result ]
            : [];
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
                ?? $this->factory->newHandlerObject($handler->methodClass);

            $method = $handler->methodName;

            $fn = [ $object, $method ];

        } elseif ($handler->invokable) {
            $object = null
                ?? $handler->invokableObject
                ?? $this->factory->newHandlerObject($handler->invokableClass);

            $fn = $object;

        } elseif ($handler->function) {
            $fn = $handler->function;
        }

        if (! is_callable($fn)) {
            throw new RuntimeException(
                [
                    'Unable to extract callable from handler.'
                    . ' / Handler: ' . Lib::debug_var_dump($handler),
                    $handler,
                ]
            );
        }

        return $fn;
    }
}
