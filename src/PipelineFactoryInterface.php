<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\ProcessManager\ProcessManagerInterface;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


interface PipelineFactoryInterface
{
    public function newPipeline() : PipelineChain;

    public function newMiddleware($from) : MiddlewareChain;


    public function newMiddlewareProcess(
        ProcessManagerInterface $processManager,
        //
        MiddlewareChain $middleware
    ) : ?MiddlewareProcess;

    public function newPipelineProcess(
        ProcessManagerInterface $processManager,
        //
        PipelineChain $pipeline
    ) : ?PipelineProcess;


    public function newProcessFrom(ProcessManagerInterface $processManager, $from) : ?PipelineProcessInterface;

    public function newProcessFromMiddleware(ProcessManagerInterface $processManager, $middleware) : ?MiddlewareProcess;

    public function newProcessFromPipeline(ProcessManagerInterface $processManager, $pipeline) : ?PipelineProcess;


    public function newHandlerObject(string $class, array $parameters = []) : object;
}
