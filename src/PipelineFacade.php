<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


class PipelineFacade implements PipelineFacadeInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;

    /**
     * @var PipelineProcessManagerInterface
     */
    protected $processManager;


    public function __construct(
        PipelineFactoryInterface $factory,
        //
        PipelineProcessManagerInterface $processManager
    )
    {
        $this->factory = $factory;

        $this->processManager = $processManager;
    }


    public function new() : PipelineChain
    {
        $pipeline = $this->factory->newPipeline();

        return $pipeline;
    }

    public function middleware($from) : MiddlewareChain
    {
        $middleware = $this->factory->newMiddleware($from);

        return $middleware;
    }


    /**
     * @throws PipelineException
     */
    public function run($pipeline, $input = null, $context = null) // : mixed
    {
        $result = $this->processManager->run(
            $pipeline,
            $input, $context
        );

        return $result;
    }
}
