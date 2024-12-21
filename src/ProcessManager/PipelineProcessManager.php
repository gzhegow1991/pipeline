<?php

namespace Gzhegow\Pipeline\ProcessManager;

use Gzhegow\Pipeline\Step\PipelineStep;
use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Processor\PipelineProcessorInterface;
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
        //
        PipelineProcessorInterface $processor
    )
    {
        $this->factory = $factory;

        $this->processor = $processor;
    }


    public function run($pipeline, $input = null, $context = null) // : mixed
    {
        $result = null;

        $process = $this->factory->newProcessFrom(
            $this,
            $pipeline
        );

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
        PipelineStep $step,
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
                [
                    'Unable to process pipe',
                    $pipe,
                ]
            );
        }

        return $resultArray;
    }


    protected function doPipeMiddleware(
        PipelineProcessInterface $process, PipelinePipe $pipe,
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
        PipelineProcessInterface $process, PipelinePipe $pipe,
        $input = null, $context = null
    ) : ?array
    {
        if (null === ($handler = $pipe->handlerAction)) {
            return null;
        }

        if (null !== ($throwable = $process->latestThrowable())) {
            throw new RuntimeException(
                [
                    'The `action` pipe should not be called with any throwables in stack',
                    $pipe,
                    $throwable,
                ]
            );
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
        PipelineProcessInterface $process, PipelinePipe $pipe,
        $input = null, $context = null
    ) : ?array
    {
        if (null === ($handler = $pipe->handlerFallback)) {
            return null;
        }

        if (null === ($throwable = $process->latestThrowable())) {
            throw new RuntimeException(
                [
                    'The `fallback` pipe should not be called without any throwables in stack',
                    $pipe,
                ]
            );
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
