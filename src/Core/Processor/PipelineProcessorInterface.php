<?php

namespace Gzhegow\Pipeline\Core\Processor;

use Gzhegow\Pipeline\Core\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Core\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Core\Handler\Middleware\GenericHandlerMiddleware;


interface PipelineProcessorInterface
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
