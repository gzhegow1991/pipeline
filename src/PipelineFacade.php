<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Exception\Runtime\PipelineException;
use Gzhegow\Pipeline\Core\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain as MiddlewareChain;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManagerInterface;


class PipelineFacade implements PipelineInterface
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


    public function pipeline() : PipelineChain
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
    public function run($pipeline, $input = null, $context = null)
    {
        $result = $this->processManager->run(
            $pipeline,
            $input, $context
        );

        return $result;
    }
}
