<?php

namespace Gzhegow\Pipeline;


use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


interface PipelineFactoryInterface
{
    /**
     * @param PipelineProcessManagerInterface|null $processManager
     *
     * @return PipelineFacadeInterface
     */
    public function makeFacade(
        $processManager = null
    ) : object;


    /**
     * @param PipelineProcessorInterface $processor
     *
     * @return PipelineProcessManagerInterface
     */
    public function makeProcessManager(
        $processor = null
    ) : object;

    /**
     * @return PipelineProcessorInterface
     */
    public function makeProcessor() : object;


    public function newPipeline() : PipelineChain;

    public function newMiddleware($from) : MiddlewareChain;


    public function newMiddlewareProcess(
        PipelineProcessManagerInterface $processManager,
        //
        MiddlewareChain $middleware
    ) : ?MiddlewareProcess;

    public function newPipelineProcess(
        PipelineProcessManagerInterface $processManager,
        //
        PipelineChain $pipeline
    ) : ?PipelineProcess;


    public function newProcessFrom(PipelineProcessManagerInterface $processManager, $from) : ?PipelineProcessInterface;

    public function newProcessFromInstance($from) : ?PipelineProcessInterface;

    public function newProcessFromMiddleware(PipelineProcessManagerInterface $processManager, $from) : ?MiddlewareProcess;

    public function newProcessFromPipeline(PipelineProcessManagerInterface $processManager, $pipeline) : ?PipelineProcess;


    public function newHandlerObject(string $class, array $parameters = []) : object;
}
