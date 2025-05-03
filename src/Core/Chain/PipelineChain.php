<?php

namespace Gzhegow\Pipeline\Core\Chain;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Handler\GenericHandler;


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
