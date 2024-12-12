<?php

namespace Gzhegow\Pipeline\Processor;

use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


interface ProcessorInterface
{
    /**
     * @return array{ 0?: mixed }
     */
    public function callMiddleware(
        GenericHandlerMiddleware $middleware,
        PipelineProcessInterface $process, $input = null, $context = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callAction(
        GenericHandlerAction $action,
        PipelineProcessInterface $process,
        $input = null, $context = null
    ) : array;

    /**
     * @return array{ 0?: mixed }
     */
    public function callFallback(
        GenericHandlerFallback $fallback,
        PipelineProcessInterface $process,
        \Throwable $throwable, $input = null, $context = null
    ) : array;
}
