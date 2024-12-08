<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Lib\Lib;
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

    public function newProcessManager() : PipelineProcessManagerInterface
    {
        $processor = $this->newProcessor();

        $processManager = new PipelineProcessManager(
            $this,
            $processor
        );

        return $processManager;
    }

    public function newFacade() : PipelineFacadeInterface
    {
        $processManager = $this->newProcessManager();

        $facade = new PipelineFacade(
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
        $genericMiddleware = GenericHandlerMiddleware::from($from);

        $pipe = Pipe::from($genericMiddleware);

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
