<?php

namespace Gzhegow\Pipeline\Core\Process;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Step\PipelineStep;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManagerInterface;


class MiddlewareProcess extends AbstractProcess
{
    /**
     * @var bool
     */
    protected $isNextCalled = false;

    /**
     * @var PipelinePipe
     */
    protected $pipeMain;

    /**
     * @var PipelinePipe[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];

    /**
     * @var MiddlewareChain
     */
    protected $middleware;

    /**
     * @var PipelineProcessInterface
     */
    protected $childProcess;


    public function __construct(
        PipelineFactoryInterface $factory,
        PipelineProcessManagerInterface $processManager,
        //
        MiddlewareChain $middleware
    )
    {
        $this->middleware = $middleware;

        parent::__construct($factory, $processManager);
    }


    public function isFinished() : bool
    {
        return (true
            && (null === $this->pipeMain)
            && (false
                || (! $this->isNextCalled)
                || parent::isFinished()
            )
        );
    }


    public function getNextStep() : ?PipelineStep
    {
        if ($this->pipeMain) {
            $pipe = $this->pipeMain;

            $step = new PipelineStep($this, $pipe);

            $this->pipeMain = null;

            return $step;
        }

        if ($this->isNextCalled) {
            $step = parent::getNextStep();

            return $step;
        }

        return null;
    }


    public function reset() : void
    {
        parent::reset();

        $this->isNextCalled = false;

        $this->pipeMain = $this->middleware->getPipe();

        foreach ( $this->middleware->getPipes() as $i => $pipe ) {
            $this->pipes[ $i ] = $pipe;
        }

        foreach ( $this->middleware->getThrowables() as $i => $throwable ) {
            $this->throwables[ $i ] = $throwable;
        }

        reset($this->pipes);
        reset($this->throwables);
    }


    public function next($input = null, $context = null)
    {
        $this->isNextCalled = true;

        $result = $this->processManager->next($this, $input, $context);

        return $result;
    }
}
