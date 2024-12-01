<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;


interface PipelineProcessManagerInterface
{
    public function newMiddlewareProcess(MiddlewareChain $middleware) : ?MiddlewareProcess;

    public function newPipelineProcess(PipelineChain $pipeline) : ?PipelineProcess;


    public function newProcessFrom($from) : ?PipelineProcessInterface;


    public function run($pipeline, $input = null, $context = null);

    public function next(PipelineProcessInterface $process, $input = null, $context = null);
}
