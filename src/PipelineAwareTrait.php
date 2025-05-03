<?php

namespace Gzhegow\Pipeline;


trait PipelineAwareTrait
{
    /**
     * @var PipelineInterface
     */
    protected $pipeline;


    /**
     * @param null|PipelineInterface $pipeline
     *
     * @return void
     */
    public function setPipeline(?PipelineInterface $pipeline) : void
    {
        $this->pipeline = $pipeline;
    }
}
