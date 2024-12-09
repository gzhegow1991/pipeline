<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Process\PipelineProcess;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\Process\MiddlewareProcess;
use Gzhegow\Pipeline\Process\PipelineProcessInterface;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


class PipelineFactory implements PipelineFactoryInterface
{
    public function newFacade(
        PipelineProcessManagerInterface $processManager
    ) : PipelineFacadeInterface
    {
        $facade = new PipelineFacade(
            $this,
            $processManager
        );

        return $facade;
    }


    public function newProcessor() : PipelineProcessorInterface
    {
        $processor = new PipelineProcessor($this);

        return $processor;
    }

    public function newProcessManager(
        PipelineProcessorInterface $processor
    ) : PipelineProcessManagerInterface
    {
        $processManager = new PipelineProcessManager(
            $this,
            $processor
        );

        return $processManager;
    }


    public function newPipeline() : PipelineChain
    {
        $pipeline = new PipelineChain($this);

        return $pipeline;
    }

    public function newMiddleware($from) : MiddlewareChain
    {
        $genericMiddleware = GenericHandlerMiddleware::from($from);

        $pipe = Pipe::from($genericMiddleware);

        $middleware = new MiddlewareChain($this, $pipe);

        return $middleware;
    }


    public function newMiddlewareProcess(
        PipelineProcessManagerInterface $processManager,
        //
        MiddlewareChain $middleware
    ) : ?MiddlewareProcess
    {
        $process = new MiddlewareProcess(
            $this,
            $processManager,
            //
            $middleware
        );

        return $process;
    }

    public function newPipelineProcess(
        PipelineProcessManagerInterface $processManager,
        //
        PipelineChain $pipeline
    ) : ?PipelineProcess
    {
        $process = new PipelineProcess(
            $this,
            $processManager,
            //
            $pipeline
        );

        return $process;
    }


    public function newProcessFrom(PipelineProcessManagerInterface $processManager, $from) : ?PipelineProcessInterface
    {
        $process = null
            ?? $this->newProcessFromInstance($from)
            ?? $this->newProcessFromPipeline($processManager, $from)
            ?? $this->newProcessFromMiddleware($processManager, $from);

        if (null === $process) {
            throw new LogicException(
                [
                    'Unable to create process from',
                    $from,
                ]
            );
        }

        return $process;
    }

    public function newProcessFromInstance($from) : ?PipelineProcessInterface
    {
        if (! ($from instanceof PipelineProcessInterface)) {
            return null;
        }

        $process = clone $from;
        $process->reset();

        return $process;
    }

    public function newProcessFromMiddleware(PipelineProcessManagerInterface $processManager, $from) : ?MiddlewareProcess
    {
        if (! ($from instanceof MiddlewareChain)) {
            return null;
        }

        $process = $this->newMiddlewareProcess($processManager, $from);

        return $process;
    }

    public function newProcessFromPipeline(PipelineProcessManagerInterface $processManager, $pipeline) : ?PipelineProcess
    {
        if (! ($pipeline instanceof PipelineChain)) {
            return null;
        }

        $process = $this->newPipelineProcess($processManager, $pipeline);

        return $process;
    }


    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        [ $args ] = Lib::array_kwargs($parameters);

        $object = new $class(...$args);

        return $object;
    }
}
