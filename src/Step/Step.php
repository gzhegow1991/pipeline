<?php

namespace Gzhegow\Pipeline\Step;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Chain\ChainInterface;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;


class Step
{
    /**
     * @var PipelineProcessInterface
     */
    public $process;

    /**
     * @var Pipe<ChainInterface|GenericHandler>
     */
    public $pipe;
}
