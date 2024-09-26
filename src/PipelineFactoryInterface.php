<?php

namespace Gzhegow\Pipeline;

interface PipelineFactoryInterface
{
    public function newPipeline() : PipelineInterface;

    public function newPipelineProcessor() : PipelineProcessorInterface;
}
