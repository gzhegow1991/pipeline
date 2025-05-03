<?php

namespace Gzhegow\Pipeline\Core\Process;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Chain\PipelineChain;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManagerInterface;


class PipelineProcess extends AbstractProcess
{
    /**
     * @var PipelinePipe[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];

    /**
     * @var PipelineChain
     */
    protected $pipeline;

    /**
     * @var PipelineProcessInterface
     */
    protected $childProcess;


    public function __construct(
        PipelineFactoryInterface $factory,
        PipelineProcessManagerInterface $processManager,
        //
        PipelineChain $pipeline
    )
    {
        $this->pipeline = $pipeline;

        parent::__construct($factory, $processManager);
    }


    public function reset() : void
    {
        parent::reset();

        foreach ( $this->pipeline->getPipes() as $i => $pipe ) {
            $this->pipes[ $i ] = $pipe;
        }

        foreach ( $this->pipeline->getThrowables() as $i => $throwable ) {
            $this->throwables[ $i ] = $throwable;
        }

        reset($this->pipes);
        reset($this->throwables);
    }
}
