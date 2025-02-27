<?php

namespace Gzhegow\Pipeline\Processor;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\PipelineFactoryInterface;
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

        $callableArgs = [
            0 => $process,
            1 => $input,
            2 => $context,
        ];
        $callableArgs += [
            'middleware' => $middleware,
            'process'    => $process,
            'input'      => $input,
            'context'    => $context,
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

        $callableArgs = [
            0 => $input,
            1 => $context,
        ];
        $callableArgs += [
            'action'  => $action,
            'process' => $process,
            'input'   => $input,
            'context' => $context,
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

        $callableArgs = [
            0 => $throwable,
            1 => $input,
            2 => $context,
        ];
        $callableArgs += [
            'fallback'  => $fallback,
            'process'   => $process,
            'throwable' => $throwable,
            'input'     => $input,
            'context'   => $context,
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
        [ $list ] = Lib::arr()->kwargs($args);

        $result = call_user_func_array($fn, $list);

        return $result;
    }


    /**
     * @return callable
     */
    protected function extractHandlerCallable(GenericHandler $handler)
    {
        $fn = null;

        if ($handler->isClosure()) {
            $fn = $handler->getClosureObject();

        } elseif ($handler->isMethod()) {
            $object = null
                ?? ($handler->hasMethodObject() ? $handler->getMethodObject() : null)
                ?? $this->factory->newHandlerObject($handler->getMethodClass());

            $method = $handler->getMethodName();

            $fn = [ $object, $method ];

        } elseif ($handler->isInvokable()) {
            $object = null
                ?? ($handler->hasInvokableObject() ? $handler->getInvokableObject() : null)
                ?? ($this->factory->newHandlerObject($handler->getInvokableClass()));

            $fn = $object;

        } elseif ($handler->isFunction()) {
            $fn = $handler->getFunctionString();
        }

        if (! is_callable($fn)) {
            throw new RuntimeException(
                [
                    'Unable to extract callable from handler.'
                    . ' / Handler: ' . Lib::debug()->var_dump($handler),
                    $handler,
                ]
            );
        }

        return $fn;
    }
}
