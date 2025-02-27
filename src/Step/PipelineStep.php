<?php

namespace Gzhegow\Pipeline\Step;

use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;


class PipelineStep
{
    /**
     * @var PipelinePipe<PipelineChainInterface|GenericHandler>
     */
    protected $pipe;

    /**
     * @var PipelineProcessInterface
     */
    protected $process;


    public function __construct(
        PipelineProcessInterface $process,
        PipelinePipe $pipe
    )
    {
        $this->process = $process;
        $this->pipe = $pipe;
    }


    public function getPipe() : PipelinePipe
    {
        return $this->pipe;
    }

    public function getProcess() : PipelineProcessInterface
    {
        return $this->process;
    }
}
