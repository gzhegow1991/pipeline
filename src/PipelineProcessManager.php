<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Step\Step;
use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;


class PipelineProcessManager implements PipelineProcessManagerInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;
    /**
     * @var PipelineProcessorInterface
     */
    protected $processor;


    public function __construct(
        PipelineFactoryInterface $factory,
        PipelineProcessorInterface $processor
    )
    {
        $this->factory = $factory;
        $this->processor = $processor;
    }


    public function newMiddlewareProcess(MiddlewareChain $middleware) : ?MiddlewareProcess
    {
        $process = new MiddlewareProcess($this, $middleware);

        return $process;
    }

    public function newPipelineProcess(PipelineChain $pipeline) : ?PipelineProcess
    {
        $process = new PipelineProcess($this, $pipeline);

        return $process;
    }


    public function newProcessFrom($from) : ?PipelineProcessInterface
    {
        $process = null
            ?? $this->newProcessFromInstance($from)
            ?? $this->newProcessFromPipeline($from)
            ?? $this->newProcessFromMiddleware($from);

        if (null === $process) {
            throw new LogicException(
                'Unable to create process from: ' . Lib::php_dump($from)
            );
        }

        return $process;
    }

    protected function newProcessFromInstance($from) : ?PipelineProcessInterface
    {
        if (! ($from instanceof PipelineProcessInterface)) {
            return null;
        }

        $process = clone $from;
        $process->reset();

        return $process;
    }

    protected function newProcessFromMiddleware($from) : ?MiddlewareProcess
    {
        if (! ($from instanceof MiddlewareChain)) {
            return null;
        }

        $process = $this->newMiddlewareProcess($from);

        return $process;
    }

    protected function newProcessFromPipeline($pipeline) : ?PipelineProcess
    {
        if (! ($pipeline instanceof PipelineChain)) {
            return null;
        }

        $process = $this->newPipelineProcess($pipeline);

        return $process;
    }


    public function run($pipeline, $input = null, $context = null) // : mixed
    {
        $result = null;

        $process = $this->newProcessFrom($pipeline);

        $resultArray = $this->doRun($process, $input, $context);

        if (count($resultArray)) {
            [ $result ] = $resultArray;
        }

        return $result;
    }

    public function next(PipelineProcessInterface $process, $input = null, $context = null) // : mixed
    {
        $result = null;

        $resultArray = $this->doNext($process, $input, $context);

        if (count($resultArray)) {
            [ $result ] = $resultArray;
        }

        return $result;
    }


    protected function doRun(PipelineProcessInterface $process, $input = null, $context = null) : array
    {
        $process->reset();

        $state = $process->getState();
        $state->inputOriginal = $input;

        $resultArray = $this->doNext($process, $input, $context);

        if ($throwables = $process->getThrowables()) {
            $e = new PipelineException(
                'Unhandled exception occured during processing pipeline', -1
            );

            foreach ( $throwables as $throwable ) {
                $e->addPrevious($throwable);
            }

            throw $e;
        }

        return $resultArray;
    }

    protected function doNext(PipelineProcessInterface $process, $input = null, $context = null) : array
    {
        $output = $input;
        $outputArray = [];

        while ( $step = $process->getNextStep() ) {
            $outputArray = $this->doStep(
                $step,
                $output,
                $context
            );

            $output = null;
            if (count($outputArray)) {
                [ $output ] = $outputArray;
            }
        }

        return $outputArray;
    }


    protected function doStep(
        Step $step,
        $input = null, $context = null
    ) : array
    {
        $process = $step->process;
        $pipe = $step->pipe;

        $resultArray = null
            ?? $this->doPipeMiddleware($process, $pipe, $input, $context)
            ?? $this->doPipeAction($process, $pipe, $input, $context)
            ?? $this->doPipeFallback($process, $pipe, $input, $context);

        if (null === $resultArray) {
            throw new RuntimeException(
                'Unable to process pipe: ' . Lib::php_dump($pipe)
            );
        }

        return $resultArray;
    }


    protected function doPipeMiddleware(
        PipelineProcessInterface $process, Pipe $pipe,
        $input = null, $context = null
    ) : ?array
    {
        if (null === ($handler = $pipe->handlerMiddleware)) {
            return null;
        }

        $resultArray = [];

        try {
            $resultArray = $this->processor->callMiddleware(
                $handler,
                $process, $input, $context
            );
        }
        catch ( \Throwable $e ) {
            $process->addThrowable($e);
        }

        return $resultArray;
    }

    protected function doPipeAction(
        PipelineProcessInterface $process, Pipe $pipe,
        $input = null, $context = null
    ) : ?array
    {
        if (null === ($handler = $pipe->handlerAction)) {
            return null;
        }

        if (null !== ($process->latestThrowable())) {
            return null;
        }

        $resultArray = [];

        try {
            $resultArray = $this->processor->callAction(
                $handler,
                $process,
                $input, $context
            );
        }
        catch ( \Throwable $e ) {
            $process->addThrowable($e);
        }

        return $resultArray;
    }

    protected function doPipeFallback(
        PipelineProcessInterface $process, Pipe $pipe,
        $input = null, $context = null
    ) : ?array
    {
        if (null === ($handler = $pipe->handlerFallback)) {
            return null;
        }

        if (null === ($throwable = $process->latestThrowable())) {
            return null;
        }

        $resultArray = [];

        try {
            $resultArray = $this->processor->callFallback(
                $handler,
                $process,
                $throwable, $input, $context
            );

            if (count($resultArray)) {
                $process->popThrowable();
            }
        }
        catch ( \Throwable $e ) {
            $process->addThrowable($e);
        }

        return $resultArray;
    }
}
