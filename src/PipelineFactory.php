<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


class PipelineFactory implements PipelineFactoryInterface
{
    public function newProcessor() : PipelineProcessorInterface
    {
        $processor = new PipelineProcessor($this);

        return $processor;
    }

    public function newProcessManager(
        PipelineProcessorInterface $processor = null
    ) : PipelineProcessManagerInterface
    {
        $processor = $processor ?? $this->newProcessor();

        $processManager = new PipelineProcessManager(
            $this,
            $processor
        );

        return $processManager;
    }

    public function newFacade(PipelineProcessManagerInterface $processManager = null) : Pipeline
    {
        $processManager = $processManager ?? $this->newProcessManager();

        $facade = new Pipeline(
            $this,
            $processManager
        );

        return $facade;
    }


    public function newPipeline() : PipelineChain
    {
        $pipeline = new PipelineChain($this);

        return $pipeline;
    }

    public function newMiddleware($from) : MiddlewareChain
    {
        $generic = GenericHandlerMiddleware::from($from);

        $pipe = Pipe::from($generic);

        $middleware = new MiddlewareChain($this, $pipe);

        return $middleware;
    }


    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        [ $args ] = Lib::array_kwargs($parameters);

        $object = new $class(...$args);

        return $object;
    }
}
