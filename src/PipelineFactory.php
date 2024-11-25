<?php

namespace Gzhegow\Pipeline;


class PipelineFactory implements PipelineFactoryInterface
{
    public function newPipeline() : PipelineInterface
    {
        $processor = $this->newPipelineProcessor();

        $pipeline = new Pipeline($processor);

        return $pipeline;
    }


    public function newPipelineProcessor() : PipelineProcessorInterface
    {
        $pipelineProcessor = new PipelineProcessor($this);

        return $pipelineProcessor;
    }
}
