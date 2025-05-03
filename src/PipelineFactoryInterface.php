<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Core\Process\PipelineProcess;
use Gzhegow\Pipeline\Core\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Core\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Core\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManagerInterface;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain as MiddlewareChain;


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
