<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\ProcessManager\PipelineProcessManagerInterface;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


interface PipelineFactoryInterface
{
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

    public function newProcessFromMiddleware(PipelineProcessManagerInterface $processManager, $middleware) : ?MiddlewareProcess;

    public function newProcessFromPipeline(PipelineProcessManagerInterface $processManager, $pipeline) : ?PipelineProcess;


    public function newHandlerObject(string $class, array $parameters = []) : object;
}
