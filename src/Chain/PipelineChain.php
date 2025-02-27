<?php

namespace Gzhegow\Pipeline\Chain;

use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Handler\GenericHandler;


class PipelineChain extends AbstractPipelineChain
{
    /**
     * @var PipelineChainInterface
     */
    protected $parent;

    /**
     * @var PipelinePipe<PipelineChainInterface|GenericHandler>[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];
}
