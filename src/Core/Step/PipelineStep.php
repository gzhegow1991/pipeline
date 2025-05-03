<?php

namespace Gzhegow\Pipeline\Core\Step;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Handler\GenericHandler;
use Gzhegow\Pipeline\Core\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Core\Process\PipelineProcessInterface;


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
