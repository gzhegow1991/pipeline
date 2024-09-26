<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;


interface PipelineProcessorInterface
{
    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object;


    /**
     * @return array{ 0?: mixed }
     */
    public function callMiddleware(
        GenericMiddleware $middleware,
        Pipeline $pipeline, $result = null, $input = null, $context = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callAction(
        GenericAction $action,
        Pipeline $pipeline, $result = null, $input = null, $context = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callFallback(
        GenericFallback $fallback,
        Pipeline $pipeline, \Throwable $throwable, $result = null, $input = null, $context = null
    ) : array;
}
