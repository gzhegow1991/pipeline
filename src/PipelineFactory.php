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
use Gzhegow\Pipeline\ProcessManager\ProcessManagerInterface;


class PipelineFactory implements PipelineFactoryInterface
{
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
        ProcessManagerInterface $processManager,
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
        ProcessManagerInterface $processManager,
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


    public function newProcessFrom(ProcessManagerInterface $processManager, $from) : ?PipelineProcessInterface
    {
        $process = null
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

    public function newProcessFromMiddleware(ProcessManagerInterface $processManager, $middleware) : ?MiddlewareProcess
    {
        if (! ($middleware instanceof MiddlewareChain)) {
            return null;
        }

        $process = $this->newMiddlewareProcess($processManager, $middleware);

        return $process;
    }

    public function newProcessFromPipeline(ProcessManagerInterface $processManager, $pipeline) : ?PipelineProcess
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
