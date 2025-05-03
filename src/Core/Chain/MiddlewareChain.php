<?php

namespace Gzhegow\Pipeline\Core\Chain;

use Gzhegow\Pipeline\Core\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Core\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Core\Handler\Middleware\GenericHandlerMiddleware;


class MiddlewareChain extends AbstractPipelineChain
{
    /**
     * @var PipelinePipe<GenericHandlerMiddleware>
     */
    protected $pipe;

    /**
     * @var PipelineChainInterface
     */
    protected $parent;

    /**
     * @var PipelinePipe<PipelineChainInterface|GenericHandler>[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];


    public function __construct(PipelineFactoryInterface $factory, PipelinePipe $pipe)
    {
        parent::__construct($factory);

        if (! $pipe->hasHandlerMiddleware()) {
            throw new LogicException(
                [
                    'The `pipe` should be wrapper over: ' . GenericHandlerMiddleware::class,
                    $pipe,
                ]
            );
        }

        $this->pipe = $pipe;
    }


    /**
     * @return PipelinePipe<GenericHandlerMiddleware>
     */
    public function getPipe() : PipelinePipe
    {
        return $this->pipe;
    }
}
