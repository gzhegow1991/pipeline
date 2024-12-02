<?php

namespace Gzhegow\Pipeline\Process;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Step\Step;
use Gzhegow\Pipeline\PipelineProcessManager;


abstract class AbstractProcess implements PipelineProcessInterface
{
    /**
     * @var PipelineProcessManager
     */
    protected $processManager;

    /**
     * @var Pipe[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];

    /**
     * @var PipelineProcessInterface
     */
    protected $currentNestedProcess;

    /**
     * @var \stdClass
     */
    protected $state;


    public function __construct(PipelineProcessManager $pm)
    {
        $this->processManager = $pm;

        $this->reset();
    }


    public function getCurrentNestedProcess() : ?PipelineProcessInterface
    {
        return $this->currentNestedProcess;
    }

    /**
     * @return static
     */
    public function setCurrentNestedProcess(?PipelineProcessInterface $process) // : static
    {
        $this->currentNestedProcess = $process;

        return $this;
    }


    public function getState() : \stdClass
    {
        return $this->state;
    }


    public function isFinished() : bool
    {
        return (true
            && (false
                || ($this->currentNestedProcess === null)
                || ($this->currentNestedProcess->isFinished())
            )
            && (null === key($this->pipes))
        );
    }


    public function getNextStep() : ?Step
    {
        if (null !== $this->currentNestedProcess) {
            if ($this->currentNestedProcess->isFinished()) {
                $this->currentNestedProcess = null;

            } else {
                return $this->currentNestedProcess->getNextStep();
            }
        }

        if (null === key($this->pipes)) {
            return null;
        }

        $pipe = current($this->pipes);

        next($this->pipes);

        $step = null;

        if (null === $step) {
            if ($pipe->middleware) {
                $nestedProcess = $this->processManager->newMiddlewareProcess($pipe->middleware);

                $this->currentNestedProcess = $nestedProcess;

                $step = $nestedProcess->getNextStep();
            }
        }

        if (null === $step) {
            if ($pipe->pipeline) {
                $nestedProcess = $this->processManager->newPipelineProcess($pipe->pipeline);

                $this->currentNestedProcess = $nestedProcess;

                $step = $this->getNextStep();
            }
        }

        if (null === $step) {
            if ($pipe->handlerFallback && ! count($this->throwables)) {
                $step = $this->getNextStep();
            }
        }

        if (null === $step) {
            if ($pipe->handlerAction && count($this->throwables)) {
                $step = $this->getNextStep();
            }
        }

        if (null === $step) {
            $step = new Step();
            $step->process = $this;
            $step->pipe = $pipe;
        }

        return $step;
    }


    public function reset() : void
    {
        $this->pipes = [];
        $this->throwables = [];

        $this->currentNestedProcess = null;

        $this->state = new \stdClass();
    }

    public function run($input = null, $context = null) // : mixed
    {
        $result = $this->processManager->run($this, $input, $context);

        return $result;
    }

    public function next($input = null, $context = null) // : mixed
    {
        $result = $this->processManager->next($this, $input, $context);

        return $result;
    }


    /**
     * @return \Throwable[]
     */
    public function getThrowables() : array
    {
        return $this->throwables;
    }

    public function latestThrowable() : ?\Throwable
    {
        $throwable = end($this->throwables);

        if (null === key($this->throwables)) {
            return null;
        }

        return $throwable;
    }

    public function popThrowable() : ?\Throwable
    {
        if (null === ($throwable = $this->latestThrowable())) {
            return null;
        }

        array_pop($this->throwables);

        return $throwable;
    }

    /**
     * @return static
     */
    public function addThrowable(\Throwable $throwable) // : static
    {
        $this->throwables[] = $throwable;

        return $this;
    }
}
