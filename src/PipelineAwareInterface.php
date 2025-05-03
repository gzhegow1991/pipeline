<?php

namespace Gzhegow\Pipeline;


interface PipelineAwareInterface
{
    /**
     * @param null|PipelineInterface $pipeline
     *
     * @return void
     */
    public function setPipeline(?PipelineInterface $pipeline) : void;
}
