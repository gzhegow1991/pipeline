<?php

namespace Gzhegow\Pipeline\Process;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Step\Step;
use Gzhegow\Pipeline\ProcessManager\ProcessManager;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\ProcessManager\ProcessManagerInterface;


abstract class AbstractProcess implements PipelineProcessInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;

    /**
     * @var ProcessManager
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


    public function __construct(
        PipelineFactoryInterface $factory,
        //
        ProcessManagerInterface $processManager
    )
    {
        $this->factory = $factory;

        $this->processManager = $processManager;

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

        if ($pipe->middleware) {
            $nestedProcess = $this->factory->newMiddlewareProcess(
                $this->processManager,
                $pipe->middleware
            );

            $this->currentNestedProcess = $nestedProcess;

            $step = $nestedProcess->getNextStep();

            return $step;
        }

        if ($pipe->pipeline) {
            $nestedProcess = $this->factory->newPipelineProcess(
                $this->processManager,
                $pipe->pipeline
            );

            $this->currentNestedProcess = $nestedProcess;

            $step = $this->getNextStep();

            return $step;
        }

        if ($pipe->handlerFallback && ! count($this->throwables)) {
            $step = $this->getNextStep();

            return $step;
        }

        if ($pipe->handlerAction && count($this->throwables)) {
            $step = $this->getNextStep();

            return $step;
        }

        $step = new Step();
        $step->process = $this;
        $step->pipe = $pipe;

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
