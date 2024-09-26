<?php

namespace Gzhegow\Pipeline;


class PipelineFactory implements PipelineFactoryInterface
{
    public function newPipeline() : PipelineInterface
    {
        $processor = $this->newPipelineProcessor();

        return new Pipeline(
            $processor
        );
    }


    public function newPipelineProcessor() : PipelineProcessorInterface
    {
        return new PipelineProcessor($this);
    }
}
