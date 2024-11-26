<?php

namespace Gzhegow\Pipeline;

interface PipelineFactoryInterface
{
    public function newPipeline(PipelineProcessorInterface $processor = null) : PipelineInterface;

    public function newPipelineProcessor() : PipelineProcessorInterface;

    public function newHandlerObject(string $class, array $parameters = []) : object;
}
