<?php

namespace Gzhegow\Pipeline\Process;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\PipelineProcessManager;


class PipelineProcess extends AbstractProcess
{
    /**
     * @var PipelineChain
     */
    protected $pipeline;


    public function __construct(PipelineProcessManager $pm, PipelineChain $pipeline)
    {
        $this->pipeline = $pipeline;

        parent::__construct($pm);
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
