<?php

namespace Gzhegow\Pipeline\Process;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Step\Step;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\PipelineProcessManagerInterface;


class MiddlewareProcess extends AbstractProcess
{
    /**
     * @var MiddlewareChain
     */
    protected $middleware;

    /**
     * @var Pipe
     */
    protected $pipe;

    /**
     * @var bool
     */
    protected $isNextCalled = false;


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
            && (null === $this->pipe)
            && (false
                || (! $this->isNextCalled)
                || parent::isFinished()
            )
        );
    }


    public function getNextStep() : ?Step
    {
        if ($pipe = $this->pipe) {
            $step = new Step();
            $step->process = $this;
            $step->pipe = $pipe;

            $this->pipe = null;

            return $step;
        }

        if (! $this->isNextCalled) {
            return null;
        }

        $step = parent::getNextStep();

        return $step;
    }


    public function reset() : void
    {
        parent::reset();

        $this->isNextCalled = false;

        $this->pipe = $this->middleware->getPipe();

        foreach ( $this->middleware->getPipes() as $i => $pipe ) {
            $this->pipes[ $i ] = $pipe;
        }

        foreach ( $this->middleware->getThrowables() as $i => $throwable ) {
            $this->throwables[ $i ] = $throwable;
        }

        reset($this->pipes);
        reset($this->throwables);
    }


    public function next($input = null, $context = null) // : mixed
    {
        $this->isNextCalled = true;

        $result = $this->processManager->next($this, $input, $context);

        return $result;
    }
}
