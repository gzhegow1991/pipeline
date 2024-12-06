<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


interface PipelineFactoryInterface
{
    public function newProcessor() : PipelineProcessorInterface;

    public function newProcessManager() : PipelineProcessManagerInterface;

    public function newFacade() : PipelineFacadeInterface;


    public function newPipeline() : PipelineChain;

    public function newMiddleware($from) : MiddlewareChain;


    public function newHandlerObject(string $class, array $parameters = []) : object;
}
