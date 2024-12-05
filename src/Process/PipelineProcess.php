<?php

namespace Gzhegow\Pipeline\Process;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\PipelineProcessManagerInterface;


class PipelineProcess extends AbstractProcess
{
    /**
     * @var PipelineChain
     */
    protected $pipeline;


    public function __construct(
        PipelineProcessManagerInterface $processManager,
        PipelineChain $pipeline
    )
    {
        $this->pipeline = $pipeline;

        parent::__construct($processManager);
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
