<?php

namespace Gzhegow\Pipeline\Core\Process;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Step\PipelineStep;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManager;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManagerInterface;


abstract class AbstractProcess implements PipelineProcessInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;

    /**
     * @var PipelineProcessManager
     */
    protected $processManager;

    /**
     * @var PipelineProcessInterface
     */
    protected $childProcess;

    /**
     * @var PipelinePipe[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];


    public function __construct(
        PipelineFactoryInterface $factory,
        //
        PipelineProcessManagerInterface $processManager
    )
    {
        $this->factory = $factory;

        $this->processManager = $processManager;

        $this->reset();
    }


    public function isFinished() : bool
    {
        return (true
            && (false
                || ($this->childProcess === null)
                || ($this->childProcess->isFinished())
            )
            && (null === key($this->pipes))
        );
    }


    public function getNextStep() : ?PipelineStep
    {
        $step = null
            ?? $this->getNextStepChildProcess()
            ?? $this->getNextStepCurrentProcess();

        return $step;
    }

    private function getNextStepChildProcess() : ?PipelineStep
    {
        if (null === $this->childProcess) return null;

        if ($this->childProcess->isFinished()) {
            $throwables = $this->childProcess->getThrowables();

            if (count($throwables)) {
                foreach ( $throwables as $e ) {
                    $this->addThrowable($e);
                }
            }

            $this->childProcess = null;

            return null;
        }

        $step = $this->childProcess->getNextStep();

        return $step;
    }

    private function getNextStepCurrentProcess() : ?PipelineStep
    {
        if (null === key($this->pipes)) return null;

        $pipe = current($this->pipes);

        next($this->pipes);

        $step = null
            ?? $this->getNextStepCurrentProcessFromMiddleware($pipe)
            ?? $this->getNextStepCurrentProcessFromPipeline($pipe)
            ?? $this->getNextStepCurrentProcessFromHandlerAction($pipe)
            ?? $this->getNextStepCurrentProcessFromHandlerFallback($pipe);

        return $step;
    }

    private function getNextStepCurrentProcessFromMiddleware(PipelinePipe $pipe) : ?PipelineStep
    {
        if (! $pipe->hasMiddleware()) return null;

        $childProcess = $this->factory->newMiddlewareProcess(
            $this->processManager,
            $pipe->getMiddleware()
        );

        $step = $childProcess->getNextStep();

        $this->childProcess = $childProcess;

        return $step;
    }

    private function getNextStepCurrentProcessFromPipeline(PipelinePipe $pipe) : ?PipelineStep
    {
        if (! $pipe->hasPipeline()) return null;

        $childProcess = $this->factory->newPipelineProcess(
            $this->processManager,
            $pipe->getPipeline()
        );

        $step = $childProcess->getNextStep();

        $this->childProcess = $childProcess;

        return $step;
    }

    private function getNextStepCurrentProcessFromHandlerAction(PipelinePipe $pipe) : ?PipelineStep
    {
        if (! $pipe->hasHandlerAction()) return null;
        if (count($this->throwables)) return null;

        $step = new PipelineStep($this, $pipe);

        return $step;
    }

    private function getNextStepCurrentProcessFromHandlerFallback(PipelinePipe $pipe) : ?PipelineStep
    {
        if (! $pipe->hasHandlerFallback()) return null;
        if (! count($this->throwables)) return null;

        $step = new PipelineStep($this, $pipe);

        return $step;
    }


    public function reset() : void
    {
        $this->pipes = [];
        $this->throwables = [];

        $this->childProcess = null;
    }

    public function run($input = null, $context = null)
    {
        $result = $this->processManager->run($this, $input, $context);

        return $result;
    }

    public function next($input = null, $context = null)
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
    public function addThrowable(\Throwable $throwable)
    {
        $this->throwables[] = $throwable;

        return $this;
    }
}
