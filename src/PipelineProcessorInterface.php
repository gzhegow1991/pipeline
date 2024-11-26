<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;


interface PipelineProcessorInterface
{
    /**
     * @return array{ 0?: mixed }
     */
    public function callMiddleware(
        GenericMiddleware $middleware,
        Pipeline $pipeline, $input = null, $context = null, $inputOriginal = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callAction(
        GenericAction $action,
        Pipeline $pipeline, $input = null, $context = null, $inputOriginal = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callFallback(
        GenericFallback $fallback,
        Pipeline $pipeline, \Throwable $throwable, $input = null, $context = null, $inputOriginal = null
    ) : array;
}
