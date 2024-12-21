<?php

namespace Gzhegow\Pipeline\Step;

use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;


class PipelineStep
{
    /**
     * @var PipelineProcessInterface
     */
    public $process;

    /**
     * @var PipelinePipe<PipelineChainInterface|GenericHandler>
     */
    public $pipe;
}
